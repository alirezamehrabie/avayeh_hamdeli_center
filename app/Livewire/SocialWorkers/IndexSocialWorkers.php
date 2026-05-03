<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\SocialWorker;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class IndexSocialWorkers extends Component
{
    public bool $embedded = false;

    public function createSocialWorker(): void
    {
        $this->dispatch('open-dashboard-section', section: 'social-worker-create');
    }

    public function editSocialWorker(SocialWorker $socialWorker): void
    {
        $this->dispatch('open-dashboard-section', section: 'social-worker-edit', id: $socialWorker->id);
    }

    public function render()
    {
        $socialWorkers = SocialWorker::paginate(10);
        return view('livewire.social-workers.index-social-workers', compact('socialWorkers'));
    }
}
