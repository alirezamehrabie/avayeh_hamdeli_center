<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceUnit extends Model
{
    protected $fillable = [
        'key',
        'label',
        'sort_id',
        'created_by',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_id')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
