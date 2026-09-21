<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingBanner extends Model
{
    protected $fillable = [
        'image_path',
        'alt_text',
        'link_url',
        'sort_id',
        'active_status',
        'created_by',
    ];

    protected $casts = [
        'active_status' => 'boolean',
        'sort_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $banner): void {
            if (blank($banner->sort_id)) {
                $banner->sort_id = (int) static::query()->max('sort_id') + 1;
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_id')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_status', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Public URL for the banner image. Landing assets are served straight from
     * /public (guest-facing), unlike authenticated /media streams.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        return asset(ltrim(str_replace('\\', '/', $this->image_path), '/'));
    }
}
