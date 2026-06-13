<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.social-worker')]
class DeliveryHistory extends Component
{
    use WithPagination;

    public string $activeSection = 'delivery-history';
    public ?int $selectedServiceId = null;

    protected $queryString = [
        'selectedServiceId' => ['except' => null],
    ];

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);
    }

    public function selectService(int $serviceId): void
    {
        $this->selectedServiceId = $serviceId;
        $this->resetPage();
    }

    public function backToServices(): void
    {
        $this->selectedServiceId = null;
        $this->resetPage();
    }

    public function render()
    {
        $socialWorkerId = (int) auth()->user()->social_worker_id;
        $selectedService = $this->selectedServiceId ? $this->selectedService($socialWorkerId) : null;

        if ($this->selectedServiceId && ! $selectedService) {
            $this->selectedServiceId = null;
        }

        return view('livewire.social-workers.delivery-history', [
            'services' => $this->services($socialWorkerId),
            'selectedService' => $selectedService,
            'deliveries' => $selectedService
                ? $this->deliveries($socialWorkerId, $selectedService->id)
                : null,
        ]);
    }

    protected function services(int $socialWorkerId)
    {
        return Service::query()
            ->with(['serviceName', 'categories'])
            ->whereHas('deliveries', fn ($query) => $query->where('social_worker_id', $socialWorkerId))
            ->withMax([
                'deliveries as last_delivery_at' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ], 'delivered_at')
            ->withCount([
                'deliveries as deliveries_count' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ])
            ->latest('last_delivery_at')
            ->latest('id')
            ->get();
    }

    protected function selectedService(int $socialWorkerId): ?Service
    {
        return Service::query()
            ->with(['serviceName', 'categories'])
            ->whereKey($this->selectedServiceId)
            ->whereHas('deliveries', fn ($query) => $query->where('social_worker_id', $socialWorkerId))
            ->first();
    }

    protected function deliveries(int $socialWorkerId, int $serviceId)
    {
        return ServiceDelivery::query()
            ->with(['service.serviceName', 'person', 'guardian'])
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_id', $serviceId)
            ->latest('delivered_at')
            ->latest('id')
            ->paginate(25);
    }

    public function formatLastDeliveryDate(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }
}
