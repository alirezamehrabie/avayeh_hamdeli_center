<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.social-worker')]
class DeliveryHistory extends Component
{
    use WithPagination;

    public string $activeSection = 'delivery-history';
    public mixed $selectedServiceId = null;
    public string $deliverySearch = '';
    public string $deliveryDateFrom = '';
    public string $deliveryDateTo = '';

    protected $queryString = [
        'selectedServiceId' => ['except' => null],
        'deliverySearch' => ['except' => ''],
        'deliveryDateFrom' => ['except' => ''],
        'deliveryDateTo' => ['except' => ''],
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

    public function updated($property): void
    {
        if (in_array($property, ['deliverySearch', 'deliveryDateFrom', 'deliveryDateTo'], true)) {
            $this->resetPage();
        }
    }

    public function clearDeliveryFilters(): void
    {
        $this->deliverySearch = '';
        $this->deliveryDateFrom = '';
        $this->deliveryDateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $socialWorkerId = (int) auth()->user()->social_worker_id;
        $selectedServiceId = $this->normalizedSelectedServiceId();
        $selectedService = $selectedServiceId ? $this->selectedService($socialWorkerId, $selectedServiceId) : null;

        if ($selectedServiceId && ! $selectedService) {
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

    protected function selectedService(int $socialWorkerId, int $selectedServiceId): ?Service
    {
        return Service::query()
            ->with(['serviceName', 'categories'])
            ->withCount([
                'deliveries as worker_deliveries_count' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ])
            ->withSum([
                'deliveries as worker_delivered_quantity' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ], 'delivered_quantity')
            ->withSum([
                'deliveries as worker_delivered_value' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ], 'delivered_total_value')
            ->withMax([
                'deliveries as worker_last_delivery_at' => fn ($query) => $query->where('social_worker_id', $socialWorkerId),
            ], 'delivered_at')
            ->whereKey($selectedServiceId)
            ->whereHas('deliveries', fn ($query) => $query->where('social_worker_id', $socialWorkerId))
            ->first();
    }

    protected function normalizedSelectedServiceId(): ?int
    {
        if ($this->selectedServiceId === null || $this->selectedServiceId === '') {
            return null;
        }

        $selectedServiceId = filter_var($this->selectedServiceId, FILTER_VALIDATE_INT);

        return $selectedServiceId !== false && $selectedServiceId > 0
            ? $selectedServiceId
            : null;
    }

    protected function deliveries(int $socialWorkerId, int $serviceId)
    {
        $search = trim($this->deliverySearch);
        $dateFrom = $this->normalizedDateInput($this->deliveryDateFrom);
        $dateTo = $this->normalizedDateInput($this->deliveryDateTo);

        return ServiceDelivery::query()
            ->with(['service.serviceName', 'person', 'guardian'])
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_id', $serviceId)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhereHas('person', function ($query) use ($search): void {
                            $query
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('guardian', function ($query) use ($search): void {
                            $query
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('national_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('delivered_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('delivered_at', '<=', $dateTo))
            ->latest('delivered_at')
            ->latest('id')
            ->paginate(25);
    }

    protected function normalizedDateInput(string $date): ?string
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function formatLastDeliveryDate(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }
}
