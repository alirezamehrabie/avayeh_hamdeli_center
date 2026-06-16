<?php

namespace App\Livewire\Guardians;

use App\Models\Guardian;
use App\Models\QrIdentity;
use App\Services\QrIdentityService;
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
    public bool $showDeleteModal = false;
    public bool $showQrModal = false;
    public string $householdStatsTab = 'household_size';
    public string $deletionReason = '';
    public ?int $expandedHouseholdSize = null;
    public ?int $expandedCoverageCount = null;
    public ?int $deletingGuardianId = null;
    public ?int $qrGuardianId = null;
    public ?string $issuedQrToken = null;

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

    public function openQrModal(int $guardianId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $guardian = Guardian::query()->findOrFail($guardianId);
        $qrIdentityService = app(QrIdentityService::class);
        $qrIdentityService->ensureActiveFor($guardian, auth()->id());

        $this->qrGuardianId = $guardian->id;
        $this->issuedQrToken = null;
        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrGuardianId = null;
        $this->issuedQrToken = null;
    }

    public function reissueQrCode(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $guardian = Guardian::query()->findOrFail($this->qrGuardianId);
        $qrIdentityService = app(QrIdentityService::class);
        $issued = $qrIdentityService->replaceFor($guardian, auth()->id());

        $this->issuedQrToken = $issued['token'];
        session()->flash('success', 'QR سرپرست با موفقیت دوباره صادر شد.');
    }

    public function revokeQrCode(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $identity = $this->selectedQrIdentity;

        if ($identity) {
            $qrIdentityService = app(QrIdentityService::class);
            $qrIdentityService->revoke($identity, auth()->id());
        }

        $this->issuedQrToken = null;
        session()->flash('success', 'QR سرپرست با موفقیت ابطال شد.');
    }

    public function getSelectedQrIdentityProperty(): ?QrIdentity
    {
        if (! $this->qrGuardianId) {
            return null;
        }

        return QrIdentity::query()
            ->where('subject_type', QrIdentity::SUBJECT_GUARDIAN)
            ->where('subject_id', $this->qrGuardianId)
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public function getQrGuardianProperty(): ?Guardian
    {
        return $this->qrGuardianId ? Guardian::query()->find($this->qrGuardianId) : null;
    }

    public function openDeleteModal(int $guardianId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->deletingGuardianId = Guardian::query()->findOrFail($guardianId)->id;
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

        $guardian = Guardian::query()->findOrFail($this->deletingGuardianId);
        $guardianName = trim($guardian->first_name . ' ' . $guardian->last_name);

        $guardian->softDeleteFamily($validated['deletionReason']);

        if ($this->expandedGuardianId === $guardian->id) {
            $this->expandedGuardianId = null;
        }

        $this->closeDeleteModal();
        $this->resetPage();

        session()->flash('success', 'خانوار ' . ($guardianName !== '' ? $guardianName : 'انتخاب‌شده') . ' با همه مددجویان مرتبط به بلاک لیست منتقل شد.');
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

        return Guardian::with([
            'socialWorker',
            'occupation',
            'insuranceType',
            'vehicleType',
            'residence.residenceStatus',
            'residence.district',
            'people.familyStatus.guardianRelationType',
            'people.harmTypes:id,title',
        ])
            ->withCount('people')
            ->find($this->selectedGuardianId);
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        return view('livewire.guardians.index-guardians', [
            'totalGuardians' => Guardian::count(),
            'totalCenterMembers' => (int) Guardian::query()->sum('children_in_house'),
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
            'deletingGuardian' => $this->deletingGuardianId
                ? Guardian::query()
                    ->withCount('people')
                    ->find($this->deletingGuardianId)
                : null,
        ]);
    }

    private function refreshGuardianStats(string $message): void
    {
        Guardian::refreshAllChildrenInHouse();
        $this->dispatch('guardian-stats-refreshed', message: $message);
    }
}
