<?php

namespace App\Livewire\Services;

use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ServiceDeliveryManager extends Component
{
    public ?int $selectedServiceId = null;

    /**
     * @var array<int, array<int, string|int|float|null>>
     */
    public array $allocations = [];

    public string $socialWorkerSearch = '';

    public bool $showSavedSummary = false;

    public bool $confirmingAllocationSave = false;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function updatedSelectedServiceId(): void
    {
        $this->syncSelectedServiceState();
        $this->showSavedSummary = false;
        $this->resetValidation();
    }

    public function updatedSocialWorkerSearch(): void
    {
        $this->resetValidation();
    }

    public function updatedAllocations(): void
    {
        $this->confirmingAllocationSave = false;
        $this->resetValidation();
    }

    public function syncSelectedServiceState(): void
    {
        unset($this->selectedService);

        $this->allocations = [];
        $this->socialWorkerSearch = '';

        if (! $this->selectedServiceId) {
            return;
        }

        $service = $this->selectedService;

        if (! $service) {
            $this->selectedServiceId = null;

            return;
        }

        $workerAllocations = $service->workerAllocations
            ->groupBy('social_worker_id');

        foreach ($workerAllocations as $workerId => $rows) {
            foreach ($rows as $row) {
                $categoryId = (int) $row->service_category_id;
                $this->allocations[(int) $workerId][$categoryId] = $this->formatDecimal($row->allocated_quantity);
            }
        }
    }

    public function addSocialWorker(int $workerId): void
    {
        if (! $this->selectedService) {
            return;
        }

        $worker = SocialWorker::query()->find($workerId);

        if (! $worker) {
            return;
        }

        foreach ($this->selectedServiceCategories as $category) {
            $this->allocations[$workerId][(int) $category->id] = $this->allocations[$workerId][(int) $category->id] ?? '0';
        }

        $this->socialWorkerSearch = '';
        $this->resetValidation();
    }

    public function removeSocialWorker(int $workerId): void
    {
        unset($this->allocations[$workerId]);
        $this->confirmingAllocationSave = false;
        $this->resetValidation();
    }

    public function requestSaveConfirmation(): void
    {
        $this->validateAllocationPayload();

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->confirmingAllocationSave = false;

            return;
        }

        $this->confirmingAllocationSave = true;
    }

    public function cancelSaveConfirmation(): void
    {
        $this->confirmingAllocationSave = false;
    }

    public function confirmSaveAllocations(): void
    {
        if (! $this->confirmingAllocationSave) {
            $this->requestSaveConfirmation();

            return;
        }

        $this->saveAllocations();
    }

    public function saveAllocations(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $workerRows = $this->validateAllocationPayload();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($service, $workerRows): void {
            $service->workerAllocations()->delete();

            foreach ($workerRows as $row) {
                ServiceWorkerAllocation::query()->create([
                    'service_id' => $service->id,
                    'social_worker_id' => $row['worker_id'],
                    'service_category_id' => $row['category_id'],
                    'allocated_quantity' => $row['quantity'],
                    'assigned_by_user_id' => auth()->id(),
                ]);
            }
        });

        $this->resetAfterSuccessfulSave();
        $this->dispatch('quota-assigned-success', message: 'سهمیه‌ها با موفقیت برای مددکاران ثبت شد.');
    }

    public function resetAfterSuccessfulSave(): void
    {
        $this->selectedServiceId = null;
        $this->allocations = [];
        $this->socialWorkerSearch = '';
        $this->showSavedSummary = false;
        $this->confirmingAllocationSave = false;
        $this->resetValidation();

        unset($this->selectedService);
    }

    protected function validateAllocationPayload(): Collection
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $workerIds = collect(array_keys($this->allocations))
            ->map(fn ($id) => (int) $id)
            ->values();

        $categoryIds = $this->selectedServiceCategories->pluck('id')->map(fn ($id) => (int) $id)->values();

        $rules = [
            'allocations' => ['array'],
            'allocations.*' => ['array'],
            'allocations.*.*' => ['nullable', 'numeric', 'min:0'],
        ];

        foreach ($workerIds as $workerId) {
            $rules['allocations.'.$workerId] = ['array'];

            foreach ($categoryIds as $categoryId) {
                $rules['allocations.'.$workerId.'.'.$categoryId] = ['nullable', 'numeric', 'min:0'];
            }
        }

        $this->validate($rules, [], $this->validationAttributes());

        $workerRows = collect($this->allocations)
            ->map(function (array $workerAllocations, int $workerId) use ($categoryIds): Collection {
                return collect($categoryIds)->mapWithKeys(function (int $categoryId) use ($workerAllocations, $workerId): array {
                    return [
                        $categoryId => [
                            'worker_id' => $workerId,
                            'category_id' => $categoryId,
                            'quantity' => (float) ($workerAllocations[$categoryId] ?? 0),
                        ],
                    ];
                });
            })
            ->flatten(1)
            ->filter(fn (array $row): bool => $row['quantity'] > 0)
            ->values();

        $validWorkerCount = SocialWorker::query()
            ->whereIn('id', $workerRows->pluck('worker_id')->unique()->all())
            ->count();

        if ($validWorkerCount !== $workerRows->pluck('worker_id')->unique()->count()) {
            $this->addError('allocations', 'یکی از مددکاران انتخاب‌شده معتبر نیست.');

            return collect();
        }

        foreach ($categoryIds as $categoryId) {
            $categoryDefined = $this->categoryDefinedQuantity($categoryId);
            $categoryTotal = $workerRows
                ->where('category_id', $categoryId)
                ->sum('quantity');

            if ($categoryTotal > $categoryDefined) {
                $this->addError(
                    'allocations',
                    'مجموع سهمیه مددکاران برای هر آیتم نمی‌تواند از مقدار تعریف‌شده همان آیتم بیشتر باشد.'
                );

                return collect();
            }
        }

        $shortfall = $this->firstExistingDeliveryAllocationShortfall($service, $workerRows);

        if ($shortfall) {
            $this->addError(
                'allocations',
                'سهمیه جدید نمی‌تواند کمتر از مقدار تحویل‌شده قبلی برای مددکار و آیتم مربوطه باشد.'
            );

            return collect();
        }

        return $workerRows;
    }

    public function enableEditing(): void
    {
        $this->showSavedSummary = false;
        $this->syncSelectedServiceState();
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with([
                'serviceName',
                'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id'),
                'workerAllocations.socialWorker',
                'workerAllocations.serviceCategory',
            ])
            ->supportsHomeDelivery()
            ->find($this->selectedServiceId);
    }

    public function getSelectedServiceCategoriesProperty(): Collection
    {
        return $this->selectedService?->categories?->values() ?? collect();
    }

    public function getSelectedWorkersProperty(): Collection
    {
        $workerIds = collect(array_keys($this->allocations))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($workerIds->isEmpty()) {
            return collect();
        }

        $workers = SocialWorker::query()
            ->whereIn('id', $workerIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return $workerIds
            ->map(fn (int $workerId) => $workers->firstWhere('id', $workerId))
            ->filter()
            ->values();
    }

    public function getAvailableSocialWorkersProperty(): Collection
    {
        $search = trim($this->socialWorkerSearch);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        $searchParts = preg_split('/\s+/', $search, 2) ?: [];
        $firstSearchPart = $searchParts[0] ?? $search;
        $secondSearchPart = $searchParts[1] ?? null;

        return SocialWorker::query()
            ->where(function ($workerQuery) use ($search, $firstSearchPart, $secondSearchPart): void {
                $workerQuery->where('first_name', 'like', $search.'%')
                    ->orWhere('last_name', 'like', $search.'%')
                    ->orWhere('worker_code', 'like', $search.'%');

                if ($secondSearchPart) {
                    $workerQuery->orWhere(function ($nameQuery) use ($firstSearchPart, $secondSearchPart): void {
                        $nameQuery->where('first_name', 'like', $firstSearchPart.'%')
                            ->where('last_name', 'like', $secondSearchPart.'%');
                    });
                }
            })
            ->whereNotIn('id', array_keys($this->allocations))
            ->orderByRaw(
                'CASE
                    WHEN worker_code LIKE ? THEN 0
                    WHEN first_name LIKE ? THEN 1
                    WHEN last_name LIKE ? THEN 2
                    ELSE 3
                END',
                [$search.'%', $search.'%', $search.'%']
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get();
    }

    public function getCurrentAllocatedTotalProperty(): float
    {
        return collect($this->allocations)
            ->sum(fn (array $workerAllocations) => collect($workerAllocations)->sum(fn ($quantity) => (float) $quantity));
    }

    public function getRemainingAssignableQuantityProperty(): float
    {
        return max(0, (float) ($this->selectedService?->total_quantity ?? 0) - $this->currentAllocatedTotal);
    }

    public function allocationForWorker(int $workerId): float
    {
        return collect($this->allocations[$workerId] ?? [])
            ->sum(fn ($quantity) => (float) $quantity);
    }

    public function allocationForCategory(int $categoryId): float
    {
        return collect($this->allocations)
            ->sum(fn (array $workerAllocations) => (float) ($workerAllocations[$categoryId] ?? 0));
    }

    public function remainingAssignableForCategory(int $categoryId): float
    {
        return max(0, $this->categoryDefinedQuantity($categoryId) - $this->allocationForCategory($categoryId));
    }

    protected function categoryDefinedQuantity(int $categoryId): float
    {
        $category = $this->selectedServiceCategories->firstWhere('id', $categoryId);

        return $category ? (float) $category->quantity : 0;
    }

    protected function firstExistingDeliveryAllocationShortfall(Service $service, Collection $workerRows): ?array
    {
        $proposedQuantities = $workerRows
            ->mapWithKeys(fn (array $row): array => [
                $this->workerCategoryKey((int) $row['worker_id'], (int) $row['category_id']) => (float) $row['quantity'],
            ]);

        $deliveredRows = ServiceDelivery::query()
            ->selectRaw('social_worker_id, service_category_id, SUM(delivered_quantity) as delivered_quantity')
            ->where('service_id', $service->id)
            ->groupBy('social_worker_id', 'service_category_id')
            ->get();

        foreach ($deliveredRows as $deliveredRow) {
            $workerId = (int) $deliveredRow->social_worker_id;
            $categoryId = (int) $deliveredRow->service_category_id;
            $deliveredQuantity = (float) $deliveredRow->delivered_quantity;
            $proposedQuantity = (float) ($proposedQuantities->get($this->workerCategoryKey($workerId, $categoryId)) ?? 0);

            if ($proposedQuantity < $deliveredQuantity) {
                return [
                    'worker_id' => $workerId,
                    'category_id' => $categoryId,
                    'delivered_quantity' => $deliveredQuantity,
                    'proposed_quantity' => $proposedQuantity,
                ];
            }
        }

        return null;
    }

    protected function workerCategoryKey(int $workerId, int $categoryId): string
    {
        return $workerId.':'.$categoryId;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->selectedWorkers as $worker) {
            foreach ($this->selectedServiceCategories as $category) {
                $attributes['allocations.'.$worker->id.'.'.$category->id] = $worker->full_name.' - '.$category->name;
            }
        }

        return $attributes;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    public function render()
    {
        return view('livewire.services.service-delivery-manager', [
            'services' => Service::query()
                ->with([
                    'serviceName',
                    'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id'),
                    'workerAllocations.socialWorker',
                    'workerAllocations.serviceCategory',
                ])
                ->supportsHomeDelivery()
                ->whereIn('status', ['approved', 'in_distribution', 'completed'])
                ->latest()
                ->get(),
            'availableSocialWorkers' => $this->availableSocialWorkers,
            'selectedWorkers' => $this->selectedWorkers,
        ]);
    }
}
