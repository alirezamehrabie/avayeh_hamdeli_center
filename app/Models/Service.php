<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Service extends Model
{
    use SoftDeletes;

    public const TYPE_DISPLAY_META = [
        'individual' => [
            'label' => 'شخصی',
            'icon' => 'person',
        ],
        'family' => [
            'label' => 'خانوادگی',
            'icon' => 'family',
        ],
    ];

    public const TYPE_OPTIONS = [
        'individual' => 'شخصی (مددجو)',
        'family' => 'خانوادگی (سرپرست)',
    ];

    public const STATUS_OPTIONS = [
        'draft' => 'پیش نویس',
        'approved' => 'تأیید شده',
        'in_distribution' => 'در حال توزیع',
        'completed' => 'تکمیل شده',
    ];

    public const PRIORITY_OPTIONS = [
        'low' => 'کم',
        'normal' => 'عادی',
        'high' => 'بالا',
        'urgent' => 'فوری',
    ];

    public const FALLBACK_UNIT_OPTIONS = [
        'count' => 'عدد',
        'portion' => 'پرس',
        'pack' => 'بسته',
        'dast' => 'دست',
        'kilogram' => 'کیلوگرم',
        'gram' => 'گرم',
        'rial' => 'ریال',
        'pair' => 'جفت',
    ];

    public const DELIVERY_CHANNEL_GATE = 'gate';

    public const DELIVERY_CHANNEL_HOME = 'home';

    public const DELIVERY_CHANNEL_ACTIVITY = 'activity';

    public const DELIVERY_CHANNEL_OPTIONS = [
        self::DELIVERY_CHANNEL_GATE => 'ایستگاه توزیع',
        self::DELIVERY_CHANNEL_HOME => 'تحویل در منزل',
        self::DELIVERY_CHANNEL_ACTIVITY => 'تحویل در فعالیت',
    ];

    public const UNIT_OPTION_ORDER = [
        'count',
        'portion',
        'pack',
        'dast',
        'kilogram',
        'gram',
        'rial',
        'pair',
    ];

    protected $fillable = [
        'code',
        'activity_id',
        'name',
        'service_name_id',
        'service_type',
        'supports_gate_delivery',
        'supports_home_delivery',
        'supports_activity_delivery',
        'description',
        'total_quantity',
        'total_service_value',
        'district_id',
        'distribution_start_date',
        'distribution_end_date',
        'priority',
        'status',
        'status_notes',
        'quantity_delivered',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'distribution_start_date' => 'date',
            'distribution_end_date' => 'date',
            'supports_gate_delivery' => 'boolean',
            'supports_home_delivery' => 'boolean',
            'supports_activity_delivery' => 'boolean',
            'total_quantity' => 'decimal:2',
            'quantity_delivered' => 'decimal:2',
            'total_service_value' => 'integer',
            'created_by' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $service): void {
            if (blank($service->code)) {
                $service->code = static::generateNextCode();
            }

            if ($service->supports_gate_delivery === null) {
                $service->supports_gate_delivery = true;
            }

            if ($service->supports_home_delivery === null) {
                $service->supports_home_delivery = true;
            }

            if ($service->supports_activity_delivery === null) {
                $service->supports_activity_delivery = false;
            }
        });

        static::deleting(function (self $service): void {
            if ($service->isForceDeleting()) {
                return;
            }

            $service->categories()
                ->whereNull('deleted_at')
                ->get()
                ->each(function (ServiceCategory $category): void {
                    $category->deleteQuietly();
                });
        });

        static::restoring(function (self $service): void {
            $service->categories()
                ->withTrashed()
                ->whereNotNull('deleted_at')
                ->get()
                ->each(function (ServiceCategory $category): void {
                    $category->restoreQuietly();
                });
        });

        static::forceDeleting(function (self $service): void {
            if (app()->isProduction()) {
                throw new \RuntimeException('Services cannot be permanently deleted in production.');
            }
        });
    }

    public function scopeSupportsGateDelivery($query)
    {
        return $query->where('supports_gate_delivery', true);
    }

    public function scopeSupportsHomeDelivery($query)
    {
        return $query->where('supports_home_delivery', true);
    }

    public function scopeSupportsActivityDelivery($query)
    {
        return $query->where('supports_activity_delivery', true);
    }

    /**
     * Misc ("متفرقه") services are those defined by a distribution operator.
     * They are shared across every distribution operator rather than being
     * scoped to the operator who created them.
     */
    public function scopeCreatedByDistributionOperator($query)
    {
        return $query->whereHas('creator', function ($creatorQuery): void {
            $creatorQuery->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
        });
    }

    /**
     * Predefined / campaign services are everything that is NOT a misc service:
     * services with no creator or whose creator is not a distribution operator.
     */
    public function scopeNotCreatedByDistributionOperator($query)
    {
        return $query->where(function ($outerQuery): void {
            $outerQuery->whereNull('created_by')
                ->orWhereDoesntHave('creator', function ($creatorQuery): void {
                    $creatorQuery->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
                });
        });
    }

    /**
     * Whether this service was defined by a distribution operator (a misc
     * service), as opposed to an admin-defined predefined/campaign service.
     */
    public function isMiscDefinedService(): bool
    {
        return $this->creator?->access_level === User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR;
    }

    public function getDeliveryChannelLabelsAttribute(): array
    {
        $labels = [];

        if ($this->supports_gate_delivery) {
            $labels[] = self::DELIVERY_CHANNEL_OPTIONS[self::DELIVERY_CHANNEL_GATE];
        }

        if ($this->supports_home_delivery) {
            $labels[] = self::DELIVERY_CHANNEL_OPTIONS[self::DELIVERY_CHANNEL_HOME];
        }

        if ($this->supports_activity_delivery) {
            $labels[] = self::DELIVERY_CHANNEL_OPTIONS[self::DELIVERY_CHANNEL_ACTIVITY];
        }

        return $labels;
    }

    public static function generateNextCode(): string
    {
        $lastSequence = (int) static::withTrashed()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(code, "-", -1) AS UNSIGNED)) as sequence')
            ->value('sequence');

        if ($lastSequence <= 0) {
            $lastSequence = (int) static::withTrashed()
                ->selectRaw('MAX(id) as sequence')
                ->value('sequence');
        }

        return 'SN-'.str_pad((string) ($lastSequence + 1), 5, '0', STR_PAD_LEFT);
    }

    public static function unitOptions(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('service_units')) {
            return static::FALLBACK_UNIT_OPTIONS;
        }

        $units = ServiceUnit::query()
            ->ordered()
            ->pluck('label', 'key')
            ->all();

        return $units !== []
            ? static::sortUnitOptions($units + static::FALLBACK_UNIT_OPTIONS)
            : static::FALLBACK_UNIT_OPTIONS;
    }

    public static function unitKeys(): array
    {
        return array_keys(static::unitOptions());
    }

    public static function typeDisplayMeta(?string $type): array
    {
        return static::TYPE_DISPLAY_META[$type] ?? [
            'label' => (string) $type,
            'icon' => 'person',
        ];
    }

    public static function typeDisplayOptions(): array
    {
        return collect(static::TYPE_DISPLAY_META)
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label']])
            ->all();
    }

    protected static function sortUnitOptions(array $units): array
    {
        $orderedUnits = [];

        foreach (static::UNIT_OPTION_ORDER as $key) {
            if (array_key_exists($key, $units)) {
                $orderedUnits[$key] = $units[$key];
            }
        }

        return $orderedUnits + array_diff_key($units, $orderedUnits);
    }

    public function serviceName(): BelongsTo
    {
        return $this->belongsTo(ServiceName::class)->withTrashed();
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function serviceCategory(): HasOne
    {
        return $this->hasOne(ServiceCategory::class)->withTrashed()->ordered();
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function socialWorkers(): BelongsToMany
    {
        return $this->belongsToMany(SocialWorker::class)
            ->withPivot('service_category_id', 'allocated_quantity')
            ->withTimestamps()
            ->distinct();
    }

    public function uniqueSocialWorkersCount(): int
    {
        if ($this->relationLoaded('socialWorkers')) {
            return (int) $this->socialWorkers->unique('id')->count();
        }

        return (int) $this->socialWorkers()
            ->getQuery()
            ->distinct()
            ->count('social_workers.id');
    }

    public function workerAllocations(): HasMany
    {
        return $this->hasMany(ServiceWorkerAllocation::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function entryFields(): HasMany
    {
        return $this->hasMany(ServiceEntryField::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ServiceDelivery::class);
    }

    public function getServiceUnitAttribute(): ?string
    {
        $category = $this->serviceCategory;

        return $category?->unit ? (string) $category->unit : null;
    }

    public function getNameAttribute($value): string
    {
        $name = trim((string) $value);

        if ($name !== '') {
            return $name;
        }

        return (string) ($this->serviceName?->name ?? '');
    }

    public function getServiceNameIdAttribute($value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->total_quantity - (float) $this->quantity_delivered);
    }

    public function getRemainingStockQuantityAttribute(): float
    {
        return max(0, (float) $this->total_quantity - (float) $this->quantity_delivered);
    }

    public function getAllocatedQuantityAttribute(): float
    {
        if ($this->relationLoaded('workerAllocations')) {
            return (float) $this->workerAllocations->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
        }

        return (float) $this->workerAllocations()->sum('allocated_quantity');
    }

    public function getRemainingAssignableQuantityAttribute(): float
    {
        return max(0, (float) $this->total_quantity - $this->allocated_quantity);
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalQuantity = (float) $this->total_quantity;

        if ($totalQuantity <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->quantity_delivered / $totalQuantity) * 100, 2));
    }

    public function refreshDeliveryProgress(): void
    {
        $deliveredQuantity = (float) $this->deliveries()->sum('delivered_quantity');
        $allocatedQuantities = $this->workerAllocations()
            ->select('social_worker_id', 'service_category_id')
            ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
            ->groupBy('social_worker_id', 'service_category_id')
            ->havingRaw('COALESCE(SUM(allocated_quantity), 0) > 0')
            ->get();

        $payload = [
            'quantity_delivered' => $deliveredQuantity,
        ];

        if ($allocatedQuantities->isNotEmpty()) {
            $deliveredByAllocation = $this->deliveries()
                ->select('social_worker_id', 'service_category_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->whereIn('social_worker_id', $allocatedQuantities->pluck('social_worker_id')->unique()->all())
                ->whereIn('service_category_id', $allocatedQuantities->pluck('service_category_id')->unique()->all())
                ->groupBy('social_worker_id', 'service_category_id')
                ->get()
                ->mapWithKeys(fn ($delivery): array => [
                    $delivery->social_worker_id.':'.$delivery->service_category_id => (float) $delivery->delivered_quantity,
                ]);

            $allAllocationsCompleted = $allocatedQuantities->every(function ($allocation) use ($deliveredByAllocation): bool {
                $key = $allocation->social_worker_id.':'.$allocation->service_category_id;

                return (float) ($deliveredByAllocation->get($key) ?? 0) >= (float) $allocation->allocated_quantity;
            });

            $payload['status'] = $allAllocationsCompleted ? 'completed' : 'in_distribution';
        } else {
            if ($deliveredQuantity >= (float) $this->total_quantity && (float) $this->total_quantity > 0) {
                $payload['status'] = 'completed';
            } elseif ($deliveredQuantity > 0) {
                $payload['status'] = 'in_distribution';
            } elseif (in_array($this->status, ['in_distribution', 'completed'], true)) {
                $payload['status'] = 'approved';
            }
        }

        $this->forceFill($payload)->saveQuietly();
    }

    public function refreshFinancialTotals(): void
    {
        if (! Schema::hasTable('service_categories')) {
            return;
        }

        $payload = [];

        if (Schema::hasColumn($this->getTable(), 'total_quantity')) {
            $payload['total_quantity'] = (float) $this->categories()
                ->selectRaw('COALESCE(SUM(quantity), 0) as total')
                ->value('total');
        }

        if (Schema::hasColumn($this->getTable(), 'total_service_value')) {
            $payload['total_service_value'] = (int) $this->categories()
                ->selectRaw('COALESCE(SUM(quantity * value), 0) as total')
                ->value('total');
        }

        if ($payload === []) {
            return;
        }

        $this->forceFill($payload)->saveQuietly();
    }

    public function deliveryUnitValue(): int
    {
        $totalQuantity = (float) $this->total_quantity;

        if ($totalQuantity <= 0) {
            return 0;
        }

        $totalValue = (float) ($this->total_service_value ?: 0);

        return (int) round($totalValue / $totalQuantity);
    }

    public function syncDeliveryValues(): void
    {
        if (! Schema::hasTable('service_deliveries')) {
            return;
        }

        $this->deliveries()
            ->with('serviceCategory')
            ->get()
            ->each(function (ServiceDelivery $delivery): void {
                $valuePerUnit = $delivery->serviceCategory
                    ? (int) ($delivery->serviceCategory->value ?? 0)
                    : $this->deliveryUnitValue();

                $delivery->forceFill([
                    'value_per_unit_snapshot' => $valuePerUnit,
                    'delivered_total_value' => (int) round((float) $delivery->delivered_quantity * $valuePerUnit),
                ])->saveQuietly();
            });
    }

    public function allocatedQuantityForWorker(int $socialWorkerId): float
    {
        if ($this->relationLoaded('workerAllocations')) {
            return (float) $this->workerAllocations
                ->where('social_worker_id', $socialWorkerId)
                ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
        }

        return (float) $this->workerAllocations()
            ->where('social_worker_id', $socialWorkerId)
            ->sum('allocated_quantity');
    }

    public function deliveredQuantityForWorker(int $socialWorkerId): float
    {
        return (float) $this->deliveries()
            ->where('social_worker_id', $socialWorkerId)
            ->sum('delivered_quantity');
    }

    public function remainingAllocationForWorker(int $socialWorkerId): float
    {
        return max(0, $this->allocatedQuantityForWorker($socialWorkerId) - $this->deliveredQuantityForWorker($socialWorkerId));
    }

    public function allocatedQuantityForWorkerCategory(int $socialWorkerId, int $serviceCategoryId): float
    {
        if ($this->relationLoaded('workerAllocations')) {
            return (float) $this->workerAllocations
                ->where('social_worker_id', $socialWorkerId)
                ->where('service_category_id', $serviceCategoryId)
                ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
        }

        return (float) $this->workerAllocations()
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_category_id', $serviceCategoryId)
            ->sum('allocated_quantity');
    }

    public function deliveredQuantityForWorkerCategory(int $socialWorkerId, int $serviceCategoryId): float
    {
        return (float) $this->deliveries()
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_category_id', $serviceCategoryId)
            ->sum('delivered_quantity');
    }

    public function deliveredQuantityForCategory(int $serviceCategoryId): float
    {
        return (float) $this->deliveries()
            ->where('service_category_id', $serviceCategoryId)
            ->sum('delivered_quantity');
    }

    public function remainingStockForCategory(int $serviceCategoryId): float
    {
        $category = $this->relationLoaded('categories')
            ? $this->categories->firstWhere('id', $serviceCategoryId)
            : $this->categories()->whereKey($serviceCategoryId)->first();

        if (! $category) {
            return 0;
        }

        return max(0, (float) $category->quantity - $this->deliveredQuantityForCategory($serviceCategoryId));
    }

    public function remainingAllocationForWorkerCategory(int $socialWorkerId, int $serviceCategoryId): float
    {
        return max(
            0,
            $this->allocatedQuantityForWorkerCategory($socialWorkerId, $serviceCategoryId)
                - $this->deliveredQuantityForWorkerCategory($socialWorkerId, $serviceCategoryId)
        );
    }
}
