<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    public const TYPE_OPTIONS = [
        'individual' => 'شخصی (مددجو)',
        'family' => 'خانوادگی (سرپرست)',
    ];

    public const UNIT_OPTIONS = [
        'package' => 'بسته',
        'piece' => 'دست',
        'count' => 'عدد',
        'portion' => 'پرس',
        'kilogram' => 'کیلوگرم',
        'gram' => 'گرم',
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

    protected $fillable = [
        'service_code',
        'service_name_id',
        'service_category_id',
        'service_type',
        'description',
        'total_quantity',
        'service_unit',
        'value_per_unit',
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
            'total_quantity' => 'decimal:2',
            'quantity_delivered' => 'decimal:2',
            'value_per_unit' => 'integer',
            'total_service_value' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public static function generateNextCode(): string
    {
        $lastId = (int) static::query()->max('id');

        return 'SRV-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }

    public function serviceName(): BelongsTo
    {
        return $this->belongsTo(ServiceName::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
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

    public function deliveries(): HasMany
    {
        return $this->hasMany(ServiceDelivery::class);
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
