<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class EditServiceAllocations extends Component
{
    public ?int $serviceId = null;

    public function mount(int $serviceId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $service = Service::query()->with('workerAllocations')->findOrFail($serviceId);

        $hasOperatorAllocations = $service->workerAllocations
            ->contains('assigned_by_user_id', auth()->id());

        if (! $hasOperatorAllocations) {
            abort(403);
        }

        $this->serviceId = $service->id;
    }

    public function render()
    {
        return view('livewire.distribution-operators.edit-service-allocations');
    }
}
