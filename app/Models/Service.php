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
        'package' => 'بسته',
        'piece' => 'دست',
        'count' => 'عدد',
        'portion' => 'پرس',
        'kilogram' => 'کیلوگرم',
        'gram' => 'گرم',
    ];

    protected $fillable = [
        'code',
        'name',
        'service_name_id',
        'service_type',
        'description',
        'total_quantity',
        'value_per_unit',
        'total_service_value',
        'total_financial_value',
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
            'total_quantity' => 'decimal:2',
            'quantity_delivered' => 'decimal:2',
            'value_per_unit' => 'integer',
            'total_service_value' => 'integer',
            'total_financial_value' => 'integer',
            'created_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $service): void {
            if (blank($service->code)) {
                $service->code = static::generateNextCode();
            }


            if (Schema::hasColumn($service->getTable(), 'total_financial_value') && blank($service->total_financial_value)) {
                $service->total_financial_value = 0;
            }
        });

        static::updated(function (self $service): void {
            $service->syncDeliveryValues();
        });
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

        return $units !== [] ? $units : static::FALLBACK_UNIT_OPTIONS;
    }

    public static function unitKeys(): array
    {
        return array_keys(static::unitOptions());
    }

    public function serviceName(): BelongsTo
    {
        return $this->belongsTo(ServiceName::class);
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
        return $this->belongsToMany(SocialWorker::class)->withPivot('allocated_quantity')->withTimestamps();
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

    public function getAllocatedQuantityAttribute(): float
    {
        if ($this->relationLoaded('socialWorkers')) {
            return (float) $this->socialWorkers->sum(fn ($worker) => (float) ($worker->pivot->allocated_quantity ?? 0));
        }

        return (float) $this->socialWorkers()->sum('service_social_worker.allocated_quantity');
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
        if (! Schema::hasColumn($this->getTable(), 'total_financial_value')) {
            return;
        }

        $totalQuantity = (float) $this->categories()
            ->selectRaw('COALESCE(SUM(quantity), 0) as total')
            ->value('total');
        $totalFinancialValue = (int) $this->categories()
            ->selectRaw('COALESCE(SUM(quantity * value), 0) as total')
            ->value('total');

        $this->forceFill([
            'total_quantity' => $totalQuantity,
            'total_financial_value' => $totalFinancialValue,
            'total_service_value' => $totalFinancialValue,
        ])->saveQuietly();
    }

    public function syncDeliveryValues(): void
    {
        if (! $this->wasChanged('value_per_unit')) {
            return;
        }

        $valuePerUnit = (int) $this->value_per_unit;

        $this->deliveries()
            ->update([
                'value_per_unit_snapshot' => $valuePerUnit,
                'delivered_total_value' => DB::raw('ROUND(delivered_quantity * ' . $valuePerUnit . ')'),
            ]);
    }

    public function allocatedQuantityForWorker(int $socialWorkerId): float
    {
        $worker = $this->socialWorkers->firstWhere('id', $socialWorkerId);

        return (float) ($worker?->pivot?->allocated_quantity ?? 0);
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

}
