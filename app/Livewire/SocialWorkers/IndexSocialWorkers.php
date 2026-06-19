<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SocialWorker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class IndexSocialWorkers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $searchField = 'all';
    public bool $embedded = false;
    public ?int $expandedSocialWorkerId = null;
    public array $coveredDetailsByWorker = [];
    public array $coveredCountsByWorker = [];
    public array $guardiansByWorker = [];
    public array $visibleGuardianLimitsByWorker = [];
    public array $visibleCoveredDetailLimitsByWorker = [];

    public function updatingSearch(): void
    {
        $this->expandedSocialWorkerId = null;
        $this->guardiansByWorker = [];
        $this->coveredDetailsByWorker = [];
        $this->visibleGuardianLimitsByWorker = [];
        $this->visibleCoveredDetailLimitsByWorker = [];
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->expandedSocialWorkerId = null;
        $this->guardiansByWorker = [];
        $this->coveredDetailsByWorker = [];
        $this->visibleGuardianLimitsByWorker = [];
        $this->visibleCoveredDetailLimitsByWorker = [];
        $this->resetPage();
    }

    public function updatingPaginators(): void
    {
        $this->expandedSocialWorkerId = null;
        $this->guardiansByWorker = [];
        $this->coveredDetailsByWorker = [];
        $this->visibleGuardianLimitsByWorker = [];
        $this->visibleCoveredDetailLimitsByWorker = [];
    }

    public function createSocialWorker(): void
    {
        $this->dispatch('open-dashboard-section', section: 'social-worker-create');
    }

    public function editSocialWorker(SocialWorker $socialWorker): void
    {
        $this->dispatch('open-dashboard-section', section: 'social-worker-edit', id: $socialWorker->id);
    }

    public function deleteSocialWorker(SocialWorker $socialWorker): void
    {
        $socialWorker->deactivate();
        $this->expandedSocialWorkerId = null;
        $this->guardiansByWorker = [];
        $this->coveredDetailsByWorker = [];
        $this->visibleGuardianLimitsByWorker = [];
        $this->visibleCoveredDetailLimitsByWorker = [];
        $this->resetPage();

        session()->flash('success', 'مددکار با موفقیت حذف شد.');
    }

    public function toggleSocialWorker(int $socialWorkerId): void
    {
        if ($this->expandedSocialWorkerId === $socialWorkerId) {
            $this->expandedSocialWorkerId = null;
            return;
        }

        $this->expandedSocialWorkerId = $socialWorkerId;
        $this->visibleGuardianLimitsByWorker[$socialWorkerId] ??= 10;
        $this->visibleCoveredDetailLimitsByWorker[$socialWorkerId] ??= 20;
        $socialWorker = SocialWorker::find($socialWorkerId);

        if (!array_key_exists($socialWorkerId, $this->guardiansByWorker)) {
            $this->guardiansByWorker[$socialWorkerId] = $socialWorker
                ?->guardians()
                ->select([
                    'id',
                    'social_worker_id',
                    'national_code',
                    'first_name',
                    'last_name',
                    'guardian_phone_number',
                ])
                ->withCount('people')
                ->orderBy('id')
                ->get()
                ->map(fn ($guardian) => [
                    'id' => $guardian->id,
                    'national_code' => $guardian->national_code,
                    'first_name' => $guardian->first_name,
                    'last_name' => $guardian->last_name,
                    'guardian_phone_number' => $guardian->guardian_phone_number,
                    'people_count' => $guardian->people_count,
                ])
                ->all() ?? [];
        }

    }

    public function getSocialWorkersProperty(): LengthAwarePaginator|Paginator
    {
        $query = SocialWorker::query()
            ->orderBy('created_at', 'desc');

        $search = $this->normalizedSearchTerm();

        if ($search !== '') {
            if ($this->searchNeedsMoreInput($search)) {
                return $query->whereRaw('1 = 0')->simplePaginate(20);
            }

            $this->applySearch($query, $search);

            return $query->simplePaginate(20);
        }

        return $query->paginate(20);
    }

    public function normalizedSearchTerm(): string
    {
        return SocialWorker::normalizeSearchText($this->search);
    }

    public function searchNeedsMoreInput(?string $search = null): bool
    {
        $search ??= $this->normalizedSearchTerm();

        return $search !== ''
            && in_array($this->searchField, ['all', 'full_name', 'first_name', 'last_name'], true)
            && mb_strlen($search) < 2;
    }

    private function applySearch($query, string $search): void
    {
        $isNumeric = ctype_digit($search);
        $prefixSearch = "{$search}%";
        $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
            : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

        match ($this->searchField) {
            'national_id' => $this->applyIdentifierSearch($query, 'national_id', $search, $prefixSearch),
            'mobile' => $this->applyIdentifierSearch($query, 'mobile', $search, $prefixSearch),
            'first_name' => $query->where('first_name', 'LIKE', $prefixSearch),
            'last_name' => $query->where('last_name', 'LIKE', $prefixSearch),
            'full_name' => $query->where('full_name', 'LIKE', $prefixSearch),
            'worker_code' => strlen($search) >= 2
                ? $query->where('worker_code', $search)
                : $query->where('worker_code', 'LIKE', $prefixSearch),
            default => $query->where(function ($q) use ($search, $prefixSearch, $isNumeric, $fullNameExpression) {
                if ($isNumeric) {
                    $q->where('worker_code', 'LIKE', $prefixSearch)
                        ->orWhere('national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch)
                        ->orWhere('mobile', strlen($search) >= 10 ? '=' : 'LIKE', strlen($search) >= 10 ? $search : $prefixSearch);

                    return;
                }

                $q->where('full_name', 'LIKE', $prefixSearch)
                    ->orWhere('first_name', 'LIKE', $prefixSearch)
                    ->orWhere('last_name', 'LIKE', $prefixSearch)
                    ->orWhereRaw("{$fullNameExpression} LIKE ?", [$prefixSearch]);
            }),
        };
    }

    private function applyIdentifierSearch($query, string $column, string $search, string $prefixSearch): void
    {
        strlen($search) >= 10
            ? $query->where($column, $search)
            : $query->where($column, 'LIKE', $prefixSearch);
    }

    public function getCoveredDetailsForWorker(int $socialWorkerId): array
    {
        return $this->coveredDetailsByWorker[$socialWorkerId] ?? [];
    }

    public function getVisibleCoveredDetailsForWorker(int $socialWorkerId): array
    {
        return array_slice(
            $this->getCoveredDetailsForWorker($socialWorkerId),
            0,
            $this->getVisibleCoveredDetailLimitForWorker($socialWorkerId)
        );
    }

    public function getVisibleCoveredDetailLimitForWorker(int $socialWorkerId): int
    {
        return $this->visibleCoveredDetailLimitsByWorker[$socialWorkerId] ?? 20;
    }

    public function showMoreCoveredDetails(int $socialWorkerId): void
    {
        $this->visibleCoveredDetailLimitsByWorker[$socialWorkerId] = $this->getVisibleCoveredDetailLimitForWorker($socialWorkerId) + 20;
    }

    public function loadCoveredDetailsForWorker(int $socialWorkerId): void
    {
        if (array_key_exists($socialWorkerId, $this->coveredDetailsByWorker)) {
            return;
        }

        $socialWorker = SocialWorker::find($socialWorkerId);
        $this->coveredDetailsByWorker[$socialWorkerId] = $socialWorker
            ? $socialWorker->getCoveredPeopleDetails()
            : [];

        $this->coveredCountsByWorker[$socialWorkerId] = count($this->coveredDetailsByWorker[$socialWorkerId]);
    }

    public function hasLoadedCoveredDetailsForWorker(int $socialWorkerId): bool
    {
        return array_key_exists($socialWorkerId, $this->coveredDetailsByWorker);
    }

    public function getGuardiansForWorker(int $socialWorkerId): array
    {
        return $this->guardiansByWorker[$socialWorkerId] ?? [];
    }

    public function getVisibleGuardiansForWorker(int $socialWorkerId): array
    {
        return array_slice(
            $this->getGuardiansForWorker($socialWorkerId),
            0,
            $this->getVisibleGuardianLimitForWorker($socialWorkerId)
        );
    }

    public function getVisibleGuardianLimitForWorker(int $socialWorkerId): int
    {
        return $this->visibleGuardianLimitsByWorker[$socialWorkerId] ?? 10;
    }

    public function showMoreGuardians(int $socialWorkerId): void
    {
        $this->visibleGuardianLimitsByWorker[$socialWorkerId] = $this->getVisibleGuardianLimitForWorker($socialWorkerId) + 10;
    }

    public function getCoveredCountForWorker(SocialWorker $socialWorker): int
    {
        if (!array_key_exists($socialWorker->id, $this->coveredCountsByWorker)) {
            $this->coveredCountsByWorker[$socialWorker->id] = (int) $socialWorker->covered_people_count;
        }

        return $this->coveredCountsByWorker[$socialWorker->id];
    }

    public function render()
    {
        return view('livewire.social-workers.index-social-workers', [
            'totalSocialWorkers' => SocialWorker::count(),
        ]);
    }
}
