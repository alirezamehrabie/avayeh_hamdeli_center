<?php

namespace App\Livewire\SocialWorkers;

use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Services\Announcements\AcknowledgeMandatoryAnnouncementService;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.social-worker')]
class AnnouncementsInbox extends Component
{
    use InteractsWithNotificationModal;
    use WithPagination;

    public string $activeSection = 'announcements';

    public string $search = '';

    public string $statusFilter = 'all'; // all, unread, read

    public string $priorityFilter = 'all'; // all, urgent, important, normal

    public ?int $selectedRecipientId = null;

    public bool $showDetailModal = false;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);

        $this->ensureBroadcastAnnouncementsReceived();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Lazily ensure announcements targeted to all social workers have a recipient row for this user.
     */
    protected function ensureBroadcastAnnouncementsReceived(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $missingAnnouncements = MandatoryAnnouncement::query()
            ->where('target_type', MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS)
            ->whereDoesntHave('recipients', fn (Builder $q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($missingAnnouncements as $announcement) {
            MandatoryAnnouncementRecipient::query()->firstOrCreate([
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ], [
                'social_worker_id' => $user->social_worker_id,
            ]);
        }
    }

    public function viewDetails(int $recipientId): void
    {
        $recipient = MandatoryAnnouncementRecipient::query()
            ->where('id', $recipientId)
            ->where('user_id', auth()->id())
            ->with(['announcement.creator'])
            ->firstOrFail();

        $this->selectedRecipientId = $recipient->id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedRecipientId = null;
    }

    public function acknowledgeAnnouncement(int $recipientId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $recipient = MandatoryAnnouncementRecipient::query()
            ->where('id', $recipientId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $recipient->isRead()) {
            app(AcknowledgeMandatoryAnnouncementService::class)->acknowledge(
                announcement: $recipient->announcement_id,
                user: $user,
                ip: request()->ip(),
                userAgent: request()->userAgent()
            );

            $this->dispatch('announcement-acknowledged', id: $recipient->announcement_id);
            $this->dispatch('check-mandatory-announcements');

            $this->openNotificationModal([
                'type' => 'success',
                'title' => 'تأیید دریافت ابلاغیه',
                'message' => 'مطالعه و تأیید این ابلاغیه با موفقیت به همراه زمان دقیق در سوابق سیستم ثبت گردید.',
            ]);
        }
    }

    public function render()
    {
        $user = auth()->user();

        $baseQuery = MandatoryAnnouncementRecipient::query()
            ->where('user_id', $user->id)
            ->whereHas('announcement');

        // KPI Counts
        $totalCount = (clone $baseQuery)->count();
        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();
        $readCount = (clone $baseQuery)->whereNotNull('read_at')->count();
        $urgentCount = (clone $baseQuery)->whereHas('announcement', fn ($q) => $q->where('priority', MandatoryAnnouncement::PRIORITY_URGENT))->count();

        // Filtered Query
        $recipientsQuery = (clone $baseQuery)
            ->with(['announcement.creator'])
            ->when($this->statusFilter === 'unread', fn (Builder $q) => $q->whereNull('read_at'))
            ->when($this->statusFilter === 'read', fn (Builder $q) => $q->whereNotNull('read_at'))
            ->when(
                in_array($this->priorityFilter, [
                    MandatoryAnnouncement::PRIORITY_NORMAL,
                    MandatoryAnnouncement::PRIORITY_IMPORTANT,
                    MandatoryAnnouncement::PRIORITY_URGENT,
                ], true),
                fn (Builder $q) => $q->whereHas('announcement', fn ($annQuery) => $annQuery->where('priority', $this->priorityFilter))
            )
            ->when(filled($this->search), function (Builder $q) {
                $term = trim($this->search);
                $q->whereHas('announcement', function (Builder $annQuery) use ($term) {
                    $annQuery->where('title', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%");
                });
            })
            ->latest('id');

        $recipients = $recipientsQuery->paginate(8);

        $selectedRecipient = $this->selectedRecipientId
            ? MandatoryAnnouncementRecipient::query()
                ->where('id', $this->selectedRecipientId)
                ->where('user_id', $user->id)
                ->with(['announcement.creator'])
                ->first()
            : null;

        return view('livewire.social-workers.announcements-inbox', [
            'recipients' => $recipients,
            'selectedRecipient' => $selectedRecipient,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'urgentCount' => $urgentCount,
        ]);
    }
}
