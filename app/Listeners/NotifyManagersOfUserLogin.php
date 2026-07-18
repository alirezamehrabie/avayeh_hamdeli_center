<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Notifications\ManagerNotificationDispatcher;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Auth\Events\Login;

class NotifyManagersOfUserLogin
{
    public function __construct(
        protected ManagerNotificationDispatcher $dispatcher
    ) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        // ورود خود مدیران اعلان تولید نمی‌کند.
        if ($user->isManager()) {
            return;
        }

        $roleLabel = User::roleDefinitions()[$user->access_level]['label'] ?? $user->access_level;
        $displayName = $user->full_name ?: $user->name;

        $this->dispatcher->dispatch(
            eventKey: NotificationEventRegistry::EVENT_USER_LOGIN,
            actor: $user,
            title: 'ورود کاربر به سیستم',
            message: sprintf('%s (%s) وارد سیستم شد.', $displayName, $roleLabel),
            subject: $user,
            context: [
                'access_level' => $user->access_level,
            ]
        );
    }
}
