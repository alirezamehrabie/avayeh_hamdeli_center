<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\SocialWorker;
use Illuminate\Support\Facades\DB;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class IndexSocialWorkers extends Component
{
    public string $search = '';
    public string $searchField = 'all';
    public bool $embedded = false;
    public ?int $expandedSocialWorkerId = null;
    public array $coveredDetailsByWorker = [];
    public array $coveredCountsByWorker = [];

    public function updatingSearch(): void
    {
        $this->expandedSocialWorkerId = null;
    }

    public function updatingSearchField(): void
    {
        $this->expandedSocialWorkerId = null;
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

        session()->flash('success', 'مددکار با موفقیت حذف شد.');
    }

    public function toggleSocialWorker(int $socialWorkerId): void
    {
        if ($this->expandedSocialWorkerId === $socialWorkerId) {
            $this->expandedSocialWorkerId = null;
            return;
        }

        $this->expandedSocialWorkerId = $socialWorkerId;

        if (!array_key_exists($socialWorkerId, $this->coveredDetailsByWorker)) {
            $socialWorker = SocialWorker::find($socialWorkerId);
            $this->coveredDetailsByWorker[$socialWorkerId] = $socialWorker
                ? $socialWorker->getCoveredPeopleDetails()
                : [];
        }

        if (!array_key_exists($socialWorkerId, $this->coveredCountsByWorker)) {
            $this->coveredCountsByWorker[$socialWorkerId] = count($this->coveredDetailsByWorker[$socialWorkerId] ?? []);
        }
    }

    public function getSocialWorkersProperty()
    {
        $query = SocialWorker::with([
            'guardians' => fn ($guardianQuery) => $guardianQuery
                ->select([
                    'id',
                    'social_worker_id',
                    'national_code',
                    'first_name',
                    'last_name',
                    'guardian_phone_number',
                ])
                ->withCount('people')
                ->orderBy('id'),
        ])->orderBy('created_at', 'desc');

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            match ($this->searchField) {
                'national_id' => $query->where('national_id', 'LIKE', "%{$search}%"),
                'mobile' => $query->where('mobile', 'LIKE', "%{$search}%"),
                'first_name' => $query->where('first_name', 'LIKE', "%{$search}%"),
                'last_name' => $query->where('last_name', 'LIKE', "%{$search}%"),
                'full_name' => $query->whereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]),
                'worker_code' => $query->where('worker_code', 'LIKE', "%{$search}%"),
                default => $query->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('national_id', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('worker_code', 'LIKE', "%{$search}%")
                        ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
                }),
            };
        }

        return $query->get()->map(function (SocialWorker $socialWorker) {
            $socialWorker->setAttribute(
                'covered_people_count',
                $this->getCoveredCountForWorker($socialWorker)
            );

            return $socialWorker;
        });
    }

    public function getCoveredDetailsForWorker(int $socialWorkerId): array
    {
        return $this->coveredDetailsByWorker[$socialWorkerId] ?? [];
    }

    public function getCoveredCountForWorker(SocialWorker $socialWorker): int
    {
        if (!array_key_exists($socialWorker->id, $this->coveredCountsByWorker)) {
            $details = $this->getOrLoadCoveredDetailsForWorker($socialWorker);
            $this->coveredCountsByWorker[$socialWorker->id] = count($details);
        }

        return $this->coveredCountsByWorker[$socialWorker->id];
    }

    private function getOrLoadCoveredDetailsForWorker(SocialWorker $socialWorker): array
    {
        if (!array_key_exists($socialWorker->id, $this->coveredDetailsByWorker)) {
            $this->coveredDetailsByWorker[$socialWorker->id] = $socialWorker->getCoveredPeopleDetails();
        }

        return $this->coveredDetailsByWorker[$socialWorker->id];
    }

    public function render()
    {
        return view('livewire.social-workers.index-social-workers', [
            'totalSocialWorkers' => SocialWorker::count(),
        ]);
    }
}
