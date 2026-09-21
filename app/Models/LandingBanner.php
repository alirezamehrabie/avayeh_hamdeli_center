<?php

namespace App\Models;

use App\Support\Landing\LandingContent;
use App\Support\Landing\LandingMediaUrl;
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

        // هر تغییر در بنرها باید کش بخش عمومی لندینگ را باطل کند.
        static::saved(function (): void {
            LandingContent::flush();
        });
        static::deleted(function (): void {
            LandingContent::flush();
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
     * Public URL for the banner image, covering both repo-shipped files under
     * public/images/landing and admin uploads streamed from the public disk.
     */
    public function getImageUrlAttribute(): ?string
    {
        return LandingMediaUrl::for($this->image_path);
    }
}
