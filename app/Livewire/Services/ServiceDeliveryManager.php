<?php

namespace App\Livewire\Services;

use App\Models\Service;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ServiceDeliveryManager extends Component
{
    public ?int $selectedServiceId = null;
    public array $selectedWorkerIds = [];
    public array $allocations = [];
    public string $socialWorkerSearch = '';
    public bool $showSavedSummary = false;

    public function updatedSelectedServiceId(): void
    {
        $this->refreshSelectedServiceState();
        $this->showSavedSummary = false;
        $this->resetValidation();
    }

    public function addSocialWorker(int $workerId): void
    {
        $worker = SocialWorker::query()->find($workerId);

        if (! $worker) {
            return;
        }

        if (! in_array($workerId, $this->selectedWorkerIds, true)) {
            $this->selectedWorkerIds[] = $workerId;
        }

        foreach ($this->selectedServiceCategories as $category) {
            $this->allocations[$workerId][(int) $category->id] = $this->allocations[$workerId][(int) $category->id] ?? $this->formatDecimal(0);
        }

        $this->socialWorkerSearch = '';
        $this->resetValidation();
    }

    public function removeSocialWorker(int $workerId): void
    {
        $service = $this->selectedService;

        if ($service && $service->deliveries()->where('social_worker_id', $workerId)->exists()) {
            $this->addError('selectedWorkerIds', 'مددکاری که تحویل خدمت ثبت‌شده دارد قابل حذف نیست.');
            return;
        }

        $this->selectedWorkerIds = array_values(array_filter(
            $this->selectedWorkerIds,
            fn (int $selectedId) => $selectedId !== $workerId
        ));

        unset($this->allocations[$workerId]);
        $this->resetValidation();
    }

    public function saveAllocations(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $categoryIds = $this->selectedServiceCategories->pluck('id')->map(fn ($id) => (int) $id);
        $rules = [
            'selectedWorkerIds' => ['array'],
            'selectedWorkerIds.*' => ['integer', Rule::exists('social_workers', 'id')],
        ];

        foreach ($this->selectedWorkerIds as $workerId) {
            foreach ($categoryIds as $categoryId) {
                $rules['allocations.' . $workerId . '.' . $categoryId] = ['nullable', 'numeric', 'min:0'];
            }
        }

        $validated = $this->validate($rules, [], $this->validationAttributes());
        $selectedWorkerIds = collect($validated['selectedWorkerIds'] ?? [])
            ->map(fn ($workerId) => (int) $workerId)
            ->unique()
            ->values();

        $lockedWorkerIds = $service->deliveries()
            ->pluck('social_worker_id')
            ->unique()
            ->diff($selectedWorkerIds);

        if ($lockedWorkerIds->isNotEmpty()) {
            $this->addError('selectedWorkerIds', 'مددکارانی که تحویل ثبت‌شده دارند باید در فهرست باقی بمانند.');
            return;
        }

        $totalAllocated = $selectedWorkerIds
            ->sum(fn ($workerId) => collect($categoryIds)
                ->sum(fn ($categoryId) => (float) ($validated['allocations'][$workerId][$categoryId] ?? 0)));

        if ($totalAllocated > (float) $service->total_quantity) {
            $this->addError('allocations_total', 'مجموع سهمیه مددکاران نمی‌تواند از تعداد کل خدمت بیشتر باشد.');
            return;
        }

        foreach ($categoryIds as $categoryId) {
            $categoryAllocated = $selectedWorkerIds
                ->sum(fn ($workerId) => (float) ($validated['allocations'][$workerId][$categoryId] ?? 0));

            if ($categoryAllocated > $this->categoryDefinedQuantity((int) $categoryId)) {
                $this->addError('allocations_total', 'مجموع سهمیه یک آیتم از موجودی کل همان آیتم بیشتر است.');
                return;
            }

            foreach ($selectedWorkerIds as $workerId) {
                $allocated = (float) ($validated['allocations'][$workerId][$categoryId] ?? 0);
                $alreadyDelivered = $service->deliveredQuantityForWorkerCategory($workerId, (int) $categoryId);

                if ($allocated < $alreadyDelivered) {
                    $this->addError('allocations.' . $workerId . '.' . $categoryId, 'سهمیه نمی‌تواند کمتر از مقدار تحویل‌شده فعلی این مددکار برای این آیتم باشد.');
                    return;
                }
            }
        }

        DB::transaction(function () use ($service, $selectedWorkerIds, $categoryIds, $validated): void {
            $service->workerAllocations()
                ->whereNotIn('social_worker_id', $selectedWorkerIds->all())
                ->delete();

            $service->workerAllocations()
                ->whereIn('social_worker_id', $selectedWorkerIds->all())
                ->whereNotIn('service_category_id', $categoryIds->all())
                ->delete();

            foreach ($selectedWorkerIds as $workerId) {
                foreach ($categoryIds as $categoryId) {
                    ServiceWorkerAllocation::query()->updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'social_worker_id' => $workerId,
                            'service_category_id' => $categoryId,
                        ],
                        [
                            'allocated_quantity' => (float) ($validated['allocations'][$workerId][$categoryId] ?? 0),
                        ]
                    );
                }
            }
        });

        $this->showSavedSummary = true;
        $this->refreshSelectedServiceState();
        $this->dispatch('quota-saved');
    }

    public function enableEditing(): void
    {
        $this->refreshSelectedServiceState();
        $this->showSavedSummary = false;
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with(['serviceName', 'categories', 'workerAllocations.serviceCategory', 'workerAllocations.socialWorker'])
            ->find($this->selectedServiceId);
    }

    public function render()
    {
        return view('livewire.services.service-delivery-manager', [
            'services' => Service::query()
                ->with(['serviceName', 'categories', 'workerAllocations.socialWorker'])
                ->whereIn('status', ['approved', 'in_distribution', 'completed'])
                ->latest()
                ->get(),
            'availableSocialWorkers' => $this->availableSocialWorkers,
            'selectedWorkers' => $this->selectedWorkers,
        ]);
    }

    protected function loadAllocations(): void
    {
        $service = $this->selectedService;
        $this->allocations = [];
        $this->selectedWorkerIds = [];
        $this->socialWorkerSearch = '';

        if (! $service) {
            return;
        }

        foreach ($service->workerAllocations->groupBy('social_worker_id') as $workerId => $workerAllocations) {
            $this->selectedWorkerIds[] = (int) $workerId;

            foreach ($this->selectedServiceCategories as $category) {
                $allocation = $workerAllocations->firstWhere('service_category_id', (int) $category->id);
                $this->allocations[(int) $workerId][(int) $category->id] = $this->formatDecimal($allocation?->allocated_quantity ?? 0);
            }
        }
    }

    protected function refreshSelectedServiceState(): void
    {
        unset($this->selectedService);
        $this->loadAllocations();
    }

    public function getSelectedWorkersProperty()
    {
        if ($this->selectedWorkerIds === []) {
            return collect();
        }

        $workers = SocialWorker::query()
            ->whereIn('id', $this->selectedWorkerIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return collect($this->selectedWorkerIds)
            ->map(fn (int $workerId) => $workers->firstWhere('id', $workerId))
            ->filter();
    }

    public function getAvailableSocialWorkersProperty()
    {
        $search = trim($this->socialWorkerSearch);

        return SocialWorker::query()
            ->whereNotIn('id', $this->selectedWorkerIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('worker_code', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function getSelectedServiceCategoriesProperty()
    {
        return $this->selectedService?->categories?->sortBy('sort_id')->values() ?? collect();
    }

    public function getCurrentAllocatedTotalProperty(): float
    {
        return collect($this->selectedWorkerIds)
            ->sum(fn (int $workerId) => $this->allocationForWorker($workerId));
    }

    public function getRemainingAssignableQuantityProperty(): float
    {
        $service = $this->selectedService;

        if (! $service) {
            return 0;
        }

        return max(0, (float) $service->total_quantity - $this->currentAllocatedTotal);
    }

    public function allocationForWorker(int $workerId): float
    {
        return collect($this->allocations[$workerId] ?? [])->sum(fn ($quantity) => (float) $quantity);
    }

    public function allocationForCategory(int $categoryId): float
    {
        return collect($this->selectedWorkerIds)
            ->sum(fn (int $workerId) => (float) ($this->allocations[$workerId][$categoryId] ?? 0));
    }

    public function remainingAssignableForCategory(int $categoryId): float
    {
        return max(0, $this->categoryDefinedQuantity($categoryId) - $this->allocationForCategory($categoryId));
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    protected function validationAttributes(): array
    {
        $service = $this->selectedService;
        $attributes = [];

        if (! $service) {
            return $attributes;
        }

        foreach ($this->selectedWorkers as $worker) {
            foreach ($this->selectedServiceCategories as $category) {
                $attributes['allocations.' . $worker->id . '.' . $category->id] = 'سهمیه ' . $worker->full_name . ' - ' . $category->name;
            }
        }

        return $attributes;
    }

    protected function categoryDefinedQuantity(int $categoryId): float
    {
        $category = $this->selectedServiceCategories->firstWhere('id', $categoryId);

        if (! $category) {
            return 0;
        }

        return (float) $category->quantity;
    }
}
