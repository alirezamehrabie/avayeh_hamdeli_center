<?php

namespace App\Livewire\DistributionOperators;

use App\Livewire\DistributionOperators\Concerns\SummarizesMiscServices;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.distribution-operator')]
class ServiceList extends Component
{
    use SummarizesMiscServices;
    use WithPagination;

    public const TAB_CAMPAIGNS = 'campaigns';

    public const TAB_MISC = 'misc';

    #[Url(as: 'tab')]
    public string $activeTab = self::TAB_CAMPAIGNS;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    public int $perPage = 12;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        if (! in_array($this->activeTab, $this->availableTabs(), true)) {
            $this->activeTab = self::TAB_CAMPAIGNS;
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, $this->availableTabs(), true)) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearCatalogFilters(): void
    {
        $this->search = '';
        $this->sort = 'latest';
        $this->resetPage();
    }

    public function selectCatalogService(int $serviceId): void
    {
        $service = $this->activeServicesQuery()
            ->with('serviceName:id,name')
            ->find($serviceId);

        if (! $service) {
            return;
        }

        $this->search = $this->serviceSearchLabel($service);
        $this->resetPage();
    }

    public function render()
    {
        $counts = [
            self::TAB_CAMPAIGNS => $this->campaignServicesQuery()->count(),
            self::TAB_MISC => $this->miscServicesQuery()->count(),
        ];

        $query = $this->filteredServicesQuery();
        $latestMiscService = $this->latestMiscService();

        return view('livewire.distribution-operators.service-list', [
            'services' => $query
                ->with([
                    'serviceName',
                    'serviceCategory',
                    'categories',
                    'socialWorkers',
                    'workerAllocations.socialWorker',
                ])
                ->paginate($this->perPage),
            'counts' => $counts,
            'unitOptions' => Service::unitOptions(),
            'sortOptions' => $this->sortOptions(),
            'hasActiveFilters' => $this->hasActiveFilters(),
            'latestMiscService' => $latestMiscService,
            'latestMiscServiceSummary' => $latestMiscService
                ? $this->latestMiscServiceSummary($latestMiscService)
                : null,
            'todayMiscCount' => $this->miscServicesQuery()->whereDate('created_at', today())->count(),
            'catalogSearchOptions' => $this->catalogSearchOptions(),
        ]);
    }

    protected function catalogSearchOptions(): array
    {
        return $this->filteredServicesQuery()
            ->with(['serviceName:id,name', 'categories:id,service_id,name'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'code' => $service->code ?: '',
                'name' => $this->serviceSearchLabel($service),
                'categories' => $service->categories
                    ->pluck('name')
                    ->filter()
                    ->take(3)
                    ->implode('، '),
                'createdAt' => optional($service->created_at)->format('Y/m/d') ?: '',
            ])
            ->values()
            ->all();
    }

    protected function serviceSearchLabel(Service $service): string
    {
        return (string) ($service->serviceName?->name ?: $service->name ?: $service->code ?: '');
    }

    protected function filteredServicesQuery(): Builder
    {
        return $this->activeServicesQuery()
            ->when(trim($this->search) !== '', fn (Builder $query) => $this->applySearch($query, trim($this->search)))
            ->tap(fn (Builder $query) => $this->applySort($query));
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $searchQuery) use ($search): void {
            $searchQuery
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('serviceName', fn (Builder $serviceNameQuery) => $serviceNameQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('socialWorkers', function (Builder $workerQuery) use ($search): void {
                    $workerQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('worker_code', 'like', "%{$search}%");
                });
        });
    }

    protected function applySort(Builder $query): Builder
    {
        return match ($this->sort) {
            'oldest' => $query->oldest(),
            'name' => $query
                ->orderBy(
                    \App\Models\ServiceName::query()
                        ->select('name')
                        ->whereColumn('service_names.id', 'services.service_name_id')
                        ->limit(1)
                )
                ->orderByDesc('id'),
            'start_date' => $query->orderByDesc('distribution_start_date')->orderByDesc('id'),
            'capacity' => $query->orderByDesc('total_quantity')->orderByDesc('id'),
            default => $query->latest(),
        };
    }

    protected function activeServicesQuery(): Builder
    {
        return $this->activeTab === self::TAB_MISC
            ? $this->miscServicesQuery()
            : $this->campaignServicesQuery();
    }

    protected function campaignServicesQuery(): Builder
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return Service::query()
            ->where(function (Builder $query): void {
                $query->where('created_by', '!=', auth()->id())
                    ->orWhereNull('created_by');
            })
            ->whereHas('workerAllocations', function (Builder $allocationQuery): void {
                $allocationQuery->where('assigned_by_user_id', auth()->id());
            });
    }

    protected function miscServicesQuery(): Builder
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return Service::query()
            ->where('created_by', auth()->id())
            ->whereHas('creator', function (Builder $creatorQuery): void {
                $creatorQuery->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
            });
    }

    protected function latestMiscService(): ?Service
    {
        return $this->miscServicesQuery()
            ->with(['serviceName', 'categories', 'workerAllocations'])
            ->latest()
            ->first();
    }

    protected function availableTabs(): array
    {
        return [
            self::TAB_CAMPAIGNS,
            self::TAB_MISC,
        ];
    }

    protected function sortOptions(): array
    {
        return [
            'latest' => 'جدیدترین',
            'start_date' => 'تاریخ شروع',
            'capacity' => 'بیشترین ظرفیت',
            'name' => 'نام خدمت',
            'oldest' => 'قدیمی‌ترین',
        ];
    }

    protected function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->sort !== 'latest';
    }
}
