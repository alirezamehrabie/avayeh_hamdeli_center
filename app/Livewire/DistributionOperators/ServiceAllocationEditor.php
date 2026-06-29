<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ServiceAllocationEditor extends Component
{
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
            ->where('assigned_by_user_id', auth()->id())
            ->pluck('social_worker_id')
            ->all();

        $workers = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'mobile', 'district_id'])
            ->with('district:id,name')
            ->whereNotIn('id', $existingWorkerIds)
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

        DB::transaction(function () use ($workerId): void {
            $categories = $this->service->categories()->lockForUpdate()->get();

            foreach ($categories as $category) {
                $existing = ServiceWorkerAllocation::query()
                    ->where('service_id', $this->service->id)
                    ->where('service_category_id', $category->id)
                    ->where('social_worker_id', $workerId)
                    ->first();

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
        $allocation = ServiceWorkerAllocation::query()->find($allocationId);

        if (! $allocation || (int) $allocation->assigned_by_user_id !== (int) auth()->id()) {
            return;
        }

        $category = $this->service->categories()->find($allocation->service_category_id);

        if (! $category) {
            return;
        }

        $totalAllocatedForCategory = ServiceWorkerAllocation::query()
            ->where('service_id', $this->service->id)
            ->where('service_category_id', $category->id)
            ->where('id', '!=', $allocationId)
            ->sum('allocated_quantity');

        $maxAllowed = max(0, (float) $category->quantity - (float) $totalAllocatedForCategory);

        $quantity = min($quantity, $maxAllowed);

        $allocation->update(['allocated_quantity' => $quantity]);

        $this->loadService();
        $this->loadExistingAllocations();
    }

    public function removeWorkerAllocations(int $socialWorkerId): void
    {
        ServiceWorkerAllocation::query()
            ->where('service_id', $this->editingServiceId)
            ->where('social_worker_id', $socialWorkerId)
            ->where('assigned_by_user_id', auth()->id())
            ->delete();

        $this->loadService();
        $this->loadExistingAllocations();
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
            ? $this->service->workerAllocations()
                ->where('assigned_by_user_id', auth()->id())
                ->with('socialWorker')
                ->get()
                ->groupBy('social_worker_id')
            : collect();

        $categoryMetrics = $this->service
            ? $this->buildCategoryMetrics()
            : [];

        return view('livewire.distribution-operators.service-allocation-editor', [
            'service' => $this->service,
            'operatorAllocations' => $operatorAllocations,
            'categoryMetrics' => $categoryMetrics,
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
     * @return array<int, array{quantity: float, allocated: float, assignable: float}>
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

        return $this->service->categories
            ->mapWithKeys(function ($category) use ($allocatedByCategory): array {
                $quantity = (float) $category->quantity;
                $allocated = (float) ($allocatedByCategory[$category->id] ?? 0);

                return [
                    (int) $category->id => [
                        'quantity' => $quantity,
                        'allocated' => $allocated,
                        'assignable' => max(0, $quantity - $allocated),
                    ],
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
