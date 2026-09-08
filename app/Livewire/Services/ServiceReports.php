<?php

namespace App\Livewire\Services;

use App\Exports\ServiceCategoryBreakdownExport;
use App\Exports\ServiceReportExport;
use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Livewire\Services\Concerns\SummarizesServiceDeliveries;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceReports extends Component
{
    use SummarizesServiceDeliveries;
    use WithPagination;

    protected const SERVICES_PER_PAGE = 20;

    protected const DELIVERY_GROUPS_PER_PAGE = 20;

    /**
     * Delivery-method landing cards shown before the services list. Adding a
     * new delivery method means adding one entry here (plus its supports_*
     * column/scope on Service, if missing); the selection screen and the
     * backend list filter are both driven off this map.
     */
    protected const DELIVERY_CHANNELS = [
        Service::DELIVERY_CHANNEL_HOME => [
            'label' => 'خدمات تحویل در منزل',
            'description' => 'خدماتی که مددکاران آن را در منزل مددجو تحویل می‌دهند.',
            'scope' => 'supportsHomeDelivery',
            'icon' => 'home',
            'classes' => 'border-sky-100 hover:border-sky-300 focus:ring-sky-100',
            'accent' => 'bg-sky-500',
            'iconClasses' => 'bg-sky-100 text-sky-600',
            'textClasses' => 'text-sky-600',
        ],
        Service::DELIVERY_CHANNEL_GATE => [
            'label' => 'خدمات ایستگاه توزیع',
            'description' => 'خدماتی که در گیت‌های ایستگاه توزیع تحویل داده می‌شوند.',
            'scope' => 'supportsGateDelivery',
            'icon' => 'station',
            'classes' => 'border-indigo-100 hover:border-indigo-300 focus:ring-indigo-100',
            'accent' => 'bg-indigo-500',
            'iconClasses' => 'bg-indigo-100 text-indigo-600',
            'textClasses' => 'text-indigo-600',
        ],
        Service::DELIVERY_CHANNEL_ACTIVITY => [
            'label' => 'خدمات فعالیتی',
            'description' => 'خدماتی که در دل فعالیت‌ها و هنگام حضور شرکت‌کنندگان تحویل داده می‌شوند.',
            'scope' => 'supportsActivityDelivery',
            'icon' => 'activity',
            'classes' => 'border-emerald-100 hover:border-emerald-300 focus:ring-emerald-100',
            'accent' => 'bg-emerald-500',
            'iconClasses' => 'bg-emerald-100 text-emerald-600',
            'textClasses' => 'text-emerald-600',
        ],
    ];

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public ?int $selectedServiceId = null;

    /**
     * Currently chosen delivery method (a key of DELIVERY_CHANNELS).
     * While it is null the landing selection screen is shown and the
     * services list query never runs.
     */
    public ?string $deliveryChannel = null;

    public string $search = '';

    public string $selectedStatus = 'all';

    public string $selectedCategory = 'all';

    public string $selectedType = 'all';

    public string $selectedServiceName = 'all';

    public string $selectedSocialWorker = 'all';

    public string $serviceDateFrom = '';

    public string $serviceDateTo = '';

    public string $deliverySearch = '';

    public string $selectedDeliveryEntryType = 'all';

    /**
     * The details-screen «مددکار اجتماعی» filter: recipients covered by the
     * chosen worker — the worker assigned to the family
     * (guardians.social_worker_id). People have no worker of their own; for
     * individual deliveries coverage runs through the person's guardian.
     */
    public string $selectedCoverageSocialWorker = 'all';

    public string $deliveryDateFrom = '';

    public string $deliveryDateTo = '';

    public ?int $editingDeliveryId = null;

    public bool $showEditDeliveryModal = false;

    public string $editRecipientName = '';

    public string $editNationalId = '';

    public string $editMobile = '';

    public ?int $editServiceCategoryId = null;

    public string $editDeliveredQuantity = '';

    public string $editDeliveredAt = '';

    public string $editNotes = '';

    public string $editConnectMessage = '';

    public string $editConnectMessageType = 'info';

    /**
     * Single source of truth for the delivery filters (entry type, date range,
     * search). The paginated list, the category-breakdown modal and the exports
     * all build on it, so on-screen numbers and exported files always agree.
     */
    protected function filteredDeliveryQuery(): Builder
    {
        $query = ServiceDelivery::query()->where('service_id', $this->selectedServiceId);

        $entryType = $this->selectedDeliveryEntryType;
        if ($entryType === 'manual') {
            $query->whereNull('person_id')->whereNull('guardian_id');
        } elseif ($entryType === 'individual') {
            $query->whereNotNull('person_id');
        } elseif ($entryType === 'guardian') {
            $query->whereNull('person_id')->whereNotNull('guardian_id');
        }

        $coverageWorkerId = ctype_digit($this->selectedCoverageSocialWorker) && (int) $this->selectedCoverageSocialWorker > 0
            ? (int) $this->selectedCoverageSocialWorker
            : null;

        if ($coverageWorkerId !== null) {
            // Coverage lives on the guardian: family deliveries link directly,
            // individual deliveries via the person's guardian. Manual records
            // have no guardian and never match.
            $query->where(function ($q) use ($coverageWorkerId) {
                $q->whereHas('guardian', fn ($g) => $g->where('social_worker_id', $coverageWorkerId))
                    ->orWhereHas('person.guardian', fn ($g) => $g->where('social_worker_id', $coverageWorkerId));
            });
        }

        $dateFrom = $this->normalizedDateInput($this->deliveryDateFrom);
        $dateTo = $this->normalizedDateInput($this->deliveryDateTo);

        if ($dateFrom !== null) {
            $query->whereDate('delivered_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('delivered_at', '<=', $dateTo);
        }

        $search = trim($this->deliverySearch);

        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(function ($q) use ($like) {
                $q->where('full_name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('person', fn ($p) => $p->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('guardian', fn ($g) => $g->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('socialWorker', fn ($w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * SQL mirror of recipientGroupKey(): one stable group key per recipient
     * (person / guardian / manual-by-national-id / manual-by-delivery-id).
     */
    protected function recipientGroupKeyExpression(): string
    {
        return "CASE
            WHEN person_id IS NOT NULL THEN CONCAT('person-', person_id)
            WHEN guardian_id IS NOT NULL THEN CONCAT('guardian-', guardian_id)
            WHEN national_id IS NOT NULL AND TRIM(national_id) <> '' THEN CONCAT('manual-', TRIM(national_id))
            ELSE CONCAT('manual-delivery-', id)
        END";
    }

    protected function deliveryRelationQuery(Builder $query): Builder
    {
        return $query
            ->with([
                'serviceCategory',
                'person.guardian.socialWorker',
                'guardian.socialWorker',
                'socialWorker',
                'creator',
                'updater',
            ])
            ->orderByDesc('delivered_at');
    }

    protected function findServiceDelivery(int $deliveryId): ?ServiceDelivery
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return $this->deliveryRelationQuery(
            ServiceDelivery::query()
                ->where('service_id', $this->selectedServiceId)
                ->whereKey($deliveryId)
        )->first();
    }

    public function getEditingDeliveryProperty(): ?ServiceDelivery
    {
        return $this->editingDeliveryId ? $this->findServiceDelivery($this->editingDeliveryId) : null;
    }

    public function getFilteredDeliveriesProperty()
    {
        if (! $this->selectedService) {
            return collect();
        }

        return $this->deliveryRelationQuery($this->filteredDeliveryQuery())->get();
    }

    public function getDeliveryRecipientCountProperty(): int
    {
        if (! $this->selectedServiceId) {
            return 0;
        }

        // Intentionally unfiltered: the header count reflects all deliveries of the service.
        $row = ServiceDelivery::query()
            ->where('service_id', $this->selectedServiceId)
            ->selectRaw('COUNT(DISTINCT '.$this->recipientGroupKeyExpression().') as recipient_count')
            ->first();

        return (int) ($row->recipient_count ?? 0);
    }

    protected function recipientGroupKey(ServiceDelivery $delivery): string
    {
        if ($delivery->person_id) {
            return 'person-'.$delivery->person_id;
        }
        if ($delivery->guardian_id) {
            return 'guardian-'.$delivery->guardian_id;
        }
        $nationalId = trim((string) ($delivery->national_id ?? ''));

        return $nationalId !== '' ? 'manual-'.$nationalId : 'manual-delivery-'.$delivery->id;
    }

    public function getGroupedDeliveriesProperty()
    {
        return $this->filteredDeliveries
            ->groupBy(fn ($delivery) => $this->recipientGroupKey($delivery))
            ->map(fn ($deliveries) => $this->mapDeliveryGroup($deliveries))
            ->values();
    }

    /**
     * Recipient groups for the on-screen list, paginated in SQL: the first
     * query resolves only the current page of group keys (20 per page), the
     * second loads just those deliveries. Full-set aggregates (modal breakdown,
     * exports) never go through this property.
     */
    public function getDeliveryGroupsProperty()
    {
        if (! $this->selectedServiceId) {
            return new LengthAwarePaginator([], 0, self::DELIVERY_GROUPS_PER_PAGE);
        }

        $groupPage = $this->filteredDeliveryQuery()
            ->selectRaw($this->recipientGroupKeyExpression().' as group_key, MAX(delivered_at) as last_delivered_at')
            ->groupBy('group_key')
            ->orderByDesc('last_delivered_at')
            ->paginate(self::DELIVERY_GROUPS_PER_PAGE, ['group_key', 'last_delivered_at'], 'deliveries');

        $keys = collect($groupPage->items())->pluck('group_key');

        if ($keys->isEmpty()) {
            return $groupPage->setCollection(collect());
        }

        $deliveries = $this->deliveryRelationQuery($this->filteredDeliveryQuery())
            ->where(function ($q) use ($keys) {
                foreach ($keys as $key) {
                    if (str_starts_with($key, 'person-')) {
                        $q->orWhere(fn ($inner) => $inner->where('person_id', substr($key, 7)));
                    } elseif (str_starts_with($key, 'guardian-')) {
                        $q->orWhere(fn ($inner) => $inner->where('guardian_id', substr($key, 9)));
                    } elseif (str_starts_with($key, 'manual-delivery-')) {
                        $q->orWhere(fn ($inner) => $inner->where('id', substr($key, 16)));
                    } else {
                        $nationalId = substr($key, 7);
                        $q->orWhere(fn ($inner) => $inner
                            ->whereNull('person_id')
                            ->whereNull('guardian_id')
                            ->where('national_id', $nationalId));
                    }
                }
            })
            ->get();

        $groups = $deliveries
            ->groupBy(fn ($delivery) => $this->recipientGroupKey($delivery))
            ->map(fn ($groupDeliveries) => $this->mapDeliveryGroup($groupDeliveries));

        return $groupPage->setCollection(
            $keys->map(fn ($key) => $groups->get($key))->filter()->values()
        );
    }

    protected function mapDeliveryGroup(Collection $deliveries): object
    {
        $first = $deliveries->first();

        $unitTotals = $deliveries
            ->groupBy(fn ($d) => $d->serviceCategory?->unit ?: '__none__')
            ->map(function ($unitDeliveries, $unitKey) {
                $hasUnit = $unitKey !== '__none__';
                $total = $unitDeliveries->sum(fn ($d) => (float) $d->delivered_quantity);

                return [
                    'unitKey' => $hasUnit ? $unitKey : null,
                    'label' => $hasUnit ? (Service::unitOptions()[$unitKey] ?? $unitKey) : '-',
                    'total' => Service::formatQuantityForUnit($total, $hasUnit ? $unitKey : null),
                ];
            })
            ->values();

        $receiptItems = $deliveries
            ->groupBy(function ($d) {
                $categoryId = $d->service_category_id ?: 'none';
                $unitKey = $d->serviceCategory?->unit ?: '__none__';

                return $categoryId.'|'.$unitKey;
            })
            ->map(function ($categoryDeliveries) {
                $sample = $categoryDeliveries->first();
                $unitKey = $sample->serviceCategory?->unit ?: null;
                $total = $categoryDeliveries->sum(fn ($d) => (float) $d->delivered_quantity);
                $lastDeliveredAt = $categoryDeliveries
                    ->map(fn ($d) => $d->delivered_at)
                    ->filter()
                    ->sortDesc()
                    ->first();

                return [
                    'category' => $sample->serviceCategory?->name ?: '-',
                    'quantity' => Service::formatQuantityForUnit($total, $unitKey),
                    'unitLabel' => $unitKey ? (Service::unitOptions()[$unitKey] ?? $unitKey) : '-',
                    'recordCount' => $categoryDeliveries->count(),
                    'date' => $lastDeliveredAt
                        ? Jalalian::fromDateTime($lastDeliveredAt)->format('Y/m/d')
                        : '-',
                ];
            })
            ->values();

        $lastDeliveredAt = $deliveries
            ->map(fn ($d) => $d->delivered_at)
            ->filter()
            ->sortDesc()
            ->first();

        return (object) [
            'recipientName' => $first->recipient_name,
            'recipientNationalId' => $first->recipient_national_id,
            'recipientType' => $first->person ? 'شخصی' : ($first->guardian ? 'خانوادگی' : 'ثبت دستی'),
            'person' => $first->person,
            'guardian' => $first->guardian,
            'mobile' => $deliveries->pluck('mobile')->filter()->first(),
            'totalQuantity' => $deliveries->sum(fn ($d) => (float) $d->delivered_quantity),
            'unitTotals' => $unitTotals,
            'receiptItems' => $receiptItems,
            'receiptDate' => $lastDeliveredAt
                ? Jalalian::fromDateTime($lastDeliveredAt)->format('Y/m/d')
                : '-',
            'totalValue' => $deliveries->sum('delivered_total_value'),
            'deliveries' => $deliveries->values(),
        ];
    }

    public function getDeliveredCategoryBreakdownProperty()
    {
        if (! $this->selectedServiceId) {
            return collect();
        }

        $rows = $this->filteredDeliveryQuery()
            ->selectRaw('service_category_id, SUM(delivered_quantity) as total, COUNT(*) as record_count, MAX(delivered_at) as last_delivered_at')
            ->groupBy('service_category_id')
            ->orderByDesc('last_delivered_at')
            ->get();

        $categories = ServiceCategory::query()
            ->withTrashed()
            ->whereIn('id', $rows->pluck('service_category_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($categories) {
            $category = $row->service_category_id ? $categories->get((int) $row->service_category_id) : null;
            $unitKey = $category?->unit ?: null;
            $total = (float) $row->total;
            $categoryQuantity = $category !== null ? (float) $category->quantity : null;

            return [
                'category' => $category?->name ?: '-',
                'unitLabel' => $unitKey ? (Service::unitOptions()[$unitKey] ?? $unitKey) : '-',
                'total' => Service::formatQuantityForUnit($total, $unitKey),
                'totalRaw' => $total,
                'recordCount' => (int) $row->record_count,
                'remaining' => $categoryQuantity === null
                    ? null
                    : Service::formatQuantityForUnit(max(0, $categoryQuantity - $total), $unitKey),
                'categoryTotal' => $categoryQuantity === null
                    ? null
                    : Service::formatQuantityForUnit($categoryQuantity, $unitKey),
            ];
        })->values();
    }

    public function mount(?int $selectedServiceId = null, ?string $deliveryChannel = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->selectedServiceId = $selectedServiceId;
        $this->deliveryChannel = $this->normalizeDeliveryChannel($deliveryChannel);
    }

    protected function normalizeDeliveryChannel(?string $channel): ?string
    {
        return $channel !== null && array_key_exists($channel, self::DELIVERY_CHANNELS)
            ? $channel
            : null;
    }

    public function selectDeliveryChannel(string $channel): void
    {
        $channel = $this->normalizeDeliveryChannel($channel);

        if ($channel === null) {
            return;
        }

        $this->deliveryChannel = $channel;
        $this->selectedServiceId = null;
        $this->clearServiceFilters();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report', channel: $channel);
    }

    public function backToChannelSelection(): void
    {
        $this->deliveryChannel = null;
        $this->selectedServiceId = null;
        $this->clearServiceFilters();
        $this->resetPage('deliveries');
        $this->closeEditDeliveryModal();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report');
    }

    public function updatingDeliveryChannel(): void
    {
        $this->resetPage();
    }

    public function openService(int $serviceId): void
    {
        $serviceExists = Service::withTrashed()->whereKey($serviceId)->exists();

        if (! $serviceExists) {
            return;
        }

        $this->selectedServiceId = $serviceId;
        $this->deliverySearch = '';
        $this->selectedDeliveryEntryType = 'all';
        $this->selectedCoverageSocialWorker = 'all';
        $this->deliveryDateFrom = '';
        $this->deliveryDateTo = '';
        $this->resetPage('deliveries');
        $this->closeEditDeliveryModal();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report', id: $serviceId, channel: $this->normalizeDeliveryChannel($this->deliveryChannel));
    }

    public function backToServices(): void
    {
        $this->selectedServiceId = null;
        $this->deliverySearch = '';
        $this->selectedDeliveryEntryType = 'all';
        $this->selectedCoverageSocialWorker = 'all';
        $this->deliveryDateFrom = '';
        $this->deliveryDateTo = '';
        $this->resetPage('deliveries');
        $this->closeEditDeliveryModal();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report', channel: $this->normalizeDeliveryChannel($this->deliveryChannel));
    }

    public function clearServiceFilters(): void
    {
        $this->search = '';
        $this->selectedServiceName = 'all';
        $this->selectedCategory = 'all';
        $this->selectedStatus = 'all';
        $this->selectedType = 'all';
        $this->selectedSocialWorker = 'all';
        $this->serviceDateFrom = '';
        $this->serviceDateTo = '';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedServiceName(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSocialWorker(): void
    {
        $this->resetPage();
    }

    public function updatingServiceDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingServiceDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingDeliverySearch(): void
    {
        $this->resetPage('deliveries');
    }

    public function updatingSelectedDeliveryEntryType(): void
    {
        $this->resetPage('deliveries');
    }

    public function updatingSelectedCoverageSocialWorker(): void
    {
        $this->resetPage('deliveries');
    }

    public function updatingDeliveryDateFrom(): void
    {
        $this->resetPage('deliveries');
    }

    public function updatingDeliveryDateTo(): void
    {
        $this->resetPage('deliveries');
    }

    public function clearDeliveryFilters(): void
    {
        $this->deliverySearch = '';
        $this->selectedDeliveryEntryType = 'all';
        $this->selectedCoverageSocialWorker = 'all';
        $this->deliveryDateFrom = '';
        $this->deliveryDateTo = '';
        $this->resetPage('deliveries');
    }

    public function exportToExcel()
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $service = $this->selectedService;

        if (! $service) {
            session()->flash('error', 'ابتدا یک خدمت را انتخاب کنید.');

            return null;
        }

        $grouped = $this->groupedDeliveries;
        $rowCount = $grouped->sum(fn ($group) => $group->deliveries->count());

        if ($rowCount === 0) {
            session()->flash('error', 'رکوردی برای خروجی گرفتن یافت نشد.');

            return null;
        }

        $maxRows = 10000;

        if ($rowCount > $maxRows) {
            session()->flash('error', "تعداد رکوردها ({$rowCount}) از سقف مجاز خروجی ({$maxRows}) بیشتر است. با استفاده از فیلترها دامنه را محدود کنید.");

            return null;
        }

        $serviceName = $service->serviceName?->name ?: 'خدمت';
        $filename = 'گزارش-خدمت-'.$serviceName.'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        $export = new ServiceReportExport($service, $grouped, Service::unitOptions());

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function exportCategoryBreakdownToExcel()
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $service = $this->selectedService;

        if (! $service) {
            session()->flash('error', 'ابتدا یک خدمت را انتخاب کنید.');

            return null;
        }

        $breakdown = $this->deliveredCategoryBreakdown;

        if ($breakdown->isEmpty()) {
            session()->flash('error', 'رکوردی برای خروجی گرفتن یافت نشد.');

            return null;
        }

        $serviceName = $service->serviceName?->name ?: 'خدمت';
        $filename = 'جزئیات-تحویل-'.$serviceName.'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        $export = new ServiceCategoryBreakdownExport($breakdown, $serviceName);

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->withTrashed()
            ->with([
                'serviceName',
                'categories' => fn ($query) => $query->withTrashed()->ordered(),
                'district',
                'socialWorkers',
            ])
            ->find($this->selectedServiceId);
    }

    public function render()
    {
        $deliveryChannel = $this->normalizeDeliveryChannel($this->deliveryChannel);
        $search = trim($this->search);

        $status = ($this->selectedStatus === 'all') ? null : $this->selectedStatus;
        $category = ($this->selectedCategory === 'all') ? null : $this->selectedCategory;
        $type = ($this->selectedType === 'all') ? null : $this->selectedType;
        $serviceName = ($this->selectedServiceName === 'all') ? null : $this->selectedServiceName;
        $socialWorkerId = ctype_digit($this->selectedSocialWorker) && (int) $this->selectedSocialWorker > 0
            ? (int) $this->selectedSocialWorker
            : null;

        // The list's «تاریخ» column shows created_at, so the date range filters that column.
        $createdFrom = $this->normalizedDateInput($this->serviceDateFrom);
        $createdTo = $this->normalizedDateInput($this->serviceDateTo);

        $categories = ServiceCategory::query()
            ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($sq) => $sq->where('name', $serviceName)))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $serviceNames = ServiceName::query()
            ->ordered()
            ->pluck('name')
            ->toArray();

        // The landing screen needs only the per-channel counts; the services list
        // runs only after a delivery method is picked (and is then filtered by it);
        // the detail screen skips the list entirely.
        $services = null;
        $socialWorkerOptions = [];
        $coverageSocialWorkerOptions = [];
        $deliveryChannelCards = [];

        if ($this->selectedServiceId === null && $deliveryChannel === null) {
            $deliveryChannelCards = collect(self::DELIVERY_CHANNELS)
                ->map(function (array $card, string $channel): array {
                    $scope = $card['scope'];

                    return $card + [
                        'channel' => $channel,
                        'count' => Service::query()->$scope()->count(),
                    ];
                })
                ->values()
                ->all();
        } elseif ($this->selectedServiceId === null) {
            // Dropdown of assignable workers for the «مددکار اجتماعی» filter;
            // includes inactive (non-deleted) workers whose historical
            // responsibilities must stay reportable.
            $socialWorkerOptions = SocialWorker::query()
                ->withoutGlobalScope('active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (SocialWorker $worker): array => [
                    'id' => $worker->id,
                    'name' => trim($worker->first_name.' '.$worker->last_name),
                ])
                ->all();

            $channelScope = self::DELIVERY_CHANNELS[$deliveryChannel]['scope'];

            $services = Service::query()
                ->$channelScope()
                ->with([
                    'serviceName',
                    'categories' => fn ($query) => $query->ordered(),
                    'socialWorkers',
                    'creator',
                    'workerAllocations.socialWorker',
                    'deliveries.person.guardian',
                    'deliveries.guardian',
                ])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($category, fn ($q) => $q->whereHas('serviceCategory', fn ($q) => $q->where('name', $category)))
                ->when($type, fn ($q) => $q->where('service_type', $type))
                ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($q) => $q->where('name', $serviceName)))
                ->when($socialWorkerId, fn ($q) => $q->where(function ($inner) use ($socialWorkerId) {
                    $inner
                        ->whereHas('workerAllocations', fn ($a) => $a->where('social_worker_id', $socialWorkerId))
                        ->orWhereHas('deliveries', fn ($d) => $d->where('social_worker_id', $socialWorkerId));
                }))
                ->when($createdFrom !== null, fn ($q) => $q->whereDate('created_at', '>=', $createdFrom))
                ->when($createdTo !== null, fn ($q) => $q->whereDate('created_at', '<=', $createdTo))
                ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                    $inner
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('serviceName', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('serviceCategory', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%'));
                }))
                ->latest()
                ->paginate(self::SERVICES_PER_PAGE);
        } elseif ($this->selectedServiceId !== null) {
            // «مددکار اجتماعی» dropdown: workers assigned to guardians who have
            // a delivery in this service — directly (family) or through their
            // people (individual). Inactive workers stay selectable so past
            // coverage remains reportable; soft-deleted rows drop out via scopes.
            $coverageWorkerIds = Guardian::query()
                ->whereNotNull('social_worker_id')
                ->where(function ($q) {
                    $q->whereHas('serviceDeliveries', fn ($d) => $d->where('service_id', $this->selectedServiceId))
                        ->orWhereHas('people.serviceDeliveries', fn ($d) => $d->where('service_id', $this->selectedServiceId));
                })
                ->distinct()
                ->pluck('social_worker_id');

            $coverageSocialWorkerOptions = SocialWorker::query()
                ->withoutGlobalScope('active')
                ->whereIn('id', $coverageWorkerIds)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (SocialWorker $worker): array => [
                    'id' => $worker->id,
                    'name' => trim($worker->first_name.' '.$worker->last_name),
                ])
                ->all();
        }

        return view('livewire.services.service-reports', [
            'services' => $services,
            'deliveryChannel' => $deliveryChannel,
            'deliveryChannelLabel' => $deliveryChannel !== null ? self::DELIVERY_CHANNELS[$deliveryChannel]['label'] : null,
            'deliveryChannelCards' => $deliveryChannelCards,
            'selectedService' => $this->selectedService,
            'deliveryGroups' => $this->deliveryGroups,
            'statusOptions' => Service::STATUS_OPTIONS,
            'typeDisplayOptions' => Service::typeDisplayOptions(),
            'unitOptions' => Service::unitOptions(),
            'categoryOptions' => $categories,
            'serviceNames' => $serviceNames,
            'socialWorkerOptions' => $socialWorkerOptions,
            'coverageSocialWorkerOptions' => $coverageSocialWorkerOptions,
            'jalaliDateTime' => fn ($dateTime) => $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-',
        ]);
    }

    public function editDelivery(int $deliveryId): void
    {
        $delivery = $this->findServiceDelivery($deliveryId);

        if (! $delivery) {
            return;
        }

        $this->editingDeliveryId = $delivery->id;
        $this->editRecipientName = (string) ($delivery->full_name ?? '');
        $this->editNationalId = (string) ($delivery->national_id ?? '');
        $this->editMobile = (string) ($delivery->mobile ?? '');
        $this->editServiceCategoryId = $delivery->service_category_id
            ? (int) $delivery->service_category_id
            : ($this->selectedService?->categories()->ordered()->value('id') ? (int) $this->selectedService->categories()->ordered()->value('id') : null);
        $this->editDeliveredQuantity = $this->formatDecimal($delivery->delivered_quantity);
        $this->editDeliveredAt = $delivery->delivered_at
            ? Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
            : Jalalian::fromDateTime(now())->format('Y/m/d');
        $this->editNotes = (string) ($delivery->notes ?? '');
        $this->editConnectMessage = '';
        $this->editConnectMessageType = 'info';
        $this->showEditDeliveryModal = true;
        $this->resetValidation();
    }

    public function connectDeliveryRecipient(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $delivery = ServiceDelivery::query()
            ->whereKey($this->editingDeliveryId)
            ->where('service_id', $service->id)
            ->first();

        abort_unless($delivery, 404);

        $validated = $this->validate([
            'editNationalId' => ['required', 'digits:10'],
            'editRecipientName' => ['nullable', 'string', 'max:255'],
        ], [], [
            'editNationalId' => 'کد ملی گیرنده',
            'editRecipientName' => 'نام گیرنده',
        ]);

        $nationalId = trim($validated['editNationalId']);

        if ($service->service_type === 'family') {
            $guardian = Guardian::query()
                ->where('national_code', $nationalId)
                ->first();

            if (! $guardian) {
                $this->editConnectMessage = 'سرپرستی با این کد ملی پیدا نشد.';
                $this->editConnectMessageType = 'error';

                return;
            }

            $profileName = $guardian->full_name !== '' ? $guardian->full_name : trim($this->editRecipientName);
            $profileMobile = $guardian->guardian_phone_number ?: null;

            $delivery->update([
                'guardian_id' => $guardian->id,
                'person_id' => null,
                'national_id' => (string) $guardian->national_code,
                'full_name' => $profileName,
                'mobile' => $profileMobile,
            ]);

            $this->editRecipientName = $profileName;
            $this->editNationalId = (string) $guardian->national_code;
            $this->editMobile = (string) ($profileMobile ?? '');
            $this->editConnectMessage = 'اطلاعات از پرونده سرپرست بازیابی و رکورد با موفقیت متصل شد.';
            $this->editConnectMessageType = 'success';

            return;
        }

        $person = Person::query()
            ->with('guardian')
            ->where('national_id', $nationalId)
            ->first();

        if (! $person) {
            $this->editConnectMessage = 'فردی با این کد ملی پیدا نشد.';
            $this->editConnectMessageType = 'error';

            return;
        }

        $profileName = trim(implode(' ', array_filter([$person->first_name, $person->last_name]))) ?: trim($this->editRecipientName);
        $profileMobile = $person->guardian?->guardian_phone_number ?: null;

        $delivery->update([
            'person_id' => $person->id,
            'guardian_id' => null,
            'national_id' => (string) $person->national_id,
            'full_name' => $profileName,
            'mobile' => $profileMobile,
        ]);

        $this->editRecipientName = $profileName;
        $this->editNationalId = (string) $person->national_id;
        $this->editMobile = (string) ($profileMobile ?? '');
        $this->editConnectMessage = 'اطلاعات از پرونده فرد بازیابی و رکورد با موفقیت متصل شد.';
        $this->editConnectMessageType = 'success';
    }

    public function saveDeliveryEdits(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $delivery = ServiceDelivery::query()
            ->whereKey($this->editingDeliveryId)
            ->where('service_id', $service->id)
            ->first();

        abort_unless($delivery, 404);

        $validated = $this->validate($this->deliveryEditRules(), [], $this->deliveryEditAttributes());

        $newQuantity = (float) $validated['editDeliveredQuantity'];
        $newCategoryId = (int) $validated['editServiceCategoryId'];
        $otherDeliveredForService = max(0, (float) $service->quantity_delivered - (float) $delivery->delivered_quantity);

        if (($otherDeliveredForService + $newQuantity) > (float) $service->total_quantity) {
            throw ValidationException::withMessages([
                'editDeliveredQuantity' => 'مقدار تحویل‌شده نمی‌تواند از تعداد کل خدمت بیشتر شود.',
            ]);
        }

        if ($delivery->social_worker_id) {
            $allocated = $service->allocatedQuantityForWorker((int) $delivery->social_worker_id);

            if ($allocated > 0) {
                $otherDeliveredForWorker = max(0, $service->deliveredQuantityForWorker((int) $delivery->social_worker_id) - (float) $delivery->delivered_quantity);

                if (($otherDeliveredForWorker + $newQuantity) > $allocated) {
                    throw ValidationException::withMessages([
                        'editDeliveredQuantity' => 'مقدار جدید از سهمیه تخصیص‌یافته این مددکار بیشتر است.',
                    ]);
                }
            }
        }

        $targetCategory = ServiceCategory::query()
            ->where('service_id', $service->id)
            ->whereKey($newCategoryId)
            ->first();

        if (! $targetCategory) {
            throw ValidationException::withMessages([
                'editServiceCategoryId' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
            ]);
        }

        $otherDeliveredInTargetCategory = max(
            0,
            $service->deliveredQuantityForCategory($newCategoryId)
                - ((int) $delivery->service_category_id === $newCategoryId ? (float) $delivery->delivered_quantity : 0)
        );

        $availableInTargetCategory = max(0, (float) $targetCategory->quantity - $otherDeliveredInTargetCategory);

        if ($newQuantity > $availableInTargetCategory) {
            throw ValidationException::withMessages([
                'editDeliveredQuantity' => 'مقدار جدید از موجودی دسته‌بندی انتخاب‌شده بیشتر است.',
            ]);
        }

        $payload = [
            'full_name' => trim((string) ($validated['editRecipientName'] ?? '')),
            'national_id' => trim($validated['editNationalId']),
            'mobile' => trim($validated['editMobile']) !== '' ? trim($validated['editMobile']) : null,
            'service_category_id' => $newCategoryId,
            'delivered_quantity' => $newQuantity,
            'value_per_unit_snapshot' => (int) $targetCategory->value,
            'delivered_total_value' => (int) round($newQuantity * (int) $targetCategory->value),
            'delivered_at' => $this->jalaliToGregorian($validated['editDeliveredAt']),
            'notes' => trim($validated['editNotes']) !== '' ? trim($validated['editNotes']) : null,
        ];

        $delivery->update($payload);

        $this->closeEditDeliveryModal();
    }

    public function closeEditDeliveryModal(): void
    {
        $this->editingDeliveryId = null;
        $this->showEditDeliveryModal = false;
        $this->editRecipientName = '';
        $this->editNationalId = '';
        $this->editMobile = '';
        $this->editServiceCategoryId = null;
        $this->editDeliveredQuantity = '';
        $this->editDeliveredAt = '';
        $this->editNotes = '';
        $this->editConnectMessage = '';
        $this->editConnectMessageType = 'info';
        $this->resetValidation();
    }

    public function deleteDelivery(int $deliveryId): void
    {
        $delivery = ServiceDelivery::query()
            ->whereKey($deliveryId)
            ->first();

        if ($delivery) {
            if ($this->editingDeliveryId === $delivery->id) {
                $this->closeEditDeliveryModal();
            }

            $delivery->delete();
        }
    }

    protected function deliveryEditRules(): array
    {
        return [
            'editRecipientName' => ['nullable', 'string', 'max:255'],
            'editNationalId' => ['required', 'string', 'max:20'],
            'editMobile' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'editServiceCategoryId' => [
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where(fn ($query) => $query
                    ->where('service_id', $this->selectedServiceId)
                    ->whereNull('deleted_at')),
            ],
            'editDeliveredQuantity' => ['required', 'numeric', 'min:0.01'],
            'editDeliveredAt' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ تحویل باید به فرمت شمسی معتبر مانند 1405/03/16 وارد شود.');
                    }
                },
            ],
            'editNotes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function deliveryEditAttributes(): array
    {
        return [
            'editRecipientName' => 'نام گیرنده',
            'editNationalId' => 'کد ملی گیرنده',
            'editMobile' => 'موبایل',
            'editServiceCategoryId' => 'دسته‌بندی خدمت',
            'editDeliveredQuantity' => 'مقدار تحویل',
            'editDeliveredAt' => 'تاریخ تحویل',
            'editNotes' => 'توضیحات',
        ];
    }

    protected function currentEditingDeliveryIsManual(): bool
    {
        $delivery = $this->editingDeliveryId
            ? $this->findServiceDelivery($this->editingDeliveryId)
            : null;

        return $delivery ? $this->isManualDelivery($delivery) : false;
    }

    protected function isManualDelivery(ServiceDelivery $delivery): bool
    {
        return ! $delivery->person_id && ! $delivery->guardian_id;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
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

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }

    protected function normalizedDateInput(string $date): ?string
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date) === 1) {
            [$year, $month, $day] = array_map('intval', explode('/', $date));

            if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
                return null;
            }

            try {
                return Jalalian::fromFormat('Y/m/d', $date)->toCarbon()->toDateString();
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
}
