<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\ManagerNotification;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-notifications'), 403);
    }

    #[On('manager-notifications-updated')]
    public function refreshBell(): void
    {
        // شمارنده و پیش‌نمایش در render بازخوانی می‌شوند.
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = ManagerNotification::query()
            ->forManager(auth()->id())
            ->findOrFail($notificationId);

        $notification->markAsRead();

        $this->dispatch('manager-notifications-updated');
    }

    public function markAllAsRead(): void
    {
        ManagerNotification::query()
            ->forManager(auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        $this->dispatch('manager-notifications-updated');
    }

    public function openNotificationCenter(): void
    {
        $this->dispatch('open-dashboard-section', section: 'notifications-center');
    }

    public function render()
    {
        $unreadCount = ManagerNotification::query()
            ->forManager(auth()->id())
            ->unread()
            ->count();

        $recentNotifications = ManagerNotification::query()
            ->forManager(auth()->id())
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.admin.notifications.notification-bell', [
            'unreadCount' => $unreadCount,
            'recentNotifications' => $recentNotifications,
        ]);
    }
}
