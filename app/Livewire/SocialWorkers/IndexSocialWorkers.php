<?php

namespace App\Livewire\SocialWorkers;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\SocialWorker;

class IndexSocialWorkers extends Component
{
    #[AllowDynamicProperties]
    #[Layout('layouts.app')]
    public function render()
    {
        $socialWorkers = SocialWorker::paginate(10);
        return view('livewire.social-workers.index-social-workers', compact('socialWorkers'));
    }
}
