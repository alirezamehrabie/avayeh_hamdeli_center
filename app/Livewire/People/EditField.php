<?php

namespace App\Livewire\People;

use App\Models\NeedLevelType;
use App\Models\NeedsLevel;
use App\Models\Person;
use App\Queries\People\PeopleIndexSearchQuery;
use Illuminate\Support\Collection;
use Livewire\Component;

class EditField extends Component
{
    public bool $embedded = false;

    public string $search = '';

    public ?int $selectedPersonId = null;

    public ?string $needLevelId = null;

    public ?string $flashMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('full-access'), 403);
    }

    public function updatedSearch(): void
    {
        $this->flashMessage = null;
    }

    public function selectPerson(int $personId): void
    {
        $person = Person::query()->find($personId);
        abort_if(! $person, 404);

        $this->selectedPersonId = $person->id;
        $this->needLevelId = $person->needsLevel?->need_level_id !== null
            ? (string) $person->needsLevel->need_level_id
            : null;
        $this->flashMessage = null;
    }

    public function resetSelection(): void
    {
        $this->selectedPersonId = null;
        $this->needLevelId = null;
        $this->search = '';
        $this->flashMessage = null;
    }

    public function save(): void
    {
        $person = $this->selectedPerson();
        abort_if(! $person, 404);

        $validated = $this->validate([
            'needLevelId' => ['required', 'integer', 'exists:need_level_types,id'],
        ], [
            'needLevelId.required' => 'انتخاب سطح نیاز الزامی است.',
            'needLevelId.exists' => 'سطح نیاز انتخابی نامعتبر است.',
        ]);

        NeedsLevel::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['need_level_id' => (int) $validated['needLevelId']],
        );

        $this->flashMessage = 'سطح نیاز برای «'.$person->full_name.'» با موفقیت ذخیره شد.';
    }

    private function selectedPerson(): ?Person
    {
        return $this->selectedPersonId
            ? Person::query()->find($this->selectedPersonId)
            : null;
    }

    /**
     * @return Collection<int, Person>
     */
    private function searchResults(): Collection
    {
        $peopleSearch = app(PeopleIndexSearchQuery::class);
        $search = $peopleSearch->normalizeSearchTerm(trim($this->search));

        if ($search === '' || $peopleSearch->needsMoreInput($search, 'all')) {
            return collect();
        }

        $query = Person::query()
            ->select(['id', 'person_code', 'first_name', 'last_name', 'full_name', 'national_id'])
            ->latest('id');

        $peopleSearch->applyTo($query, $search);

        return $query->limit(10)->get();
    }

    public function render()
    {
        return view('livewire.people.edit-field', [
            'person' => $this->selectedPerson(),
            'searchResults' => $this->selectedPersonId === null ? $this->searchResults() : collect(),
            'levels' => NeedLevelType::query()->orderByDesc('severity_order')->get(),
        ]);
    }
}
