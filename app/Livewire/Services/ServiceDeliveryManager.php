<?php

namespace App\Livewire\Services;

use App\Models\Service;
use App\Models\SocialWorker;
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

        $this->allocations[$workerId] = $this->allocations[$workerId] ?? $this->formatDecimal(0);
        $this->socialWorkerSearch = '';
        $this->resetValidation();
    }

    public function removeSocialWorker(int $workerId): void
    {
        $service = $this->selectedService;

        if ($service && $service->deliveredQuantityForWorker($workerId) > 0) {
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

        $rules = [
            'selectedWorkerIds' => ['array'],
            'selectedWorkerIds.*' => ['integer', Rule::exists('social_workers', 'id')],
        ];

        foreach ($this->selectedWorkerIds as $workerId) {
            $rules['allocations.' . $workerId] = ['nullable', 'numeric', 'min:0'];
        }

        $validated = $this->validate($rules, [], $this->validationAttributes());
        $selectedWorkerIds = collect($validated['selectedWorkerIds'] ?? [])
            ->map(fn ($workerId) => (int) $workerId)
            ->unique()
            ->values();

        $lockedWorkerIds = $service->socialWorkers
            ->filter(fn ($worker) => $service->deliveredQuantityForWorker($worker->id) > 0)
            ->pluck('id')
            ->diff($selectedWorkerIds);

        if ($lockedWorkerIds->isNotEmpty()) {
            $this->addError('selectedWorkerIds', 'مددکارانی که تحویل ثبت‌شده دارند باید در فهرست باقی بمانند.');
            return;
        }

        $totalAllocated = $selectedWorkerIds
            ->sum(fn ($workerId) => (float) ($validated['allocations'][$workerId] ?? 0));

        if ($totalAllocated > (float) $service->total_quantity) {
            $this->addError('allocations_total', 'مجموع سهمیه مددکاران نمی‌تواند از تعداد کل خدمت بیشتر باشد.');
            return;
        }

        foreach ($selectedWorkerIds as $workerId) {
            $allocated = (float) ($validated['allocations'][$workerId] ?? 0);
            $alreadyDelivered = $service->deliveredQuantityForWorker($workerId);

            if ($allocated < $alreadyDelivered) {
                $this->addError('allocations.' . $workerId, 'سهمیه نمی‌تواند کمتر از مقدار تحویل‌شده فعلی این مددکار باشد.');
                return;
            }
        }

        $syncPayload = [];
        foreach ($selectedWorkerIds as $workerId) {
            $syncPayload[$workerId] = [
                'allocated_quantity' => (float) ($validated['allocations'][$workerId] ?? 0),
            ];
        }

        $service->socialWorkers()->sync($syncPayload);

        $this->showSavedSummary = true;
        session()->flash('success', 'Quotas assigned');
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
            ->with(['serviceName', 'serviceCategory', 'socialWorkers'])
            ->find($this->selectedServiceId);
    }

    public function render()
    {
        return view('livewire.services.service-delivery-manager', [
            'services' => Service::query()
                ->with(['serviceName', 'serviceCategory', 'socialWorkers'])
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

        foreach ($service->socialWorkers as $worker) {
            $this->selectedWorkerIds[] = (int) $worker->id;
            $this->allocations[$worker->id] = $this->formatDecimal($worker->pivot->allocated_quantity ?? 0);
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

    public function getCurrentAllocatedTotalProperty(): float
    {
        return collect($this->selectedWorkerIds)
            ->sum(fn (int $workerId) => (float) ($this->allocations[$workerId] ?? 0));
    }

    public function getRemainingAssignableQuantityProperty(): float
    {
        $service = $this->selectedService;

        if (! $service) {
            return 0;
        }

        return max(0, (float) $service->total_quantity - $this->currentAllocatedTotal);
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
            $attributes['allocations.' . $worker->id] = 'سهمیه ' . $worker->full_name;
        }

        return $attributes;
    }
}
