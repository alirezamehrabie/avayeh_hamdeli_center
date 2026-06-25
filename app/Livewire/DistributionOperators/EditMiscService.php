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
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $service = Service::query()->findOrFail($serviceId);

        if ((int) $service->created_by !== (int) auth()->id()) {
            abort(403);
        }

        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        $this->serviceId = $service->id;
    }

    public function render()
    {
        return view('livewire.distribution-operators.edit-misc-service');
    }
}
