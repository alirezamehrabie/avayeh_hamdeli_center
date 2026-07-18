<?php

namespace App\Models;

use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerNotificationPreference extends Model
{
    protected $fillable = [
        'manager_id',
        'event_key',
        'enabled',
        'target_type',
        'target_roles',
        'target_user_ids',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'manager_id' => 'integer',
            'enabled' => 'boolean',
            'target_roles' => 'array',
            'target_user_ids' => 'array',
            'metadata' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeEnabledFor(Builder $query, string $eventKey): Builder
    {
        return $query->where('event_key', $eventKey)->where('enabled', true);
    }

    /**
     * Whether this preference matches the given actor (the user who performed
     * the event). Targeting values were validated server-side on save, but the
     * match is still defensive against stale role/user data.
     */
    public function matchesActor(User $actor): bool
    {
        if (! $this->enabled) {
            return false;
        }

        return match ($this->target_type) {
            NotificationEventRegistry::TARGET_ROLES => in_array(
                $actor->access_level,
                array_values((array) $this->target_roles),
                true
            ),
            NotificationEventRegistry::TARGET_USERS => in_array(
                $actor->id,
                array_map('intval', array_values((array) $this->target_user_ids)),
                true
            ),
            default => true,
        };
    }
}
