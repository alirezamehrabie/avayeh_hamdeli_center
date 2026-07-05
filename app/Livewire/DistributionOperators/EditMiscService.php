<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class EditMiscService extends Component
{
    public ?int $serviceId = null;

    public function mount(int $serviceId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-distribution-operator-services'), 403);

        $service = Service::query()->findOrFail($serviceId);

        abort_unless(auth()->user()?->can('edit-distribution-operator-service', $service), 403);

        $this->serviceId = $service->id;
    }

    public function render()
    {
        return view('livewire.distribution-operators.edit-misc-service');
    }
}
