<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityOperatorAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'user_id',
        'assigned_by',
        'notes',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'user_id' => 'integer',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
