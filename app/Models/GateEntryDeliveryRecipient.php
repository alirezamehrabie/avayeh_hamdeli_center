<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Entry Gate operator's declaration that a scanned subject's items are handed to somebody
 * other than the beneficiary (a parent, the group leader, or a named third party).
 *
 * One row per (service, subject) — the same grain as GateEntryFieldValue — so a declaration covers
 * every item authorized for that subject at that service.
 */
class GateEntryDeliveryRecipient extends Model
{
    public const TYPE_MOTHER = 'mother';

    public const TYPE_FATHER = 'father';

    public const TYPE_GROUP_LEADER = 'group_leader';

    public const TYPE_OTHER = 'other';

    public const TYPE_OPTIONS = [
        self::TYPE_MOTHER => 'مادر',
        self::TYPE_FATHER => 'پدر',
        self::TYPE_GROUP_LEADER => 'سرگروه (یاریگر)',
        self::TYPE_OTHER => 'سایر',
    ];

    protected $fillable = [
        'service_id',
        'person_id',
        'guardian_id',
        'is_proxy_delivery',
        'recipient_type',
        'recipient_name',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_proxy_delivery' => 'boolean',
            'service_id' => 'integer',
            'person_id' => 'integer',
            'guardian_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Human-readable recipient for identity cards and reports: the chosen relation, or the typed
     * name when the operator picked "سایر". Empty when this row records a direct handover.
     */
    public function getRecipientLabelAttribute(): string
    {
        if (! $this->is_proxy_delivery) {
            return '';
        }

        if ($this->recipient_type === self::TYPE_OTHER) {
            return trim((string) $this->recipient_name) ?: self::TYPE_OPTIONS[self::TYPE_OTHER];
        }

        return self::TYPE_OPTIONS[$this->recipient_type] ?? '';
    }
}
