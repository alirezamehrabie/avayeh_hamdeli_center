<?php

namespace App\Livewire\DistributionOperators\Concerns;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;

trait SummarizesMiscServices
{
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
