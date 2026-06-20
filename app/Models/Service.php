<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Service extends Model
{
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
    ];

    public const DELIVERY_CHANNEL_GATE = 'gate';

    public const DELIVERY_CHANNEL_HOME = 'home';

    public const DELIVERY_CHANNEL_OPTIONS = [
        self::DELIVERY_CHANNEL_GATE => 'ایستگاه توزیع',
        self::DELIVERY_CHANNEL_HOME => 'تحویل در منزل',
    ];

    public const UNIT_OPTION_ORDER = [
        'count',
        'portion',
        'pack',
        'dast',
        'kilogram',
        'gram',
    ];

    protected $fillable = [
        'code',
        'name',
        'service_name_id',
        'service_type',
        'supports_gate_delivery',
        'supports_home_delivery',
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
            'total_quantity' => 'decimal:2',
            'quantity_delivered' => 'decimal:2',
            'total_service_value' => 'integer',
            'created_by' => 'integer',
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

    public function getDeliveryChannelLabelsAttribute(): array
    {
        $labels = [];

        if ($this->supports_gate_delivery) {
            $labels[] = self::DELIVERY_CHANNEL_OPTIONS[self::DELIVERY_CHANNEL_GATE];
        }

        if ($this->supports_home_delivery) {
            $labels[] = self::DELIVERY_CHANNEL_OPTIONS[self::DELIVERY_CHANNEL_HOME];
        }

        return $labels;
    }

    public static function generateNextCode(): string
    {
        $lastSequence = (int) static::query()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(code, "-", -1) AS UNSIGNED)) as sequence')
            ->value('sequence');

        if ($lastSequence <= 0) {
            $lastSequence = (int) static::query()
                ->selectRaw('MAX(id) as sequence')
                ->value('sequence');
        }

        return 'SN-' . str_pad((string) ($lastSequence + 1), 5, '0', STR_PAD_LEFT);
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

        $payload = [
            'quantity_delivered' => $deliveredQuantity,
        ];

        if ($deliveredQuantity >= (float) $this->total_quantity && (float) $this->total_quantity > 0) {
            $payload['status'] = 'completed';
        } elseif ($deliveredQuantity > 0) {
            $payload['status'] = 'in_distribution';
        } elseif (in_array($this->status, ['in_distribution', 'completed'], true)) {
            $payload['status'] = 'approved';
        }

        $this->forceFill($payload)->saveQuietly();
    }

    public function refreshFinancialTotals(): void
    {
        if (! Schema::hasColumn($this->getTable(), 'total_service_value')) {
            return;
        }

        $totalFinancialValue = (int) $this->categories()
            ->selectRaw('COALESCE(SUM(quantity * value), 0) as total')
            ->value('total');

        $this->forceFill([
            'total_service_value' => $totalFinancialValue,
        ])->saveQuietly();
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
                $valuePerUnit = (int) ($delivery->serviceCategory?->value ?? $this->deliveryUnitValue());

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
