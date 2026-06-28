<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class DefineService extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);
    }

    public function render()
    {
        $latestMiscService = $this->latestMiscService();

        return view('livewire.distribution-operators.define-service', [
            'latestMiscService' => $latestMiscService,
            'latestMiscServiceSummary' => $latestMiscService
                ? $this->latestMiscServiceSummary($latestMiscService)
                : null,
            'todayMiscCount' => $this->miscServicesQuery()->whereDate('created_at', today())->count(),
        ]);
    }

    protected function latestMiscService(): ?Service
    {
        return $this->miscServicesQuery()
            ->with(['serviceName', 'categories', 'workerAllocations.socialWorker'])
            ->latest()
            ->first();
    }

    protected function miscServicesQuery()
    {
        return Service::query()
            ->where('created_by', auth()->id())
            ->whereHas('creator', function ($creatorQuery): void {
                $creatorQuery->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
            });
    }

    protected function latestMiscServiceSummary(Service $service): array
    {
        $unitOptions = Service::unitOptions();
        $categories = $service->categories;
        $workerAllocations = $service->workerAllocations;
        $primaryUnit = $categories->first()?->unit ?? 'count';
        $distributionDate = $service->distribution_start_date
            ? Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d')
            : null;

        return [
            'name' => $service->name ?: ($service->serviceName?->name ?? 'خدمت متفرقه'),
            'code' => $service->code ?: '—',
            'category_names' => $categories->pluck('name')->filter()->take(4)->values(),
            'category_count' => $categories->count(),
            'worker_count' => $workerAllocations->pluck('social_worker_id')->filter()->unique()->count(),
            'total_quantity' => (float) $service->total_quantity,
            'unit_label' => $unitOptions[$primaryUnit] ?? $primaryUnit,
            'date' => $distributionDate,
            'latest_worker' => $workerAllocations->first()?->socialWorker?->full_name,
            'updated_at' => $service->updated_at?->diffForHumans(),
        ];
    }
}
