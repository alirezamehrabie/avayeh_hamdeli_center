<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceName extends Model
{
    protected $fillable = [
        'name',
        'sort_id',
        'created_by',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('sort_id')->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }
}
