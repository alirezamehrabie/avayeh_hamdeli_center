<?php

namespace App\Models;

use App\Support\Landing\LandingContent;
use App\Support\Landing\LandingMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingServiceCard extends Model
{
    public const RAIL_ROWS = [1, 2];

    protected $fillable = [
        'image_path',
        'title',
        'rail_row',
        'sort_id',
        'active_status',
        'created_by',
    ];

    protected $casts = [
        'active_status' => 'boolean',
        'rail_row' => 'integer',
        'sort_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $card): void {
            if (blank($card->sort_id)) {
                $card->sort_id = (int) static::query()->where('rail_row', $card->rail_row)->max('sort_id') + 1;
            }
        });

        // هر تغییر در کارت‌ها باید کش بخش عمومی لندینگ را باطل کند.
        static::saved(function (): void {
            LandingContent::flush();
        });
        static::deleted(function (): void {
            LandingContent::flush();
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('rail_row')->orderBy('sort_id')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_status', true);
    }

    public function scopeRail(Builder $query, int $railRow): Builder
    {
        return $query->where('rail_row', $railRow);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageUrlAttribute(): ?string
    {
        return LandingMediaUrl::for($this->image_path);
    }
}
