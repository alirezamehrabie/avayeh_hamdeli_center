<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Person;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPeople extends Component
{
    use WithPagination;

    public $search = '';
    public bool $embedded = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getPeopleProperty()
    {
        $query = Person::orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('national_id', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('person_code', 'LIKE', '%' . $this->search . '%');
            });
        }

        return $query->paginate(20);
    }

    public function editPerson(Person $person)
    {
        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'person-edit', id: $person->id);
            return;
        }

        return redirect()->route('people.form', [
            'mode' => 'edit',
            'person' => $person->id
        ]);
    }

    public function render()
    {
        return view('livewire.people.index-people');
    }
}
