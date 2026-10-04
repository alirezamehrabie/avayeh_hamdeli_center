<?php

namespace App\Livewire\People;

use App\Models\Person;
use App\Queries\People\PeopleIndexSearchQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DeletedPeople extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public string $search = '';

    public string $searchField = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);
    }

    public function updatingSearch(): void
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
        $this->resetPage();
    }

    public function refreshData(): void
    {
        $this->resetPage();
        $this->dispatch('people-block-list-toast', message: 'لیست مددجویان غیرفعال به‌روزرسانی شد.');
    }

    public function goToPeopleList(): void
    {
        $this->dispatch('open-dashboard-section', section: 'people-list');
    }

    public function getPeopleProperty()
    {
        $query = Person::onlyTrashed()
            ->with(['guardian' => fn ($query) => $query->withTrashed()]);

        $search = trim($this->search);

        if ($search !== '') {
            $searchQuery = new PeopleIndexSearchQuery;

            if ($searchQuery->needsMoreInput($search, $this->searchField)) {
                return $query->whereRaw('1 = 0')->paginate(20);
            }

            $searchQuery->applyTo($query, $search, $this->searchField);
        }

        return $query
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);
    }

    public function searchNeedsMoreInput(?string $search = null): bool
    {
        $search = trim($search ?? $this->search);

        if ($search === '') {
            return false;
        }

        return (new PeopleIndexSearchQuery)->needsMoreInput($search, $this->searchField);
    }

    public function formatJalaliDate(?\DateTimeInterface $date): string
    {
        if (! $date) {
            return '-';
        }

        try {
            return \App\Helpers\Morilog\Jalalian::fromDateTime($date)->format('Y/m/d H:i');
        } catch (\Throwable) {
            return $date->format('Y/m/d H:i');
        }
    }

    public function restoreSupervision(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $person = Person::onlyTrashed()->findOrFail($personId);
        $person->restoreSupervision();
        $this->resetPage();

        $personName = $person->full_name ? " ({$person->full_name})" : '';
        session()->flash('success', "نظارت مددجو{$personName} با همان کد قبلی با موفقیت بازیابی شد.");
        $this->dispatch('people-block-list-toast', message: 'نظارت مددجو با موفقیت بازیابی شد.');
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        return view('livewire.people.deleted-people', [
            'totalDeletedPeople' => Person::onlyTrashed()->count(),
            'searchFieldLabels' => PeopleIndexSearchQuery::$fieldLabels,
        ]);
    }
}
