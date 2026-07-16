<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
            'deliveries' => $deliveries,
            'deliveryGroups' => $deliveryGroups,
            'recipientGroups' => $this->recipientGroups($deliveryGroups),
        ]);
    }

    public function editDeliveryItem(int $deliveryId): void
    {
        $this->editDeliveryBatch('item-'.$deliveryId);
    }

    public function editDeliveryBatch(string $batchKey): void
    {
        $rows = $this->deliveryBatchRows($batchKey);

        abort_if($rows->isEmpty(), 404);
        abort_unless($this->deliveryRowsAreEditable($rows), 403);

        $recipient = $rows->first();

        $this->editingDeliveryBatchKey = $batchKey;
        $this->editRecipientName = $recipient->recipient_name;
        $this->editRecipientNationalId = $recipient->recipient_national_id;
        $this->editRecipientType = $recipient->person_id
            ? 'مددجو'
            : ($recipient->guardian_id ? 'سرپرست خانوار' : 'گیرنده ثبت‌نشده');
        $this->editDeliveredAt = $this->formatLastDeliveryDate($recipient->delivered_at);
        $this->editItems = $rows
            ->map(fn (ServiceDelivery $delivery): array => [
                'id' => $delivery->id,
                'category' => $delivery->serviceCategory?->name ?: '-',
                'unit' => $this->formatUnitLabel($delivery->serviceCategory?->unit),
                'quantity' => $this->formatEditableQuantity($delivery->delivered_quantity),
                'value_per_unit' => (int) $delivery->value_per_unit_snapshot,
            ])
            ->values()
            ->all();
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
                'min:0.01',
                'max:9999999999.99',
            ],
        ], [], [
            'editItems.*.quantity' => 'مقدار تحویل',
        ]);

        $submittedItems = collect($validated['editItems'])
            ->mapWithKeys(fn (array $item): array => [(int) $item['id'] => (float) $item['quantity']]);

        DB::transaction(function () use ($submittedItems): void {
            $rows = $this->deliveryBatchRows($this->editingDeliveryBatchKey, true);

            abort_if($rows->isEmpty(), 404);
            abort_unless($this->deliveryRowsAreEditable($rows), 403);

            $expectedIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
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

                $newBatchQuantity = $categoryRows->sum(
                    fn (ServiceDelivery $row): float => (float) $submittedItems[(int) $row->id]
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
                $quantity = (float) $submittedItems[(int) $row->id];

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
                return [
                    'recipient_key' => $recipientKey,
                    'recipient' => $groups->first()['recipient'],
                    'delivery_groups' => $groups->values(),
                ];
            })
            ->values();
    }

    protected function deliveryBatchRows(string $batchKey, bool $lockForUpdate = false): Collection
    {
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
