<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use App\Models\SocialWorker;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class DeletedSocialWorkers extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public function getDeletedSocialWorkersProperty()
    {
        return SocialWorker::withoutGlobalScope('active')
            ->onlyTrashed()
            ->where('is_active', false)
            ->orderBy('deleted_at', 'desc')
            ->paginate(50);
    }

    public function restoreSocialWorker(int $socialWorkerId): void
    {
        $socialWorker = $this->findDeletedSocialWorker($socialWorkerId);
        $socialWorker->reactivate();
        $this->resetPage();

        session()->flash('success', 'مددکار با همان کد قبلی به لیست اصلی بازگردانده شد.');
    }

    public function cancelDelete(int $socialWorkerId): void
    {
        $socialWorker = $this->findDeletedSocialWorker($socialWorkerId);
        $socialWorker->reactivate();
        $this->resetPage();

        session()->flash('success', 'حذف مددکار لغو شد و مددکار به لیست اصلی بازگشت.');
    }

    private function findDeletedSocialWorker(int $socialWorkerId): SocialWorker
    {
        return SocialWorker::withoutGlobalScope('active')
            ->onlyTrashed()
            ->where('is_active', false)
            ->findOrFail($socialWorkerId);
    }

    public function render()
    {
        return view('livewire.social-workers.deleted-social-workers');
    }
}
