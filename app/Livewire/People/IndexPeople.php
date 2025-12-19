<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SocialWorker;

class IndexPeople extends Component
{
    use WithPagination;

    public function render()
    {
        // استفاده از Eager Loading برای رابطه مددکار
        $people = Person::with(['socialWorker'])
            ->latest()
            ->paginate(15);

        return view('livewire.people.index-people', [
            'people' => $people
        ])->extends('layouts.app');
    }
}
