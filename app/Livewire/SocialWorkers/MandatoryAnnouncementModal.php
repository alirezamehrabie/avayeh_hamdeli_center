<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\Jalalian;
use App\Models\MandatoryAnnouncement;
use App\Queries\Announcements\PendingMandatoryAnnouncementQuery;
use App\Services\Announcements\AcknowledgeMandatoryAnnouncementService;
use Livewire\Attributes\On;
use Livewire\Component;

class MandatoryAnnouncementModal extends Component
{
    public ?int $pendingRecipientId = null;

    public ?int $announcementId = null;

    public string $title = '';

    public string $body = '';

    public string $priority = MandatoryAnnouncement::PRIORITY_IMPORTANT;

    public int $minReadingSeconds = 0;

    public string $createdAtJalali = '';

    public string $creatorName = '';

    public bool $isBlocking = true;

    public bool $hasPending = false;

    public bool $isAcknowledging = false;

    public function mount(): void
    {
        $this->loadPendingAnnouncement();
    }

    public function loadPendingAnnouncement(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->resetState();

            return;
        }

        $recipient = app(PendingMandatoryAnnouncementQuery::class)->getFirstBlockingForUser($user);

        if (! $recipient || ! $recipient->announcement) {
            $this->resetState();

            return;
        }

        $announcement = $recipient->announcement;

        $this->pendingRecipientId = $recipient->id;
        $this->announcementId = $announcement->id;
        $this->title = $announcement->title;
        $this->body = $announcement->body;
        $this->priority = $announcement->priority;
        $this->minReadingSeconds = (int) $announcement->min_reading_seconds;
        $this->isBlocking = (bool) $announcement->is_blocking;
        $this->creatorName = $announcement->creator?->full_name
            ?: trim(($announcement->creator?->first_name ?? '').' '.($announcement->creator?->last_name ?? ''))
            ?: 'مدیریت مرکز آوای همدلی';

        try {
            $this->createdAtJalali = Jalalian::fromCarbon($announcement->created_at)->format('Y/m/d - H:i');
        } catch (\Throwable $e) {
            $this->createdAtJalali = (string) $announcement->created_at;
        }

        $this->hasPending = true;
    }

    public function acknowledge(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->announcementId) {
            return;
        }

        $this->isAcknowledging = true;

        $service = app(AcknowledgeMandatoryAnnouncementService::class);
        $service->acknowledge(
            announcement: $this->announcementId,
            user: $user,
            ip: request()->ip(),
            userAgent: request()->userAgent()
        );

        $this->dispatch('announcement-acknowledged', id: $this->announcementId);

        // Check if there are other pending mandatory announcements in the queue
        $this->loadPendingAnnouncement();

        $this->isAcknowledging = false;
    }

    #[On('check-mandatory-announcements')]
    public function refreshPending(): void
    {
        $this->loadPendingAnnouncement();
    }

    protected function resetState(): void
    {
        $this->pendingRecipientId = null;
        $this->announcementId = null;
        $this->title = '';
        $this->body = '';
        $this->priority = MandatoryAnnouncement::PRIORITY_IMPORTANT;
        $this->minReadingSeconds = 0;
        $this->createdAtJalali = '';
        $this->creatorName = '';
        $this->isBlocking = false;
        $this->hasPending = false;
        $this->isAcknowledging = false;
    }

    public function render()
    {
        return view('livewire.social-workers.mandatory-announcement-modal');
    }
}
