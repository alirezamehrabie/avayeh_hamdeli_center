<?php

namespace App\Livewire\DistributionOperators;

use App\Livewire\DistributionOperators\Concerns\FormatsServiceQuantities;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceAllocationEditor extends Component
{
    use FormatsServiceQuantities;
    use InteractsWithNotificationModal;

    public ?int $editingServiceId = null;

    public bool $confirmingSave = false;

    /**
     * @var array<int, array<int, float>>
     */
    public array $allocationQuantities = [];

    public ?int $addingSocialWorkerId = null;

    public string $socialWorkerQuery = '';

    public string $selectedSocialWorkerCode = '';

    public string $selectedSocialWorkerDisplay = '';

    public bool $showSocialWorkerSuggestions = false;

    public ?Service $service = null;

    public function mount(int $editingServiceId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->editingServiceId = $editingServiceId;
        $this->loadService();
        $this->loadExistingAllocations();
    }

    public function updatedSocialWorkerQuery(mixed $value): void
    {
        $this->socialWorkerQuery = trim((string) $value);
        $this->addingSocialWorkerId = null;
        $this->selectedSocialWorkerCode = '';
        $this->selectedSocialWorkerDisplay = '';
        $this->showSocialWorkerSuggestions = true;

        if ($this->socialWorkerQuery === '') {
            $this->addingSocialWorkerId = null;
        }
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        if (! $this->service) {
            return;
        }

        $existingWorkerIds = $this->service->workerAllocations()
            ->pluck('social_worker_id')
            ->map(fn ($id): int => (int) $id)
            ->unique();

        if ($existingWorkerIds->contains($socialWorkerId)) {
            $this->showSocialWorkerSuggestions = false;

            return;
        }

        $worker = SocialWorker::query()
            ->with('district:id,name')
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'district_id'])
            ->find($socialWorkerId);

        if (! $worker) {
            return;
        }

        $this->addingSocialWorkerId = $worker->id;
        $this->selectedSocialWorkerCode = $worker->worker_code ? (string) $worker->worker_code : '-';
        $this->selectedSocialWorkerDisplay = $this->formatSelectedSocialWorkerDisplay($worker);
        $this->socialWorkerQuery = $this->selectedSocialWorkerDisplay;
        $this->showSocialWorkerSuggestions = false;

        $this->addSocialWorkerAllocation();
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->addingSocialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->selectedSocialWorkerCode = '';
        $this->selectedSocialWorkerDisplay = '';
        $this->showSocialWorkerSuggestions = true;
    }

    public function getSocialWorkerSuggestionsProperty(): Collection
    {
        $query = trim($this->socialWorkerQuery);

        if (! $this->showSocialWorkerSuggestions || mb_strlen($query) < 2 || ! $this->service) {
            return collect();
        }

        $existingWorkerIds = $this->service->workerAllocations()
            ->pluck('social_worker_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $workers = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'mobile', 'district_id'])
            ->with('district:id,name')
            ->where(function (Builder $workerQuery) use ($query): void {
                $workerQuery->where('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%')
                    ->orWhere('worker_code', 'like', $query.'%')
                    ->orWhere('mobile', 'like', $query.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query.'%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$query.'%']);
            })
            ->orderBy('worker_code')
            ->limit(10)
            ->get();

        return $workers->map(fn (SocialWorker $worker): array => [
            'id' => (int) $worker->id,
            'duplicate' => $existingWorkerIds->contains((int) $worker->id),
            'name' => trim($worker->full_name) ?: 'مددکار بدون نام',
            'code' => $worker->worker_code ? (string) $worker->worker_code : '-',
            'mobile' => $worker->mobile ?: '-',
            'district' => $worker->district?->name ?: 'بدون منطقه',
        ])->values();
    }

    public function addSocialWorkerAllocation(): void
    {
        if (! $this->addingSocialWorkerId || ! $this->service) {
            return;
        }

        $workerId = (int) $this->addingSocialWorkerId;

        $existingServiceWorkerAllocation = ServiceWorkerAllocation::query()
            ->where('service_id', $this->service->id)
            ->where('social_worker_id', $workerId)
            ->first();

        if ($existingServiceWorkerAllocation) {
            if ((int) $existingServiceWorkerAllocation->assigned_by_user_id !== (int) auth()->id()) {
                $this->addError('addingSocialWorkerId', 'This worker is already allocated to this service by another operator.');
            }

            $this->addingSocialWorkerId = null;
            $this->socialWorkerQuery = '';
            $this->selectedSocialWorkerCode = '';
            $this->selectedSocialWorkerDisplay = '';
            $this->showSocialWorkerSuggestions = false;

            return;
        }

        DB::transaction(function () use ($workerId): void {
            $categories = $this->service->categories()->lockForUpdate()->get();

            foreach ($categories as $category) {
                $existing = ServiceWorkerAllocation::query()
                    ->where('service_id', $this->service->id)
                    ->where('service_category_id', $category->id)
                    ->where('social_worker_id', $workerId)
                    ->lockForUpdate()
                    ->first();

                if ($existing && (int) $existing->assigned_by_user_id !== (int) auth()->id()) {
                    continue;
                }

                if (! $existing) {
                    ServiceWorkerAllocation::query()->create([
                        'service_id' => $this->service->id,
                        'service_category_id' => $category->id,
                        'social_worker_id' => $workerId,
                        'allocated_quantity' => 0,
                        'assigned_by_user_id' => auth()->id(),
                    ]);
                }
            }
        });

        $this->addingSocialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->selectedSocialWorkerCode = '';
        $this->selectedSocialWorkerDisplay = '';
        $this->showSocialWorkerSuggestions = false;

        $this->loadService();
        $this->loadExistingAllocations();

        $this->dispatch('open-notification-toast', config: [
            'type' => 'success',
            'title' => 'مددکار اضافه شد',
            'message' => 'ردیف تخصیص مددکار اضافه شد. مقدار سهمیه‌ها را در همین صفحه تنظیم کنید.',
            'icon' => 'success',
            'duration' => 4200,
        ]);
    }

    public function updateAllocationQuantity(int $allocationId, string $value): void
    {
        $quantity = max(0, (float) $value);
        $errorMessage = null;

        if (! $this->service) {
            return;
        }

        DB::transaction(function () use ($allocationId, $quantity, &$errorMessage): void {
            $allocation = ServiceWorkerAllocation::query()
                ->whereKey($allocationId)
                ->where('service_id', $this->service->id)
                ->where('assigned_by_user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $allocation) {
                return;
            }

            $category = $this->service->categories()
                ->whereKey($allocation->service_category_id)
                ->lockForUpdate()
                ->first();

            if (! $category) {
                return;
            }

            $deliveredQuantity = (float) ServiceDelivery::query()
                ->where('service_id', $this->service->id)
                ->where('service_category_id', $category->id)
                ->where('social_worker_id', $allocation->social_worker_id)
                ->sum('delivered_quantity');

            if ($quantity < $deliveredQuantity) {
                $errorMessage = 'Allocation cannot be lower than the quantity already delivered for this worker and category.';

                return;
            }

            $totalAllocatedForCategory = ServiceWorkerAllocation::query()
                ->where('service_id', $this->service->id)
                ->where('service_category_id', $category->id)
                ->where('id', '!=', $allocationId)
                ->lockForUpdate()
                ->get()
                ->sum(fn (ServiceWorkerAllocation $allocation): float => (float) $allocation->allocated_quantity);

            $maxAllowed = max(0, (float) $category->quantity - (float) $totalAllocatedForCategory);

            $allocation->update([
                'allocated_quantity' => min($quantity, $maxAllowed),
            ]);
        });

        if ($errorMessage) {
            $this->addError('allocationQuantities', $errorMessage);
        }

        $this->loadService();
        $this->loadExistingAllocations();
    }

    public function removeWorkerAllocations(int $socialWorkerId): void
    {
        $errorMessage = null;

        DB::transaction(function () use ($socialWorkerId, &$errorMessage): void {
            $allocations = ServiceWorkerAllocation::query()
                ->where('service_id', $this->editingServiceId)
                ->where('social_worker_id', $socialWorkerId)
                ->where('assigned_by_user_id', auth()->id())
                ->lockForUpdate()
                ->get();

            if ($allocations->isEmpty()) {
                return;
            }

            $hasAnotherWorkerAllocation = ServiceWorkerAllocation::query()
                ->where('service_id', $this->editingServiceId)
                ->where('assigned_by_user_id', auth()->id())
                ->where('social_worker_id', '!=', $socialWorkerId)
                ->exists();

            if (! $hasAnotherWorkerAllocation) {
                $errorMessage = 'At least one worker allocation must remain so this service stays available for editing.';

                return;
            }

            $hasDeliveries = ServiceDelivery::query()
                ->where('service_id', $this->editingServiceId)
                ->where('social_worker_id', $socialWorkerId)
                ->whereIn('service_category_id', $allocations->pluck('service_category_id')->all())
                ->exists();

            if ($hasDeliveries) {
                $errorMessage = 'This worker has delivered quantities for this service and cannot be removed from allocations.';

                return;
            }

            ServiceWorkerAllocation::query()
                ->whereIn('id', $allocations->pluck('id')->all())
                ->delete();
        });

        $this->service?->refreshDeliveryProgress();

        if ($errorMessage) {
            $this->addError('allocationQuantities', $errorMessage);
        }

        $this->loadService();
        $this->loadExistingAllocations();
    }

    public function confirmRemoveWorkerAllocations(int $socialWorkerId): void
    {
        $workerName = trim((string) $this->service?->workerAllocations()
            ->with('socialWorker:id,first_name,last_name')
            ->where('assigned_by_user_id', auth()->id())
            ->where('social_worker_id', $socialWorkerId)
            ->first()?->socialWorker?->full_name);

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'حذف تخصیص‌های مددکار',
            'message' => $workerName !== ''
                ? "آیا از حذف تمام تخصیص‌های «{$workerName}» مطمئن هستید؟"
                : 'آیا از حذف تمام تخصیص‌های این مددکار مطمئن هستید؟',
            'buttons' => [
                [
                    'label' => 'حذف تخصیص‌ها',
                    'action' => 'event',
                    'event' => 'confirm-remove-worker-allocations',
                    'payload' => ['socialWorkerId' => $socialWorkerId],
                    'variant' => 'danger',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-remove-worker-allocations')]
    public function removeWorkerAllocationsConfirmed(int $socialWorkerId): void
    {
        $this->removeWorkerAllocations($socialWorkerId);
    }

    public function requestSaveConfirmation(): void
    {
        $this->confirmingSave = true;
    }

    public function cancelSaveConfirmation(): void
    {
        $this->confirmingSave = false;
    }

    public function confirmSave()
    {
        $this->confirmingSave = false;

        return redirect()
            ->route('distribution-operator.service-list')
            ->with('distribution-operator-notification', [
                'type' => 'success',
                'title' => 'تخصیص‌ها به‌روزرسانی شد',
                'message' => 'تغییرات سهمیه‌های خدمت ذخیره شد و می‌توانید ادامه کار را از فهرست خدمات انجام دهید.',
                'icon' => 'success',
                'buttons' => [
                    [
                        'label' => 'مشاهده فهرست',
                        'action' => 'close',
                        'variant' => 'success',
                    ],
                ],
            ]);
    }

    public function cancelEditing()
    {
        return redirect()->route('distribution-operator.service-list');
    }

    public function render()
    {
        $operatorAllocations = $this->service
            ? $this->orderedOperatorAllocations()
            : collect();

        $categoryMetrics = $this->service
            ? $this->buildCategoryMetrics()
            : [];

        $workerCategoryDeliveryMetrics = $this->service
            ? $this->buildWorkerCategoryDeliveryMetrics()
            : [];

        return view('livewire.distribution-operators.service-allocation-editor', [
            'service' => $this->service,
            'operatorAllocations' => $operatorAllocations,
            'categoryMetrics' => $categoryMetrics,
            'workerCategoryDeliveryMetrics' => $workerCategoryDeliveryMetrics,
            'unitOptions' => \App\Models\Service::unitOptions(),
            'socialWorkerSuggestions' => $this->showSocialWorkerSuggestions ? $this->socialWorkerSuggestions : collect(),
            'socialWorkerId' => $this->addingSocialWorkerId,
        ]);
    }

    protected function loadService(): void
    {
        $this->service = Service::query()
            ->with(['serviceName', 'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id')])
            ->whereIn('status', ['approved', 'in_distribution'])
            ->where(function (Builder $query): void {
                $query->whereNull('created_by')
                    ->orWhereDoesntHave('creator', fn (Builder $creatorQuery) => $creatorQuery
                        ->where('access_level', \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR));
            })
            ->find($this->editingServiceId);

        if (! $this->service) {
            abort(404);
        }
    }

    protected function loadExistingAllocations(): void
    {
        $this->allocationQuantities = [];

        if (! $this->service) {
            return;
        }

        $operatorAllocations = $this->service->workerAllocations()
            ->where('assigned_by_user_id', auth()->id())
            ->get();

        foreach ($operatorAllocations as $allocation) {
            $workerId = (int) $allocation->social_worker_id;
            $categoryId = (int) $allocation->service_category_id;

            if (! isset($this->allocationQuantities[$workerId])) {
                $this->allocationQuantities[$workerId] = [];
            }

            $this->allocationQuantities[$workerId][$categoryId] = (float) $allocation->allocated_quantity;
        }
    }

    /**
     * @return Collection<int, EloquentCollection<int, ServiceWorkerAllocation>>
     */
    protected function orderedOperatorAllocations(): Collection
    {
        $allocations = $this->service->workerAllocations()
            ->where('assigned_by_user_id', auth()->id())
            ->with('socialWorker')
            ->orderBy('id')
            ->get();

        $workerOrder = $allocations
            ->groupBy('social_worker_id')
            ->map(fn (EloquentCollection $workerAllocations): int => (int) $workerAllocations->min('id'))
            ->sort();

        $groupedAllocations = $allocations->groupBy('social_worker_id');

        return $workerOrder->mapWithKeys(
            fn (int $firstAllocationId, int $workerId): array => [
                $workerId => $groupedAllocations->get($workerId, new EloquentCollection),
            ]
        );
    }

    /**
     * @return array<int, array{quantity: float, allocated: float, delivered: float, remaining_stock: float, assignable: float}>
     */
    protected function buildCategoryMetrics(): array
    {
        if (! $this->service) {
            return [];
        }

        $allocatedByCategory = ServiceWorkerAllocation::query()
            ->select('service_category_id')
            ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
            ->where('service_id', $this->service->id)
            ->groupBy('service_category_id')
            ->pluck('allocated_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->all();

        $deliveredByCategory = ServiceDelivery::query()
            ->select('service_category_id')
            ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
            ->where('service_id', $this->service->id)
            ->groupBy('service_category_id')
            ->pluck('delivered_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->all();

        return $this->service->categories
            ->mapWithKeys(function ($category) use ($allocatedByCategory, $deliveredByCategory): array {
                $quantity = (float) $category->quantity;
                $allocated = (float) ($allocatedByCategory[$category->id] ?? 0);
                $delivered = (float) ($deliveredByCategory[$category->id] ?? 0);
                $remainingStock = max(0, $quantity - $delivered);

                return [
                    (int) $category->id => [
                        'quantity' => $quantity,
                        'allocated' => $allocated,
                        'delivered' => $delivered,
                        'remaining_stock' => $remainingStock,
                        'assignable' => max(0, $quantity - $allocated),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<int, float>>
     */
    protected function buildWorkerCategoryDeliveryMetrics(): array
    {
        if (! $this->service) {
            return [];
        }

        return ServiceDelivery::query()
            ->select('social_worker_id', 'service_category_id')
            ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
            ->where('service_id', $this->service->id)
            ->groupBy('social_worker_id', 'service_category_id')
            ->get()
            ->groupBy('social_worker_id')
            ->mapWithKeys(function (Collection $rows, int|string $workerId): array {
                return [
                    (int) $workerId => $rows
                        ->mapWithKeys(fn ($row): array => [
                            (int) $row->service_category_id => (float) $row->delivered_quantity,
                        ])
                        ->all(),
                ];
            })
            ->all();
    }

    protected function formatSelectedSocialWorkerDisplay(SocialWorker $worker): string
    {
        $name = trim($worker->full_name) ?: 'مددکار بدون نام';
        $district = trim((string) ($worker->district?->name ?? ''));

        return $district !== '' ? $name.' - '.$district : $name;
    }
}
