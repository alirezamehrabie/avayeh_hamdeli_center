<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    protected $fillable = [
        'service_name_id',
        'name',
        'sort_id',
        'created_by',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN sort_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_id')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function serviceName(): BelongsTo
    {
        return $this->belongsTo(ServiceName::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
