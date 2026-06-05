<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Component;

class ServiceReports extends Component
{
    public ?int $selectedServiceId = null;

    public function mount(?int $selectedServiceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedServiceId = $selectedServiceId;
    }

    public function openService(int $serviceId): void
    {
        $serviceExists = Service::query()->whereKey($serviceId)->exists();

        if (! $serviceExists) {
            return;
        }

        $this->selectedServiceId = $serviceId;
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report', id: $serviceId);
    }

    public function backToServices(): void
    {
        $this->selectedServiceId = null;
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report');
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with([
                'serviceName',
                'serviceCategory',
                'district',
                'socialWorkers',
                'deliveries.person.guardian',
                'deliveries.guardian',
                'deliveries.socialWorker',
                'deliveries.creator',
            ])
            ->find($this->selectedServiceId);
    }

    public function render()
    {
        return view('livewire.services.service-reports', [
            'services' => Service::query()
                ->with(['serviceName', 'serviceCategory', 'district', 'socialWorkers'])
                ->latest()
                ->get(),
            'selectedService' => $this->selectedService,
            'statusOptions' => Service::STATUS_OPTIONS,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::UNIT_OPTIONS,
        ]);
    }
}
