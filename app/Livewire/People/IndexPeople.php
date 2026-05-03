<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPeople extends Component
{
    use WithPagination;

    public $search = '';
    public string $searchField = 'all';
    public bool $embedded = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->resetPage();
    }

    public function getPeopleProperty()
    {
        $query = Person::orderBy('created_at', 'desc');

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            match ($this->searchField) {
                'person_code' => $query->where('person_code', 'LIKE', "%{$search}%"),
                'full_name' => $query->whereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]),
                'first_name' => $query->where('first_name', 'LIKE', "%{$search}%"),
                'last_name' => $query->where('last_name', 'LIKE', "%{$search}%"),
                'national_id' => $query->where('national_id', 'LIKE', "%{$search}%"),
                default => $query->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('national_id', 'LIKE', "%{$search}%")
                        ->orWhere('person_code', 'LIKE', "%{$search}%")
                        ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
                }),
            };
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

    public function quickEditPerson(Person $person)
    {
        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'people-fast-create', id: $person->id);
            return;
        }

        return redirect()->route('people.fast-create', ['person' => $person->id]);
    }

    public function render()
    {
        return view('livewire.people.index-people');
    }
}
