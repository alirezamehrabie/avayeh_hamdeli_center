<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ServiceList extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function editService(int $serviceId): void
    {
        $this->dispatch('open-dashboard-section', section: 'service-definition', id: $serviceId);
    }

    public function createService(): void
    {
        $this->dispatch('open-dashboard-section', section: 'service-definition');
    }

    public function formatReadableNumber(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return number_format((int) $number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    }

    public function formatQuantityForUnit(string|int|float|null $value, string $unit): string
    {
        $number = (float) ($value ?? 0);

        return number_format($number, $this->isDecimalQuantityUnit($unit) ? 2 : 0, '.', '');
    }

    public function isDecimalQuantityUnit(string $unit): bool
    {
        return in_array($unit, ['kilogram', 'gram', 'kg', 'g'], true);
    }

    public function render()
    {
        $search = trim($this->search);
        $statusFilter = $this->statusFilter;

        return view('livewire.services.service-list', [
            'services' => Service::query()
                ->with([
                    'serviceName',
                    'serviceCategory',
                    'categories' => fn ($query) => $query->ordered(),
                    'district',
                    'creator',
                    'socialWorkers',
                    'workerAllocations.socialWorker',
                    'deliveries',
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nestedQuery) use ($search) {
                        $nestedQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhereHas('serviceName', fn ($serviceNameQuery) => $serviceNameQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                                $creatorQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', "%{$search}%");
                            });
                    });
                })
                ->when($statusFilter !== 'all', fn ($query) => $query->where('status', $statusFilter))
                ->latest()
                ->get(),
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'statusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }

    public function socialWorkerSummary(Service $service, array $unitOptions): array
    {
        $deliveries = $service->deliveries;
        $categories = $service->categories->keyBy('id');

        return [
            'service' => $service->serviceName?->name ?: '-',
            'code' => $service->code,
            'workers' => $service->workerAllocations
                ->groupBy('social_worker_id')
                ->map(function (Collection $allocations, int|string $workerId) use ($deliveries, $categories, $unitOptions): array {
                    $worker = $allocations->first()?->socialWorker;
                    $workerDeliveries = $deliveries->where('social_worker_id', (int) $workerId);
                    $totalAllocated = (float) $allocations->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                    $totalDelivered = (float) $workerDeliveries->sum(fn ($delivery) => (float) $delivery->delivered_quantity);
                    $progress = $totalAllocated > 0 ? min(100, round(($totalDelivered / $totalAllocated) * 100, 1)) : 0;

                    $categoryIds = $allocations
                        ->pluck('service_category_id')
                        ->merge($workerDeliveries->pluck('service_category_id'))
                        ->filter()
                        ->unique()
                        ->values();

                    return [
                        'id' => (int) $workerId,
                        'name' => $worker?->full_name ?: '-',
                        'code' => $worker?->worker_code ? (string) $worker->worker_code : '-',
                        'mobile' => $worker?->mobile ?: '-',
                        'initials' => $this->workerInitials($worker?->first_name, $worker?->last_name),
                        'allocated' => $this->formatReadableNumber($totalAllocated),
                        'delivered' => $this->formatReadableNumber($totalDelivered),
                        'remaining' => $this->formatReadableNumber(max(0, $totalAllocated - $totalDelivered)),
                        'progress' => $progress,
                        'categories' => $categoryIds
                            ->map(function (int|string $categoryId) use ($allocations, $workerDeliveries, $categories, $unitOptions): array {
                                $category = $categories->get((int) $categoryId);
                                $allocated = (float) $allocations
                                    ->where('service_category_id', (int) $categoryId)
                                    ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                                $delivered = (float) $workerDeliveries
                                    ->where('service_category_id', (int) $categoryId)
                                    ->sum(fn ($delivery) => (float) $delivery->delivered_quantity);
                                $progress = $allocated > 0 ? min(100, round(($delivered / $allocated) * 100, 1)) : 0;
                                $unit = $category?->unit ? ($unitOptions[$category->unit] ?? $category->unit) : '-';

                                return [
                                    'name' => $category?->name ?: '-',
                                    'unit' => $unit,
                                    'allocated' => $this->formatReadableNumber($allocated),
                                    'delivered' => $this->formatReadableNumber($delivered),
                                    'remaining' => $this->formatReadableNumber(max(0, $allocated - $delivered)),
                                    'progress' => $progress,
                                ];
                            })
                            ->values(),
                    ];
                })
                ->sortBy('name')
                ->values(),
        ];
    }

    protected function workerInitials(?string $firstName, ?string $lastName): string
    {
        $initials = mb_substr(trim((string) $firstName), 0, 1).mb_substr(trim((string) $lastName), 0, 1);

        return $initials !== '' ? $initials : '؟';
    }
}
