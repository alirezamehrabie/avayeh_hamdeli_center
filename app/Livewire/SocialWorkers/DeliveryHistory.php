<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.social-worker')]
class DeliveryHistory extends Component
{
    use InteractsWithNotificationModal;
    use WithPagination;

    public string $activeSection = 'delivery-history';

    public mixed $selectedServiceId = null;

    public string $deliverySearch = '';

    public string $deliveryDateFrom = '';

    public string $deliveryDateTo = '';

    public bool $showEditDeliveryModal = false;

    public string $editingDeliveryBatchKey = '';

    public string $editRecipientName = '';

    public string $editRecipientNationalId = '';

    public string $editRecipientType = '';

    public string $editDeliveredAt = '';

    public array $editItems = [];

    public string $deliveryUpdateMessage = '';

    protected ?array $unitOptionsCache = null;

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

        $deliveries = $selectedService
            ? $this->deliveries($socialWorkerId, $selectedService->id)
            : null;

        $deliveryGroups = $deliveries
            ? $this->deliveryGroups($deliveries->getCollection(), $socialWorkerId, $selectedService->id)
            : collect();

        return view('livewire.social-workers.delivery-history', [
            'services' => $this->services($socialWorkerId),
            'selectedService' => $selectedService,
            'workerRemainingAllocation' => $selectedService
                ? $this->workerRemainingAllocation($socialWorkerId, $selectedService->id)
                : null,
            'deliveries' => $deliveries,
            'deliveryGroups' => $deliveryGroups,
            'recipientGroups' => $this->recipientGroups($deliveryGroups),
        ]);
    }

    public function editDeliveryItem(int $deliveryId): void
    {
        $this->editDeliveryBatch('item-'.$deliveryId);
    }

    public function editDeliveryCategory(int $deliveryId): void
    {
        $this->editDeliveryBatch('category-'.$deliveryId);
    }

    /**
     * A category-group spans the recipient's whole history for one category
     * (several batches/dates), so batch atomicity checks do not apply —
     * every row must simply be individually editable.
     */
    protected function batchRowsAreEditable(string $batchKey, Collection $rows): bool
    {
        if (str_starts_with($batchKey, 'category-')) {
            return $rows->isNotEmpty()
                && $rows->every(fn (ServiceDelivery $row): bool => $this->deliveryRowsAreEditable(collect([$row])));
        }

        return $this->deliveryRowsAreEditable($rows);
    }

    public function editDeliveryBatch(string $batchKey): void
    {
        $rows = $this->deliveryBatchRows($batchKey);

        abort_if($rows->isEmpty(), 404);
        abort_unless($this->batchRowsAreEditable($batchKey, $rows), 403);

        $recipient = $rows->last();

        $this->editingDeliveryBatchKey = $batchKey;
        $this->editRecipientName = $recipient->recipient_name;
        $this->editRecipientNationalId = $recipient->recipient_national_id;
        $this->editRecipientType = $recipient->person_id
            ? 'مددجو'
            : ($recipient->guardian_id ? 'سرپرست خانوار' : 'گیرنده ثبت‌نشده');
        $this->editDeliveredAt = $this->formatLastDeliveryDate($recipient->delivered_at);
        $items = $rows
            ->map(fn (ServiceDelivery $delivery): array => [
                'id' => $delivery->id,
                'category' => $delivery->serviceCategory?->name ?: '-',
                'unit' => $this->formatUnitLabel($delivery->serviceCategory?->unit),
                'quantity' => $this->formatEditableQuantity($delivery->delivered_quantity),
                'decimal' => Service::unitUsesDecimalPrecision($delivery->serviceCategory?->unit),
                'value_per_unit' => (int) $delivery->value_per_unit_snapshot,
            ])
            ->values();

        if (str_starts_with($batchKey, 'category-')) {
            $total = (float) $rows->sum(fn (ServiceDelivery $row): float => (float) $row->delivered_quantity);
            $aggregate = $items->last();
            $aggregate['quantity'] = $this->formatEditableQuantity($total);
            $items = collect([$aggregate]);
        }

        $this->editItems = $items->all();
        $this->deliveryUpdateMessage = '';
        $this->resetValidation();
        $this->showEditDeliveryModal = true;
    }

    public function saveDeliveryBatch(): void
    {
        $validated = $this->validate([
            'editItems' => ['required', 'array', 'min:1'],
            'editItems.*.id' => ['required', 'integer'],
            'editItems.*.quantity' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999.99',
            ],
        ], [], [
            'editItems.*.quantity' => 'مقدار تحویل',
        ]);

        $submittedItems = collect($validated['editItems'])
            ->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (float) $item['quantity']]);

        $quantityFieldIndexes = [];
        foreach ($validated['editItems'] as $index => $item) {
            $quantityFieldIndexes[(int) $item['id']] = (int) $index;
        }

        DB::transaction(function () use ($submittedItems, $quantityFieldIndexes): void {
            $rows = $this->deliveryBatchRows($this->editingDeliveryBatchKey, true);

            abort_if($rows->isEmpty(), 404);
            abort_unless($this->batchRowsAreEditable($this->editingDeliveryBatchKey, $rows), 403);

            $expectedIds = str_starts_with($this->editingDeliveryBatchKey, 'category-')
                ? collect([(int) $rows->last()->id])
                : $rows->pluck('id')->map(fn ($id) => (int) $id);
            $expectedIds = $expectedIds->sort()->values();
            $submittedIds = $submittedItems->keys()->map(fn ($id) => (int) $id)->sort()->values();

            abort_unless($expectedIds->all() === $submittedIds->all(), 422);

            $categoryIds = $rows->pluck('service_category_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $categories = ServiceCategory::query()
                ->where('service_id', (int) $rows->first()->service_id)
                ->whereIn('id', $categoryIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $allocations = ServiceWorkerAllocation::query()
                ->where('service_id', (int) $rows->first()->service_id)
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->whereIn('service_category_id', $categoryIds)
                ->lockForUpdate()
                ->get()
                ->groupBy('service_category_id');

            foreach ($rows->groupBy('service_category_id') as $categoryId => $categoryRows) {
                $categoryId = (int) $categoryId;
                $category = $categories->get($categoryId);

                if (! $category) {
                    throw ValidationException::withMessages([
                        'editItems' => 'یکی از دسته‌بندی‌های این تحویل دیگر در دسترس نیست.',
                    ]);
                }

                if (! Service::unitUsesDecimalPrecision($category->unit)) {
                    foreach ($categoryRows as $row) {
                        if (fmod((float) ($submittedItems[(int) $row->id] ?? 0), 1.0) !== 0.0) {
                            throw ValidationException::withMessages([
                                'editItems.'.$quantityFieldIndexes[(int) $row->id].'.quantity' => "مقدار «{$category->name}» باید عدد صحیح باشد.",
                            ]);
                        }
                    }
                }

                $newBatchQuantity = $categoryRows->sum(
                    fn (ServiceDelivery $row): float => (float) ($submittedItems[(int) $row->id] ?? 0)
                );
                $otherDeliveredQuantity = (float) ServiceDelivery::query()
                    ->where('service_id', (int) $rows->first()->service_id)
                    ->where('service_category_id', $categoryId)
                    ->whereNotIn('id', $rows->pluck('id'))
                    ->sum('delivered_quantity');

                if ($otherDeliveredQuantity + $newBatchQuantity > (float) $category->quantity) {
                    throw ValidationException::withMessages([
                        'editItems' => "مقدار جدید «{$category->name}» از موجودی این دسته‌بندی بیشتر است.",
                    ]);
                }

                $allocatedQuantity = (float) ($allocations->get($categoryId)?->sum('allocated_quantity') ?? 0);
                $otherWorkerDeliveredQuantity = (float) ServiceDelivery::query()
                    ->where('service_id', (int) $rows->first()->service_id)
                    ->where('service_category_id', $categoryId)
                    ->where('social_worker_id', $this->currentSocialWorkerId())
                    ->whereNotIn('id', $rows->pluck('id'))
                    ->sum('delivered_quantity');

                if ($otherWorkerDeliveredQuantity + $newBatchQuantity > $allocatedQuantity) {
                    throw ValidationException::withMessages([
                        'editItems' => "مقدار جدید «{$category->name}» از سهمیه تخصیص‌یافته شما بیشتر است.",
                    ]);
                }
            }

            foreach ($rows as $row) {
                // Category groups save the total once: the latest row carries
                // the new value, the remaining rows of the group are zeroed.
                $quantity = (float) ($submittedItems[(int) $row->id] ?? 0);

                $row->forceFill([
                    'delivered_quantity' => $quantity,
                    'delivered_total_value' => (int) round($quantity * (int) $row->value_per_unit_snapshot),
                    'updated_by' => auth()->id(),
                    'corrected_at' => now(),
                ])->saveQuietly();
            }

            $rows->first()->service?->refreshDeliveryProgress();
        });

        $this->closeEditDeliveryModal();
        $this->deliveryUpdateMessage = 'مقادیر تحویل با موفقیت اصلاح شد.';
        $this->dispatch('delivery-history-updated');
    }

    public function closeEditDeliveryModal(): void
    {
        $this->showEditDeliveryModal = false;
        $this->editingDeliveryBatchKey = '';
        $this->editRecipientName = '';
        $this->editRecipientNationalId = '';
        $this->editRecipientType = '';
        $this->editDeliveredAt = '';
        $this->editItems = [];
        $this->resetValidation();
    }

    public function openZeroCategoryConfirmation(int $deliveryId): void
    {
        $anchor = $this->deliveryBatchRows('item-'.$deliveryId)->first();

        abort_if(! $anchor, 404);
        abort_unless($this->deliveryRowsAreEditable(collect([$anchor])), 403);

        $targets = $this->recipientCategoryRows($anchor);
        $totalQuantity = $targets->sum(fn (ServiceDelivery $row): float => (float) $row->delivered_quantity);
        $categoryName = $anchor->serviceCategory?->name ?: '-';
        $totalLabel = $this->formatQuantityForUnit($totalQuantity, $anchor->serviceCategory?->unit);

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'صفر کردن مقدار تحویل',
            'message' => "همه ثبت‌های دسته‌بندی «{$categoryName}» برای «{$anchor->recipient_name}» در این خدمت (مجموعاً {$totalLabel}) به صفر تغییر خواهد کرد. رکورد حفظ می‌شود و فقط مقدار صفر ثبت می‌گردد.",
            'icon' => 'warning',
            'buttons' => [
                [
                    'label' => 'صفر کردن مقدار',
                    'action' => 'event',
                    'event' => 'confirm-zero-delivery-category',
                    'payload' => ['deliveryId' => $deliveryId],
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

    #[On('confirm-zero-delivery-category')]
    public function zeroDeliveryCategory(int $deliveryId): void
    {
        $anchor = $this->deliveryBatchRows('item-'.$deliveryId)->first();

        abort_if(! $anchor, 404);
        abort_unless($this->deliveryRowsAreEditable(collect([$anchor])), 403);

        DB::transaction(function () use ($anchor): void {
            $targets = $this->recipientCategoryRows($anchor, true);

            abort_if($targets->isEmpty(), 404);

            foreach ($targets as $row) {
                $row->forceFill([
                    'delivered_quantity' => 0,
                    'delivered_total_value' => 0,
                    'updated_by' => auth()->id(),
                    'corrected_at' => now(),
                ])->saveQuietly();
            }

            $anchor->service?->refreshDeliveryProgress();
        });

        $this->deliveryUpdateMessage = 'مقدار تحویل این دسته‌بندی برای گیرنده صفر شد.';
        $this->dispatch('delivery-history-updated');
    }

    /**
     * Editable rows of the same category held by the same recipient (the
     * person_id / guardian_id / national_id identity used across this page).
     */
    protected function recipientCategoryRows(ServiceDelivery $anchor, bool $lockForUpdate = false): Collection
    {
        $rows = ServiceDelivery::query()
            ->with(['serviceCategory', 'person', 'guardian', 'service'])
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->where('service_id', (int) $this->normalizedSelectedServiceId())
            ->where('service_category_id', (int) $anchor->service_category_id)
            ->when(
                $anchor->person_id,
                fn (Builder $query) => $query->where('person_id', $anchor->person_id),
                fn (Builder $query) => $anchor->guardian_id
                    ? $query->where('guardian_id', $anchor->guardian_id)
                    : $query->whereNull('person_id')->whereNull('guardian_id')->where('national_id', $anchor->national_id)
            )
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->orderBy('id')
            ->get();

        return $rows->filter(
            fn (ServiceDelivery $row): bool => $this->deliveryRowsAreEditable(collect([$row]))
        );
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

    protected function workerRemainingAllocation(int $socialWorkerId, int $serviceId): ?array
    {
        $allocations = ServiceWorkerAllocation::query()
            ->where('service_id', $serviceId)
            ->where('social_worker_id', $socialWorkerId)
            ->get()
            ->groupBy('service_category_id')
            ->map(fn (Collection $rows): float => (float) $rows->sum('allocated_quantity'));

        if ($allocations->isEmpty()) {
            return null;
        }

        $delivered = ServiceDelivery::query()
            ->where('service_id', $serviceId)
            ->where('social_worker_id', $socialWorkerId)
            ->whereIn('service_category_id', $allocations->keys())
            ->groupBy('service_category_id')
            ->selectRaw('service_category_id, SUM(delivered_quantity) as total_delivered')
            ->pluck('total_delivered', 'service_category_id');

        $remaining = $allocations->map(
            fn (float $allocated, $categoryId): float => max(
                $allocated - (float) ($delivered[$categoryId] ?? 0),
                0
            )
        );

        $units = ServiceCategory::query()
            ->whereIn('id', $allocations->keys())
            ->pluck('unit', 'id')
            ->map(fn ($unit): string => trim((string) $unit));

        return [
            'total' => (float) $remaining->sum(),
            'unit' => $units->unique()->count() === 1 ? $units->first() : null,
        ];
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
        $batchAnchorIds = ServiceDelivery::query()
            ->selectRaw('MIN(id)')
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_id', $serviceId)
            ->whereNotNull('delivery_batch_id')
            ->groupBy('delivery_batch_id');

        return $this->deliveryQuery($socialWorkerId, $serviceId)
            ->where(function (Builder $query) use ($batchAnchorIds): void {
                $query
                    ->whereNull('delivery_batch_id')
                    ->orWhereIn('id', $batchAnchorIds);
            })
            ->latest('delivered_at')
            ->latest('id')
            ->paginate(25);
    }

    protected function deliveryQuery(int $socialWorkerId, int $serviceId): Builder
    {
        $search = trim($this->deliverySearch);
        $dateFrom = $this->normalizedDateInput($this->deliveryDateFrom);
        $dateTo = $this->normalizedDateInput($this->deliveryDateTo);

        return ServiceDelivery::query()
            ->with(['service.serviceName', 'serviceCategory', 'person', 'guardian'])
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
            ->when($dateTo, fn ($query) => $query->whereDate('delivered_at', '<=', $dateTo));
    }

    protected function deliveryGroups(
        Collection $anchors,
        int $socialWorkerId,
        int $serviceId
    ): Collection {
        if ($anchors->isEmpty()) {
            return collect();
        }

        $batchIds = $anchors->pluck('delivery_batch_id')->filter()->unique()->values();
        $legacyIds = $anchors->whereNull('delivery_batch_id')->pluck('id')->values();
        $items = ServiceDelivery::query()
            ->with(['serviceCategory', 'person', 'guardian'])
            ->where('social_worker_id', $socialWorkerId)
            ->where('service_id', $serviceId)
            ->where(function (Builder $query) use ($batchIds, $legacyIds): void {
                if ($batchIds->isNotEmpty()) {
                    $query->whereIn('delivery_batch_id', $batchIds);
                }

                if ($legacyIds->isNotEmpty()) {
                    $method = $batchIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('id', $legacyIds);
                }
            })
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ServiceDelivery $delivery): string => $this->deliveryBatchKey($delivery));

        return $anchors->map(function (ServiceDelivery $anchor) use ($items): array {
            $batchKey = $this->deliveryBatchKey($anchor);
            $batchItems = $items->get($batchKey, collect());

            return [
                'batch_key' => $batchKey,
                'recipient' => $anchor,
                'items' => $batchItems,
                'can_edit' => $this->deliveryRowsAreEditable($batchItems),
                'editable_item_ids' => $batchItems
                    ->filter(fn (ServiceDelivery $delivery): bool => $this->deliveryRowsAreEditable(collect([$delivery])))
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
            ];
        });
    }

    protected function recipientGroups(Collection $deliveryGroups): Collection
    {
        return $deliveryGroups
            ->groupBy(fn (array $group): string => $this->recipientKey($group['recipient']))
            ->map(function (Collection $groups, string $recipientKey): array {
                $items = $groups->flatMap(function (array $group): Collection {
                    return $group['items']->map(fn (ServiceDelivery $delivery): array => [
                        'delivery' => $delivery,
                        'quantity' => (float) $delivery->delivered_quantity,
                        'can_edit' => in_array((int) $delivery->id, $group['editable_item_ids'], true),
                    ]);
                })->values();

                // Same category listed several times (one line per batch/date)
                // collapses into a single row whose quantity is the true total
                // for this recipient — a display/summary aggregate only; no
                // delivery record is modified or merged. Non-editable rows
                // (gate/attendance linked) keep their own line, as before.
                $totalsByCategory = [];
                $latestRowByCategory = [];

                foreach ($items as $item) {
                    if (! $item['can_edit']) {
                        continue;
                    }

                    $categoryId = (int) $item['delivery']->service_category_id;
                    $totalsByCategory[$categoryId] = ($totalsByCategory[$categoryId] ?? 0.0) + (float) $item['quantity'];

                    if (! isset($latestRowByCategory[$categoryId]) || (int) $item['delivery']->id > (int) $latestRowByCategory[$categoryId]->id) {
                        $latestRowByCategory[$categoryId] = $item['delivery'];
                    }
                }

                $aggregatedItems = [];
                $emittedCategories = [];

                foreach ($items as $item) {
                    if (! $item['can_edit']) {
                        $aggregatedItems[] = $item;
                        continue;
                    }

                    $categoryId = (int) $item['delivery']->service_category_id;

                    if (isset($emittedCategories[$categoryId])) {
                        continue;
                    }

                    $emittedCategories[$categoryId] = true;

                    $aggregatedItems[] = [
                        'delivery' => $latestRowByCategory[$categoryId],
                        'quantity' => $totalsByCategory[$categoryId],
                        'can_edit' => true,
                    ];
                }

                return [
                    'recipient_key' => $recipientKey,
                    'recipient' => $groups->first()['recipient'],
                    'delivery_groups' => $groups->values(),
                    'items' => collect($aggregatedItems),
                ];
            })
            ->values();
    }

    protected function deliveryBatchRows(string $batchKey, bool $lockForUpdate = false): Collection
    {
        if (str_starts_with($batchKey, 'category-')) {
            $anchor = ServiceDelivery::query()
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->where('service_id', (int) $this->normalizedSelectedServiceId())
                ->whereKey((int) substr($batchKey, 9))
                ->first();

            if (! $anchor) {
                return collect();
            }

            return $this->recipientCategoryRows($anchor, $lockForUpdate);
        }

        $query = ServiceDelivery::query()
            ->with(['serviceCategory', 'person', 'guardian', 'service'])
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->where('service_id', (int) $this->normalizedSelectedServiceId());

        if (str_starts_with($batchKey, 'batch-')) {
            $query->where('delivery_batch_id', substr($batchKey, 6));
        } elseif (preg_match('/^legacy-(\d+)$/', $batchKey, $matches) === 1) {
            $query->whereKey((int) $matches[1])->whereNull('delivery_batch_id');
        } elseif (preg_match('/^item-(\d+)$/', $batchKey, $matches) === 1) {
            $query->whereKey((int) $matches[1]);
        } else {
            return collect();
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->orderBy('id')->get();
    }

    protected function deliveryRowsAreEditable(Collection $rows): bool
    {
        return $rows->isNotEmpty()
            && Gate::allows('edit-social-worker-deliveries')
            && $rows->pluck('service_id')->unique()->count() === 1
            && $rows->map(fn (ServiceDelivery $delivery): string => $this->recipientKey($delivery))->unique()->count() === 1
            && $rows->map(fn (ServiceDelivery $delivery): string => $delivery->delivered_at?->toDateString() ?? '')->unique()->count() === 1
            && $rows->every(fn (ServiceDelivery $delivery): bool => (
                (int) $delivery->social_worker_id === $this->currentSocialWorkerId()
                && ($delivery->delivery_channel ?: Service::DELIVERY_CHANNEL_HOME) === Service::DELIVERY_CHANNEL_HOME
                && ! $delivery->gate_entry_assignment_id
                && ! $delivery->activity_attendance_id
            ));
    }

    protected function recipientKey(ServiceDelivery $delivery): string
    {
        if ($delivery->person_id) {
            return 'person-'.$delivery->person_id;
        }

        if ($delivery->guardian_id) {
            return 'guardian-'.$delivery->guardian_id;
        }

        return 'national-'.$delivery->recipient_national_id;
    }

    protected function deliveryBatchKey(ServiceDelivery $delivery): string
    {
        return $delivery->delivery_batch_id
            ? 'batch-'.$delivery->delivery_batch_id
            : 'legacy-'.$delivery->id;
    }

    protected function currentSocialWorkerId(): int
    {
        return (int) auth()->user()?->social_worker_id;
    }

    protected function formatEditableQuantity(mixed $value): string
    {
        $quantity = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($quantity, '0'), '.');
    }

    protected function normalizedDateInput(string $date): ?string
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date) === 1) {
            try {
                return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeJalaliDate(string $date): string
    {
        $date = strtr($date, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);

        [$year, $month, $day] = array_map('intval', explode('/', $date));

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    public function formatLastDeliveryDate(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        return $this->persianNumber(Jalalian::fromDateTime($date)->format('Y/m/d'));
    }

    public function formatQuantity(mixed $value): string
    {
        return $this->persianNumber(number_format((float) $value, 2));
    }

    public function formatQuantityForUnit(mixed $value, mixed $unit): string
    {
        return $this->persianNumber(Service::formatQuantityForUnit($value, (string) $unit));
    }

    public function formatCurrency(mixed $value): string
    {
        return $this->persianNumber(number_format((int) $value)).' ریال';
    }

    public function formatUnitLabel(mixed $unit): string
    {
        $unit = trim((string) $unit);

        if ($unit === '') {
            return '';
        }

        $unitOptions = $this->unitOptionsCache ??= Service::unitOptions();

        return (string) ($unitOptions[$unit] ?? $unit);
    }

    public function persianNumber(mixed $value): string
    {
        return strtr((string) $value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
            ',' => '٬',
        ]);
    }
}
