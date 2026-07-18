<?php

namespace App\Observers;

use App\Models\Service;
use App\Models\User;
use App\Services\Notifications\ManagerNotificationDispatcher;
use App\Support\Notifications\NotificationEventRegistry;

class ServiceNotificationObserver
{
    /**
     * Notifications must only be created after the wrapping transaction (e.g.
     * the misc-service creation batch) has actually committed.
     */
    public bool $afterCommit = true;

    public function __construct(
        protected ManagerNotificationDispatcher $dispatcher
    ) {}

    public function created(Service $service): void
    {
        $this->dispatch(
            $service,
            NotificationEventRegistry::EVENT_SERVICE_CREATED,
            'ثبت خدمت جدید',
            sprintf('خدمت «%s» (کد %s) ثبت شد.', $service->name, $service->code)
        );
    }

    public function updated(Service $service): void
    {
        $this->dispatch(
            $service,
            NotificationEventRegistry::EVENT_SERVICE_UPDATED,
            'ویرایش خدمت',
            sprintf('خدمت «%s» (کد %s) به‌روزرسانی شد.', $service->name, $service->code)
        );
    }

    public function deleted(Service $service): void
    {
        if ($service->isForceDeleting()) {
            return;
        }

        $this->dispatch(
            $service,
            NotificationEventRegistry::EVENT_SERVICE_ARCHIVED,
            'غیرفعال‌سازی خدمت',
            sprintf('خدمت «%s» (کد %s) آرشیو شد.', $service->name, $service->code)
        );
    }

    public function restored(Service $service): void
    {
        $this->dispatch(
            $service,
            NotificationEventRegistry::EVENT_SERVICE_RESTORED,
            'فعال‌سازی مجدد خدمت',
            sprintf('خدمت «%s» (کد %s) بازگردانی شد.', $service->name, $service->code)
        );
    }

    protected function dispatch(Service $service, string $eventKey, string $title, string $message): void
    {
        $actor = auth()->user();

        if ($actor instanceof User) {
            $roleLabel = User::roleDefinitions()[$actor->access_level]['label'] ?? $actor->access_level;
            $displayName = $actor->full_name ?: $actor->name;
            $message .= sprintf(' توسط: %s (%s)', $displayName, $roleLabel);
        }

        $this->dispatcher->dispatch(
            eventKey: $eventKey,
            actor: $actor instanceof User ? $actor : null,
            title: $title,
            message: $message,
            subject: $service,
            context: [
                'service_code' => $service->code,
                'service_name' => $service->name,
                'service_status' => $service->status,
            ]
        );
    }
}
