<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategoryTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_name_id',
        'name',
        'sort_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'service_name_id' => 'integer',
            'sort_id' => 'integer',
            'created_by' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            if (blank($template->sort_id)) {
                $template->sort_id = static::nextSortId((int) $template->service_name_id);
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN sort_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('sort_id')
            ->orderByDesc('id');
    }

    public static function nextSortId(int $serviceNameId): int
    {
        $nextSortId = (int) static::query()
            ->where('service_name_id', $serviceNameId)
            ->max('sort_id');

        return $nextSortId > 0 ? $nextSortId + 1 : 1;
    }

    public function serviceName(): BelongsTo
    {
        return $this->belongsTo(ServiceName::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
