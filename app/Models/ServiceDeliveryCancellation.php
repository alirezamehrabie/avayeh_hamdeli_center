<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record of a ServiceDelivery ledger row that was purged at the Exit Gate when a
 * finalized delivery was cancelled. The ledger row is force-deleted so its unique
 * gate_entry_assignment_id slot can be reused by a clean re-finalization; this table keeps the full
 * snapshot of what was delivered, plus who cancelled it and when.
 *
 * Rows are never updated or deleted by application code.
 */
class ServiceDeliveryCancellation extends Model
{
    protected $fillable = [
        'service_delivery_id',
        'gate_entry_assignment_id',
        'service_id',
        'service_category_id',
        'person_id',
        'guardian_id',
        'delivered_quantity',
        'delivered_total_value',
        'delivered_at',
        'delivery_snapshot',
        'canceled_by',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'service_delivery_id' => 'integer',
            'gate_entry_assignment_id' => 'integer',
            'service_id' => 'integer',
            'service_category_id' => 'integer',
            'person_id' => 'integer',
            'guardian_id' => 'integer',
            'delivered_quantity' => 'decimal:2',
            'delivered_total_value' => 'integer',
            'delivered_at' => 'date',
            'delivery_snapshot' => 'array',
            'canceled_by' => 'integer',
            'canceled_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class)->withTrashed();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(GateEntryAssignment::class, 'gate_entry_assignment_id')->withTrashed();
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    /**
     * The archived ledger row, hydrated from the snapshot — lets a report render a cancelled
     * delivery exactly like an active one. The instance is detached: saving it is not possible.
     */
    public function snapshotDelivery(): ServiceDelivery
    {
        $delivery = new ServiceDelivery;
        $delivery->setRawAttributes((array) ($this->delivery_snapshot ?? []), true);

        return $delivery;
    }
}
