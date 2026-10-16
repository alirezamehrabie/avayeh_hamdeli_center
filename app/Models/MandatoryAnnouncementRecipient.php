<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandatoryAnnouncementRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'social_worker_id',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(MandatoryAnnouncement::class, 'announcement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function socialWorker(): BelongsTo
    {
        return $this->belongsTo(SocialWorker::class, 'social_worker_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser(Builder $query, int|User $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereHas('announcement', function (Builder $announcementQuery): void {
            $announcementQuery->where('is_blocking', true);
        });
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(?string $ip = null, ?string $userAgent = null): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill([
            'read_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ])->save();
    }
}
