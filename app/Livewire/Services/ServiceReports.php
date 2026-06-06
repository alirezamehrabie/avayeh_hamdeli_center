<?php

namespace App\Livewire\Services;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Livewire\Component;

class ServiceReports extends Component
{
    public ?int $selectedServiceId = null;
    public string $search = '';
    public string $selectedStatus = 'all';
    public string $selectedCategory = 'all';
    public string $selectedType = 'all';
    public string $selectedServiceName = 'all';

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

        $status = ($this->selectedStatus === 'all') ? null : $this->selectedStatus;
        $category = ($this->selectedCategory === 'all') ? null : $this->selectedCategory;
        $type = ($this->selectedType === 'all') ? null : $this->selectedType;
        $serviceName = ($this->selectedServiceName === 'all') ? null : $this->selectedServiceName;

        $categories = ServiceCategory::query()
            ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($sq) => $sq->where('name', $serviceName)))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $serviceNames = ServiceName::query()
            ->ordered()
            ->pluck('name')
            ->toArray();

        return view('livewire.services.service-reports', [
            'services' => Service::query()
                ->with(['serviceName', 'serviceCategory', 'district', 'socialWorkers', 'creator'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($category, fn ($q) => $q->whereHas('serviceCategory', fn ($q) => $q->where('name', $category)))
                ->when($type, fn ($q) => $q->where('service_type', $type))
                ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($q) => $q->where('name', $serviceName)))
                ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                    $inner
                        ->orWhere('service_code', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('serviceName', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('serviceCategory', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%'));
                }))
                ->latest()
                ->get(),
            'selectedService' => $this->selectedService,
            'statusOptions' => Service::STATUS_OPTIONS,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::UNIT_OPTIONS,
            'categoryOptions' => $categories,
            'serviceNames' => $serviceNames,
            'jalaliDateTime' => fn ($dateTime) => $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-',
        ]);
    }
}
