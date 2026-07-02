<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GateEntryAssignment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'service_id',
        'service_category_id',
        'person_id',
        'guardian_id',
        'national_id',
        'full_name',
        'mobile',
        'status',
        'assigned_at',
        'delivered_at',
        'delivered_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
            'service_id' => 'integer',
            'service_category_id' => 'integer',
            'person_id' => 'integer',
            'guardian_id' => 'integer',
            'delivered_by' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function getRecipientNameAttribute(): string
    {
        $storedName = trim((string) ($this->full_name ?? ''));

        if ($storedName !== '') {
            return $storedName;
        }

        if ($this->person) {
            return trim(implode(' ', array_filter([$this->person->first_name, $this->person->last_name]))) ?: '-';
        }

        if ($this->guardian) {
            return trim(implode(' ', array_filter([$this->guardian->first_name, $this->guardian->last_name]))) ?: '-';
        }

        return '-';
    }
}
