<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MandatoryAnnouncement extends Model
{
    use HasFactory, SoftDeletes;

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_IMPORTANT = 'important';

    public const PRIORITY_URGENT = 'urgent';

    public const TARGET_ALL_SOCIAL_WORKERS = 'all_social_workers';

    public const TARGET_SPECIFIC_SOCIAL_WORKERS = 'specific_social_workers';

    protected $fillable = [
        'created_by',
        'title',
        'body',
        'priority',
        'target_type',
        'is_blocking',
        'min_reading_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_blocking' => 'boolean',
            'min_reading_seconds' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MandatoryAnnouncementRecipient::class, 'announcement_id');
    }

    public function readRecipients(): HasMany
    {
        return $this->recipients()->whereNotNull('read_at');
    }

    public function unreadRecipients(): HasMany
    {
        return $this->recipients()->whereNull('read_at');
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_NORMAL => 'عادی',
            self::PRIORITY_IMPORTANT => 'مهم',
            self::PRIORITY_URGENT => 'بسیار فوری / اضطراری',
            default => 'مهم',
        };
    }

    public function priorityBadgeClasses(): string
    {
        return match ($this->priority) {
            self::PRIORITY_NORMAL => 'bg-blue-50 text-blue-700 border-blue-200',
            self::PRIORITY_IMPORTANT => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PRIORITY_URGENT => 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }

    public function priorityIcon(): string
    {
        return match ($this->priority) {
            self::PRIORITY_NORMAL => 'bi-info-circle',
            self::PRIORITY_IMPORTANT => 'bi-exclamation-triangle',
            self::PRIORITY_URGENT => 'bi-shield-exclamation',
            default => 'bi-exclamation-triangle',
        };
    }
}
