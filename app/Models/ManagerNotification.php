<?php

namespace App\Models;

use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ManagerNotification extends Model
{
    protected $fillable = [
        'manager_id',
        'event_key',
        'title',
        'message',
        'actor_id',
        'actor_name',
        'actor_role',
        'subject_type',
        'subject_id',
        'context',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'manager_id' => 'integer',
            'actor_id' => 'integer',
            'context' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForManager(Builder $query, int $managerId): Builder
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function getEventLabelAttribute(): string
    {
        return NotificationEventRegistry::label($this->event_key);
    }

    public function getActorRoleLabelAttribute(): ?string
    {
        if (blank($this->actor_role)) {
            return null;
        }

        return User::roleDefinitions()[$this->actor_role]['label'] ?? $this->actor_role;
    }
}
