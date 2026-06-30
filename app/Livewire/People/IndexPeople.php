<?php

namespace App\Livewire\People;

use App\Models\Person;
use App\Queries\People\DeletingPersonDetailsQuery;
use App\Queries\People\PeopleIndexSearchQuery;
use App\Queries\People\SelectedPersonDetailsQuery;
use App\Queries\People\TrackingPersonDetailsQuery;
use App\Services\People\SoftDeletePerson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPeople extends Component
{
    use WithPagination;

    public $search = '';

    public string $searchField = 'all';

    public bool $embedded = false;

    public ?int $selectedPersonId = null;

    public ?int $trackingPersonId = null;

    public bool $showPersonModal = false;

    public bool $showTrackingModal = false;

    public bool $showDeleteModal = false;

    public string $deletionReason = '';

    public ?int $deletingPersonId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-people'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->searchField = 'all';
        $this->resetPage();
    }

    public function getPeopleProperty(): LengthAwarePaginator|Paginator
    {
        return app(PeopleIndexSearchQuery::class)->paginate(
            search: $this->search,
            searchField: $this->searchField,
        );
    }

    public function normalizedSearchTerm(): string
    {
        return app(PeopleIndexSearchQuery::class)->normalizeSearchTerm((string) $this->search);
    }

    public function searchNeedsMoreInput(?string $search = null): bool
    {
        $search ??= $this->normalizedSearchTerm();

        return app(PeopleIndexSearchQuery::class)->needsMoreInput($search, $this->searchField);
    }

    public function editPerson(Person $person)
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'person-edit', id: $person->id);

            return;
        }

        return redirect()->route('people.form', [
            'mode' => 'edit',
            'person' => $person->id,
        ]);
    }

    public function quickEditPerson(Person $person)
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'people-fast-create', id: $person->id);

            return;
        }

        return redirect()->route('people.fast-create', ['person' => $person->id]);
    }

    public function showPersonInfo(int $personId): void
    {
        $this->selectedPersonId = $personId;
        $this->showPersonModal = true;
    }

    public function closePersonModal(): void
    {
        $this->showPersonModal = false;
        $this->selectedPersonId = null;
    }

    public function showRegistrationTracking(int $personId): void
    {
        $this->trackingPersonId = $personId;
        $this->showTrackingModal = true;
    }

    public function closeTrackingModal(): void
    {
        $this->showTrackingModal = false;
        $this->trackingPersonId = null;
    }

    public function getSelectedPersonProperty(): ?Person
    {
        return app(SelectedPersonDetailsQuery::class)->find($this->selectedPersonId);
    }

    public function getTrackingPersonProperty(): ?Person
    {
        return app(TrackingPersonDetailsQuery::class)->find($this->trackingPersonId);
    }

    public function deletePerson(Person $person): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        app(SoftDeletePerson::class)->handle($person, $this->deletionReason);
        $this->resetPage();

        session()->flash('success', 'مددجو با موفقیت به بلاک لیست منتقل شد.');
    }

    public function openDeleteModal(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $this->deletingPersonId = Person::query()->findOrFail($personId)->id;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPersonId = null;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
    }

    public function confirmDeletePerson(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $validated = $this->validate([
            'deletionReason' => ['required', 'string', 'max:1000'],
        ], [
            'deletionReason.required' => 'ثبت علت حذف الزامی است.',
        ]);

        $person = Person::query()->findOrFail($this->deletingPersonId);

        $this->deletionReason = $validated['deletionReason'];
        $this->deletePerson($person);
        $this->closeDeleteModal();
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-people'), 403);

        return view('livewire.people.index-people', [
            'deletingPerson' => app(DeletingPersonDetailsQuery::class)->find($this->deletingPersonId),
        ]);
    }
}
