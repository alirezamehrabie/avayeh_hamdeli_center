<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class ServiceList extends Component
{
    public ?int $editingServiceId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);
    }

    public function startEditing(int $serviceId): void
    {
        $service = $this->operatorServicesQuery()->with('socialWorkers')->findOrFail($serviceId);
        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        $this->editingServiceId = $service->id;
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-list', [
            'services' => $this->operatorServicesQuery()
                ->with(['serviceName', 'categories', 'socialWorkers'])
                ->latest()
                ->get(),
            'unitOptions' => Service::unitOptions(),
        ]);
    }

    protected function operatorServicesQuery(): Builder
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return Service::query()
            ->where('created_by', auth()->id())
            ->whereHas('creator', function (Builder $query): void {
                $query->where('access_level', \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
            });
    }

}
