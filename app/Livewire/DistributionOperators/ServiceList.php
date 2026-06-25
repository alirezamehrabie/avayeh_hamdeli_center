<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class ServiceList extends Component
{
    public const TAB_CAMPAIGNS = 'campaigns';

    public const TAB_MISC = 'misc';

    #[Url(as: 'tab')]
    public string $activeTab = self::TAB_CAMPAIGNS;

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
        }
    }

    public function render()
    {
        $counts = [
            self::TAB_CAMPAIGNS => $this->campaignServicesQuery()->count(),
            self::TAB_MISC => $this->miscServicesQuery()->count(),
        ];

        return view('livewire.distribution-operators.service-list', [
            'services' => $this->activeServicesQuery()
                ->with([
                    'serviceName',
                    'serviceCategory',
                    'categories',
                    'socialWorkers',
                    'workerAllocations.socialWorker',
                ])
                ->latest()
                ->get(),
            'counts' => $counts,
            'unitOptions' => Service::unitOptions(),
        ]);
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

    protected function availableTabs(): array
    {
        return [
            self::TAB_CAMPAIGNS,
            self::TAB_MISC,
        ];
    }
}
