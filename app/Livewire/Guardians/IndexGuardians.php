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
    public ?int $expandedGuardianId = null;
    public string $search = '';
    public string $searchField = 'all';
    public ?int $selectedGuardianId = null;
    public bool $showHouseholdModal = false;

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
        ]);
    }
}
