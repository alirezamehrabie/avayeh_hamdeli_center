<?php

namespace App\Livewire\SocialWorkers;

use Livewire\Component;
use App\Models\SocialWorker;

class IndexSocialWorkers extends Component
{
    public function render()
    {
        $socialWorkers = SocialWorker::paginate(10);
        return view('livewire.social-workers.index-social-workers', compact('socialWorkers'))
            ->extends('layouts.app')
            ->section('content');
    }
}
