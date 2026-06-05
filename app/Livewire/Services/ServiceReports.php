<?php

namespace App\Livewire\Services;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use Livewire\Component;

class ServiceReports extends Component
{
    public ?int $selectedServiceId = null;
    public string $search = '';

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
        $search = trim($this->search);

        return view('livewire.services.service-reports', [
            'services' => Service::query()
                ->with(['serviceName', 'serviceCategory', 'district', 'socialWorkers', 'creator'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($innerQuery) use ($search) {
                        if (ctype_digit($search)) {
                            $innerQuery->orWhere('id', (int) $search);
                        }

                        $innerQuery
                            ->orWhere('service_code', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%')
                            ->orWhereHas('serviceName', function ($serviceNameQuery) use ($search) {
                                $serviceNameQuery->where('name', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('serviceCategory', function ($categoryQuery) use ($search) {
                                $categoryQuery->where('name', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                                $creatorQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('first_name', 'like', '%' . $search . '%')
                                    ->orWhere('last_name', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->latest()
                ->get(),
            'selectedService' => $this->selectedService,
            'statusOptions' => Service::STATUS_OPTIONS,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::UNIT_OPTIONS,
            'jalaliDateTime' => fn ($dateTime) => $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-',
        ]);
    }
}
