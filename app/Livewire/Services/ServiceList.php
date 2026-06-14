<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Component;

class ServiceList extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function editService(int $serviceId): void
    {
        $this->dispatch('open-dashboard-section', section: 'define-services', id: $serviceId);
    }

    public function createService(): void
    {
        $this->dispatch('open-dashboard-section', section: 'define-services');
    }

    public function render()
    {
        return view('livewire.services.service-list', [
            'services' => Service::query()
                ->with([
                    'serviceName',
                    'serviceCategory',
                    'categories' => fn ($query) => $query->ordered(),
                    'district',
                    'creator',
                    'socialWorkers',
                ])
                ->latest()
                ->get(),
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'statusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }
}
