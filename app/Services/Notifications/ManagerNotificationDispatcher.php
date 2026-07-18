<?php

namespace App\Services\Notifications;

use App\Models\ManagerNotification;
use App\Models\ManagerNotificationPreference;
use App\Models\User;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Creates manager notifications for a system event, honoring each manager's
 * saved preferences (enabled + role/user targeting). Failures are logged and
 * never bubble up into the business action that triggered the event.
 */
class ManagerNotificationDispatcher
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function dispatch(
        string $eventKey,
        ?User $actor,
        string $title,
        string $message,
        ?Model $subject = null,
        array $context = []
    ): void {
        if (! NotificationEventRegistry::has($eventKey)) {
            return;
        }

        try {
            $preferences = ManagerNotificationPreference::query()
                ->enabledFor($eventKey)
                ->whereHas('manager', function ($query): void {
                    $query->where('access_level', User::ACCESS_LEVEL_MANAGER);
                })
                ->get();

            if ($preferences->isEmpty()) {
                return;
            }

            $now = now();
            $rows = [];

            foreach ($preferences as $preference) {
                if ($actor && ! $preference->matchesActor($actor)) {
                    continue;
                }

                // Prevent duplicate bursts: skip when the exact same event for
                // the same actor/subject reached this manager moments ago.
                $recentDuplicate = ManagerNotification::query()
                    ->where('manager_id', $preference->manager_id)
                    ->where('event_key', $eventKey)
                    ->when($actor, fn ($query) => $query->where('actor_id', $actor->id))
                    ->when($subject, fn ($query) => $query
                        ->where('subject_type', $subject->getMorphClass())
                        ->where('subject_id', $subject->getKey()))
                    ->where('created_at', '>=', $now->copy()->subMinute())
                    ->exists();

                if ($recentDuplicate) {
                    continue;
                }

                $rows[] = [
                    'manager_id' => $preference->manager_id,
                    'event_key' => $eventKey,
                    'title' => $title,
                    'message' => $message,
                    'actor_id' => $actor?->id,
                    'actor_name' => $actor?->full_name ?: $actor?->name,
                    'actor_role' => $actor?->access_level,
                    'subject_type' => $subject?->getMorphClass(),
                    'subject_id' => $subject?->getKey(),
                    'context' => $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                ManagerNotification::query()->insert($rows);
            }
        } catch (\Throwable $exception) {
            Log::error('manager_notifications.dispatch_failed', [
                'event_key' => $eventKey,
                'actor_id' => $actor?->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
