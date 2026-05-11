<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DeletedPeople extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);
    }

    public function getPeopleProperty()
    {
        return Person::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);
    }

    public function restoreSupervision(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $person = Person::onlyTrashed()->findOrFail($personId);
        $person->restoreSupervision();
        $this->resetPage();

        session()->flash('success', 'نظارت مددجو با همان کد قبلی بازیابی شد.');
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        return view('livewire.people.deleted-people');
    }
}
