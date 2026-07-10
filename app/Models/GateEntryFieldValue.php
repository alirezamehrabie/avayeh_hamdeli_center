<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateEntryFieldValue extends Model
{
    protected $fillable = [
        'service_id',
        'person_id',
        'guardian_id',
        'values',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'service_id' => 'integer',
            'person_id' => 'integer',
            'guardian_id' => 'integer',
            'created_by' => 'integer',
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
}
