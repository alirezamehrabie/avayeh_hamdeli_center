<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\SocialWorker;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class IndexSocialWorkers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $searchField = 'all';
    public bool $embedded = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->resetPage();
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
        $this->resetPage();

        session()->flash('success', 'مددکار با موفقیت حذف شد.');
    }

    public function getSocialWorkersProperty()
    {
        $query = SocialWorker::orderBy('created_at', 'desc');

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

        return $query->paginate(50);
    }

    public function render()
    {
        return view('livewire.social-workers.index-social-workers', [
            'totalSocialWorkers' => SocialWorker::count(),
        ]);
    }
}
