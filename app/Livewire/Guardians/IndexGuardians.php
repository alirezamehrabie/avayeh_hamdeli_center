<?php

namespace App\Livewire\Guardians;

use App\Models\Guardian;
use App\Queries\Guardians\DeletingGuardianDetailsQuery;
use App\Queries\Guardians\ExpandedGuardianPeopleQuery;
use App\Queries\Guardians\GuardianHouseholdStatsQuery;
use App\Queries\Guardians\GuardianIndexSearchQuery;
use App\Queries\Guardians\GuardianListSummaryQuery;
use App\Queries\Guardians\SelectedGuardianDetailsQuery;
use App\Services\Guardians\RefreshGuardianStats;
use App\Services\Guardians\SoftDeleteGuardianFamily;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexGuardians extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public ?int $expandedGuardianId = null;

    public string $search = '';

    public string $searchField = 'all';

    public ?int $selectedGuardianId = null;

    public bool $showHouseholdModal = false;

    public bool $showHouseholdSizeModal = false;

    public bool $showDeleteModal = false;

    public string $householdStatsTab = 'household_size';

    public string $deletionReason = '';

    public ?int $expandedHouseholdSize = null;

    public ?int $expandedCoverageCount = null;

    public ?int $deletingGuardianId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function updatingSearch(): void
    {
        $this->expandedGuardianId = null;
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->expandedGuardianId = null;
        $this->resetPage();
    }

    public function getGuardiansProperty()
    {
        return app(GuardianIndexSearchQuery::class)->paginate(
            search: $this->search,
            searchField: $this->searchField,
        );
    }

    public function getExpandedGuardianPeopleProperty(): Collection
    {
        return app(ExpandedGuardianPeopleQuery::class)->get($this->expandedGuardianId);
    }

    public function toggleGuardian(int $guardianId): void
    {
        $this->expandedGuardianId = $this->expandedGuardianId === $guardianId ? null : $guardianId;
    }

    public function showHouseholdInfo(int $guardianId): void
    {
        $this->selectedGuardianId = $guardianId;
        $this->showHouseholdModal = true;
    }

    public function editGuardian(int $guardianId): mixed
    {
        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'guardian-edit', id: $guardianId);

            return null;
        }

        return redirect()->route('guardians.edit', ['guardian' => $guardianId]);
    }

    public function openDeleteModal(int $guardianId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->deletingGuardianId = app(DeletingGuardianDetailsQuery::class)->findOrFail($guardianId)->id;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingGuardianId = null;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
    }

    public function deleteGuardianFamily(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $validated = $this->validate([
            'deletionReason' => ['required', 'string', 'max:1000'],
        ], [
            'deletionReason.required' => 'ثبت علت حذف الزامی است.',
        ]);

        $guardian = app(SoftDeleteGuardianFamily::class)->handleById(
            (int) $this->deletingGuardianId,
            $validated['deletionReason']
        );
        $guardianName = trim($guardian->first_name.' '.$guardian->last_name);

        if ($this->expandedGuardianId === $guardian->id) {
            $this->expandedGuardianId = null;
        }

        $this->closeDeleteModal();
        $this->resetPage();

        session()->flash('success', 'خانوار '.($guardianName !== '' ? $guardianName : 'انتخاب‌شده').' با همه مددجویان مرتبط به بلاک لیست منتقل شد.');
    }

    public function goToDeletedGuardians(): mixed
    {
        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'guardians-block-list');

            return null;
        }

        return redirect()->route('guardians.block-list');
    }

    public function closeHouseholdModal(): void
    {
        $this->showHouseholdModal = false;
        $this->selectedGuardianId = null;
    }

    public function showHouseholdSizeDetails(): void
    {
        $this->showHouseholdSizeModal = true;
    }

    public function setHouseholdStatsTab(string $tab): void
    {
        $this->householdStatsTab = $tab;
    }

    public function toggleHouseholdSize(int $householdSize): void
    {
        $this->expandedHouseholdSize = $this->expandedHouseholdSize === $householdSize ? null : $householdSize;
    }

    public function toggleCoverageCount(int $coverageCount): void
    {
        $this->expandedCoverageCount = $this->expandedCoverageCount === $coverageCount ? null : $coverageCount;
    }

    public function closeHouseholdSizeModal(): void
    {
        $this->showHouseholdSizeModal = false;
        $this->expandedHouseholdSize = null;
        $this->expandedCoverageCount = null;
    }

    public function refreshStats(): void
    {
        $this->refreshGuardianStats('آمار سرپرستان به‌روزرسانی شد.');
    }

    #[On('guardians-list-success')]
    public function showSuccessMessage(string $message): void
    {
        session()->flash('success', $message);
        $this->dispatch('guardians-list-toast', message: $message);
    }

    public function getSelectedGuardianProperty(): ?Guardian
    {
        return app(SelectedGuardianDetailsQuery::class)->find($this->selectedGuardianId);
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $householdSizeStats = collect();
        $coverageCountStats = collect();

        if ($this->showHouseholdSizeModal) {
            $householdStats = app(GuardianHouseholdStatsQuery::class);
            $householdSizeStats = $householdStats->byHouseholdSize();
            $coverageCountStats = $householdStats->byCoverageCount();
        }

        $summary = app(GuardianListSummaryQuery::class)->get();

        return view('livewire.guardians.index-guardians', [
            'totalGuardians' => $summary['totalGuardians'],
            'totalCenterMembers' => $summary['totalCenterMembers'],
            'householdSizeStats' => $householdSizeStats,
            'coverageCountStats' => $coverageCountStats,
            'deletingGuardian' => app(DeletingGuardianDetailsQuery::class)->find($this->deletingGuardianId),
        ]);
    }

    private function refreshGuardianStats(string $message): void
    {
        app(RefreshGuardianStats::class)->handle();
        $this->dispatch('guardian-stats-refreshed', message: $message);
    }
}
