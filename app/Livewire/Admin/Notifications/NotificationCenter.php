<?php

namespace App\Livewire\Admin\Notifications;

use App\Helpers\Morilog\Jalalian;
use App\Models\ManagerNotification;
use App\Support\Notifications\NotificationEventRegistry;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $eventFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 15;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-notifications'), 403);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'eventFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
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

    public function deleteNotification(int $notificationId): void
    {
        ManagerNotification::query()
            ->forManager(auth()->id())
            ->findOrFail($notificationId)
            ->delete();

        $this->dispatch('manager-notifications-updated');
    }

    public function deleteReadNotifications(): void
    {
        ManagerNotification::query()
            ->forManager(auth()->id())
            ->read()
            ->delete();

        $this->resetPage();
        $this->dispatch('manager-notifications-updated');
    }

    #[On('manager-notifications-updated')]
    public function refreshList(): void
    {
        // فهرست در render بازخوانی می‌شود.
    }

    protected function jalaliToCarbon(string $jalaliDate): ?\Carbon\Carbon
    {
        $jalaliDate = trim($jalaliDate);

        if ($jalaliDate === '') {
            return null;
        }

        try {
            // Jalalian::toCarbon() نسخه داخلی Carbon را برمی‌گرداند؛ به Carbon استاندارد تبدیل می‌کنیم.
            return \Carbon\Carbon::parse(
                Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon()->format('Y-m-d')
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        $query = ManagerNotification::query()
            ->forManager(auth()->id())
            ->with('actor:id,first_name,last_name,name,access_level')
            ->latest();

        if ($this->statusFilter === 'unread') {
            $query->unread();
        } elseif ($this->statusFilter === 'read') {
            $query->read();
        }

        if ($this->eventFilter !== '' && NotificationEventRegistry::has($this->eventFilter)) {
            $query->where('event_key', $this->eventFilter);
        }

        if ($from = $this->jalaliToCarbon($this->dateFrom)) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->jalaliToCarbon($this->dateTo)) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $unreadCount = ManagerNotification::query()
            ->forManager(auth()->id())
            ->unread()
            ->count();

        return view('livewire.admin.notifications.notification-center', [
            'notifications' => $query->paginate($this->perPage),
            'unreadCount' => $unreadCount,
            'eventOptions' => NotificationEventRegistry::options(),
        ]);
    }
}
