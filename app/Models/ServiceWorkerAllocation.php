<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceWorkerAllocation extends Model
{
    protected $table = 'service_social_worker';

    protected $fillable = [
        'service_id',
        'service_category_id',
        'social_worker_id',
        'allocated_quantity',
        'assigned_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'service_category_id' => 'integer',
            'social_worker_id' => 'integer',
            'allocated_quantity' => 'decimal:2',
            'assigned_by_user_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $allocation): void {
            $allocation->service?->refreshDeliveryProgress();
        });

        static::updated(function (self $allocation): void {
            $allocation->service?->refreshDeliveryProgress();
        });

        static::deleted(function (self $allocation): void {
            $allocation->service?->refreshDeliveryProgress();
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function socialWorker(): BelongsTo
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
