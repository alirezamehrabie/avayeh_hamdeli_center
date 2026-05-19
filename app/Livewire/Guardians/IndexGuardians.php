<?php

namespace App\Livewire\Guardians;

use App\Models\Guardian;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexGuardians extends Component
{
    use WithPagination;

    public bool $embedded = false;
    public bool $hasAutoRefreshedStats = false;
    public ?int $expandedGuardianId = null;
    public string $search = '';
    public string $searchField = 'all';
    public ?int $selectedGuardianId = null;
    public bool $showHouseholdModal = false;
    public bool $showHouseholdSizeModal = false;
    public string $householdStatsTab = 'household_size';
    public ?int $expandedHouseholdSize = null;
    public ?int $expandedCoverageCount = null;

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
        $query = Guardian::withCount('people')
            ->with(['people' => fn ($query) => $query->orderBy('created_at', 'desc')]);

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            match ($this->searchField) {
                'national_code' => $query->where('national_code', 'LIKE', "%{$search}%"),
                'full_name' => $query->whereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]),
                'mobile' => $query->where('guardian_phone_number', 'LIKE', "%{$search}%"),
                default => $query->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('national_code', 'LIKE', "%{$search}%")
                        ->orWhere('guardian_phone_number', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
                }),
            };
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
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

    public function refreshStatsOnLoad(): void
    {
        if ($this->hasAutoRefreshedStats) {
            return;
        }

        $this->hasAutoRefreshedStats = true;
        $this->refreshGuardianStats('آمار سرپرستان به‌روزرسانی شد.');
    }

    public function refreshStats(): void
    {
        $this->refreshGuardianStats('آمار سرپرستان به‌روزرسانی شد.');
    }

    public function getSelectedGuardianProperty(): ?Guardian
    {
        if (!$this->selectedGuardianId) {
            return null;
        }

        return Guardian::with(['socialWorker', 'occupation', 'insuranceType', 'vehicleType', 'residence.residenceStatus', 'residence.district'])
            ->withCount('people')
            ->find($this->selectedGuardianId);
    }

    public function render()
    {
        return view('livewire.guardians.index-guardians', [
            'totalGuardians' => Guardian::count(),
            'householdSizeStats' => Guardian::query()
                ->select(['id', 'national_code', 'first_name', 'last_name', 'children_in_house'])
                ->orderBy('children_in_house')
                ->orderBy('national_code')
                ->get()
                ->groupBy(fn (Guardian $guardian) => (int) ($guardian->children_in_house ?? 0))
                ->map(function ($guardians, $householdSize) {
                    return [
                        'household_size' => (int) $householdSize,
                        'households_count' => $guardians->count(),
                        'national_codes' => $guardians->pluck('national_code')->filter()->values()->all(),
                    ];
                })
                ->values(),
            'coverageCountStats' => Guardian::query()
                ->select(['id', 'national_code', 'first_name', 'last_name', 'children_count'])
                ->orderBy('children_count')
                ->orderBy('national_code')
                ->get()
                ->groupBy(fn (Guardian $guardian) => (int) ($guardian->children_count ?? 0))
                ->map(function ($guardians, $coverageCount) {
                    return [
                        'coverage_count' => (int) $coverageCount,
                        'households_count' => $guardians->count(),
                        'national_codes' => $guardians->pluck('national_code')->filter()->values()->all(),
                    ];
                })
                ->values(),
        ]);
    }

    private function refreshGuardianStats(string $message): void
    {
        Guardian::refreshAllChildrenInHouse();
        $this->dispatch('guardian-stats-refreshed', message: $message);
    }
}
