<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
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
            'unitOptions' => Service::unitOptions(),
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
        // Misc services are shared across all distribution operators, so the
        // quick-access panel reflects the latest misc service defined by any
        // operator rather than only the current one's own.
        return Service::query()->createdByDistributionOperator();
    }

    protected function latestMiscServiceSummary(Service $service): array
    {
        $categories = $service->categories;
        $workerAllocations = $service->workerAllocations;
        $distributionDate = $service->distribution_start_date
            ? Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d')
            : null;
        $categoryNames = $categories->pluck('name')->filter()->values();
        $categoryCount = $categoryNames->count();
        $allocationCount = $workerAllocations->groupBy('social_worker_id')->count();
        $quantity = (float) ($service->total_quantity ?: $categories->sum('quantity'));

        return [
            'name' => $service->name ?: ($service->serviceName?->name ?? 'خدمت متفرقه'),
            'category_names' => $categoryNames->take(3)->values(),
            'category_count' => $categoryCount,
            'date' => $distributionDate,
            'allocation_count' => $allocationCount,
            'quantity' => $quantity,
            'unit_label' => Service::unitOptions()[$service->service_unit] ?? ($service->service_unit ?? 'واحد'),
            'description_preview' => filled($service->description)
                ? str($service->description)->squish()->limit(110)->toString()
                : null,
        ];
    }
}
