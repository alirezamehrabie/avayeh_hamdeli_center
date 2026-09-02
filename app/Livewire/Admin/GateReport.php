<?php

namespace App\Livewire\Admin;

use App\Exports\GateDeliveryReportExport;
use App\Exports\GateEntryReportExport;
use App\Exports\GateExitReportExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
use App\Models\GateEntryDeliveryRecipient;
use App\Models\GateEntryFieldValue;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceEntryField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class GateReport extends Component
{
    use WithPagination;

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public ?int $selectedServiceId = null;

    public string $serviceSearch = '';

    /** Which of the three gate reports is on screen: entry | delivery | exit. */
    public string $activeTab = 'entry';

    public string $search = '';

    public string $selectedCategory = 'all';

    public string $selectedStatus = 'all';

    /** all | direct | proxy | one of GateEntryDeliveryRecipient::TYPE_OPTIONS keys. */
    public string $selectedDeliveryMethod = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $queryString = [
        'selectedServiceId' => ['except' => null],
        'activeTab' => ['except' => 'entry'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function selectService(int $serviceId): void
    {
        $serviceExists = Service::query()->supportsGateDelivery()->whereKey($serviceId)->exists();

        if (! $serviceExists) {
            return;
        }

        $this->selectedServiceId = $serviceId;
        $this->serviceSearch = '';
        $this->resetFilters();
    }

    public function backToServices(): void
    {
        $this->selectedServiceId = null;
        $this->resetFilters();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['entry', 'delivery', 'exit'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->selectedStatus = 'all';
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'selectedCategory', 'selectedStatus', 'selectedDeliveryMethod', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = 'all';
        $this->selectedStatus = 'all';
        $this->selectedDeliveryMethod = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function exportToExcel()
    {
        $service = $this->selectedService();

        if (! $service) {
            return null;
        }

        $maxRows = 5000;
        $query = $this->buildQueryForTab($this->activeTab, $service);
        $count = (clone $query)->count();

        if ($count === 0) {
            session()->flash('error', 'رکوردی برای خروجی گرفتن یافت نشد.');

            return null;
        }

        if ($count > $maxRows) {
            session()->flash('error', "تعداد رکوردها ({$count}) از سقف مجاز خروجی ({$maxRows}) بیشتر است.");

            return null;
        }

        $tabLabels = ['entry' => 'ورود', 'delivery' => 'تحویل', 'exit' => 'خروج'];
        $filename = 'گزارش-گیت-'.$tabLabels[$this->activeTab].'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        $exportQuery = $query->limit($maxRows);

        $export = match ($this->activeTab) {
            'delivery' => new GateDeliveryReportExport($exportQuery, $service),
            'exit' => new GateExitReportExport($exportQuery, $service),
            default => new GateEntryReportExport($exportQuery, $service),
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function render()
    {
        $service = $this->selectedService();

        $results = null;
        $stats = null;
        $categories = collect();
        $entryFieldsByPerson = collect();
        $entryFieldsByGuardian = collect();
        $proxyByPerson = collect();
        $proxyByGuardian = collect();

        if ($service) {
            $categories = $service->categories()->ordered()->get();
            $stats = $this->buildStats($service);

            $results = $this->buildQueryForTab($this->activeTab, $service)
                ->paginate(20);

            [$proxyByPerson, $proxyByGuardian] = $this->loadProxyRecipients($service, $results->getCollection());

            if ($this->activeTab === 'entry') {
                [$entryFieldsByPerson, $entryFieldsByGuardian] = $this->loadEntryFieldValues($service, $results->getCollection());
            }
        }

        return view('livewire.admin.gate-report', [
            'gateServices' => $this->selectedServiceId ? collect() : $this->gateServices(),
            'selectedService' => $service,
            'categories' => $categories,
            'stats' => $stats,
            'results' => $results,
            'entryFieldsByPerson' => $entryFieldsByPerson,
            'entryFieldsByGuardian' => $entryFieldsByGuardian,
            'entryFieldDefinitions' => $service?->entryFields ?? collect(),
            'proxyByPerson' => $proxyByPerson,
            'proxyByGuardian' => $proxyByGuardian,
        ]);
    }

    protected function gateServices(): Collection
    {
        return Service::query()
            ->supportsGateDelivery()
            ->withCount('categories')
            ->when(trim($this->serviceSearch) !== '', function (Builder $query): void {
                $search = trim($this->serviceSearch);

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('serviceName', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('categories', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->get();
    }

    protected function selectedService(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->supportsGateDelivery()
            ->with(['serviceName', 'entryFields'])
            ->find($this->selectedServiceId);
    }

    protected function buildStats(Service $service): array
    {
        $assignmentCounts = GateEntryAssignment::query()
            ->where('service_id', $service->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($assignmentCounts[GateEntryAssignment::STATUS_PENDING] ?? 0);
        $delivered = (int) ($assignmentCounts[GateEntryAssignment::STATUS_DELIVERED] ?? 0);
        $finalized = (int) ($assignmentCounts[GateEntryAssignment::STATUS_FINALIZED] ?? 0);
        $totalEntered = $pending + $delivered + $finalized;

        $totalValue = (int) ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_GATE)
            ->sum('delivered_total_value');

        // Distinct beneficiaries whose items were declared as handed to somebody else.
        $proxyRecipients = (int) GateEntryDeliveryRecipient::query()
            ->where('service_id', $service->id)
            ->where('is_proxy_delivery', true)
            ->count();

        return [
            'entered' => $totalEntered,
            'delivered' => $delivered,
            'exited' => $finalized,
            'pending' => $pending,
            'total_value' => $totalValue,
            'proxy_recipients' => $proxyRecipients,
        ];
    }

    protected function buildQueryForTab(string $tab, Service $service): Builder
    {
        return match ($tab) {
            'delivery' => $this->deliveryQuery($service),
            'exit' => $this->exitQuery($service),
            default => $this->entryQuery($service),
        };
    }

    protected function entryQuery(Service $service): Builder
    {
        $query = GateEntryAssignment::query()
            ->where('service_id', $service->id)
            ->with(['serviceCategory', 'person:id,person_code', 'guardian:id,guardian_code', 'creator']);

        $this->applySharedFilters($query, 'assigned_at');

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        return $query->orderByDesc('assigned_at')->orderByDesc('id');
    }

    protected function deliveryQuery(Service $service): Builder
    {
        $query = GateEntryAssignment::query()
            ->where('service_id', $service->id)
            ->whereIn('status', [GateEntryAssignment::STATUS_DELIVERED, GateEntryAssignment::STATUS_FINALIZED])
            ->with(['serviceCategory', 'person:id,person_code', 'guardian:id,guardian_code', 'deliveredBy']);

        $this->applySharedFilters($query, 'delivered_at');

        return $query->orderByDesc('delivered_at')->orderByDesc('id');
    }

    protected function exitQuery(Service $service): Builder
    {
        $query = ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_GATE)
            ->with(['serviceCategory', 'person:id,person_code', 'guardian:id,guardian_code', 'creator']);

        $this->applySharedFilters($query, 'delivered_at');

        return $query->orderByDesc('delivered_at')->orderByDesc('id');
    }

    protected function applySharedFilters(Builder $query, string $dateColumn): void
    {
        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($this->selectedCategory !== 'all') {
            $query->where('service_category_id', (int) $this->selectedCategory);
        }

        $dateFrom = $this->normalizedDateInput($this->dateFrom);
        $dateTo = $this->normalizedDateInput($this->dateTo);

        if ($dateFrom) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        $this->applyDeliveryMethodFilter($query);
    }

    /**
     * Filter by the Entry Gate's delivery-method declaration. Both reported tables (assignments and
     * the delivery ledger) carry service_id + person_id/guardian_id, so the declaration is matched
     * with a correlated subquery against whichever table the tab is reading.
     */
    protected function applyDeliveryMethodFilter(Builder $query): void
    {
        if ($this->selectedDeliveryMethod === 'all') {
            return;
        }

        $table = $query->getModel()->getTable();

        $matchesSubject = function ($recipientQuery) use ($table): void {
            $recipientQuery->from('gate_entry_delivery_recipients')
                ->whereColumn('gate_entry_delivery_recipients.service_id', "{$table}.service_id")
                ->where(function ($subjectQuery) use ($table): void {
                    $subjectQuery
                        ->whereColumn('gate_entry_delivery_recipients.person_id', "{$table}.person_id")
                        ->orWhereColumn('gate_entry_delivery_recipients.guardian_id', "{$table}.guardian_id");
                })
                ->where('gate_entry_delivery_recipients.is_proxy_delivery', true);
        };

        // "Direct" means no active proxy declaration at all — including rows recorded before this
        // feature existed, which is why it is a NOT EXISTS rather than a flag comparison.
        if ($this->selectedDeliveryMethod === 'direct') {
            $query->whereNotExists($matchesSubject);

            return;
        }

        if ($this->selectedDeliveryMethod === 'proxy') {
            $query->whereExists($matchesSubject);

            return;
        }

        if (! array_key_exists($this->selectedDeliveryMethod, GateEntryDeliveryRecipient::TYPE_OPTIONS)) {
            return;
        }

        $type = $this->selectedDeliveryMethod;

        $query->whereExists(function ($recipientQuery) use ($matchesSubject, $type): void {
            $matchesSubject($recipientQuery);
            $recipientQuery->where('gate_entry_delivery_recipients.recipient_type', $type);
        });
    }

    /**
     * @return array{0: Collection, 1: Collection} Entry field values keyed by person_id / guardian_id.
     */
    protected function loadEntryFieldValues(Service $service, Collection $assignments): array
    {
        if ($service->entryFields->isEmpty() || $assignments->isEmpty()) {
            return [collect(), collect()];
        }

        $personIds = $assignments->pluck('person_id')->filter()->unique()->values();
        $guardianIds = $assignments->pluck('guardian_id')->filter()->unique()->values();

        if ($personIds->isEmpty() && $guardianIds->isEmpty()) {
            return [collect(), collect()];
        }

        $records = GateEntryFieldValue::query()
            ->where('service_id', $service->id)
            ->where(function (Builder $query) use ($personIds, $guardianIds): void {
                $query->whereIn('person_id', $personIds)->orWhereIn('guardian_id', $guardianIds);
            })
            ->get();

        $educationNames = null;
        $formatValues = function (array $values) use ($service, &$educationNames): array {
            $rows = [];

            foreach ($service->entryFields as $field) {
                $value = $values[$field->id] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                if ($field->type === ServiceEntryField::TYPE_EDUCATION_LEVEL) {
                    $educationNames ??= EducationLevel::query()->pluck('name', 'id');
                    $value = $educationNames[(int) $value] ?? null;

                    if (! $value) {
                        continue;
                    }
                }

                $rows[] = ['label' => $field->title ?: 'بدون عنوان', 'value' => (string) $value];
            }

            return $rows;
        };

        $byPerson = $records->whereNotNull('person_id')->mapWithKeys(
            fn (GateEntryFieldValue $record) => [(int) $record->person_id => $formatValues(collect($record->values ?? [])->all())]
        );

        $byGuardian = $records->whereNotNull('guardian_id')->mapWithKeys(
            fn (GateEntryFieldValue $record) => [(int) $record->guardian_id => $formatValues(collect($record->values ?? [])->all())]
        );

        return [$byPerson, $byGuardian];
    }

    /**
     * Delivery-method declarations for the rows on screen, keyed by person_id / guardian_id.
     * Every reported tab carries both columns, so one loader serves all three.
     *
     * @return array{0: Collection, 1: Collection}
     */
    protected function loadProxyRecipients(Service $service, Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [collect(), collect()];
        }

        $personIds = $rows->pluck('person_id')->filter()->unique()->values();
        $guardianIds = $rows->pluck('guardian_id')->filter()->unique()->values();

        if ($personIds->isEmpty() && $guardianIds->isEmpty()) {
            return [collect(), collect()];
        }

        $records = GateEntryDeliveryRecipient::query()
            ->where('service_id', $service->id)
            ->where('is_proxy_delivery', true)
            ->where(function (Builder $query) use ($personIds, $guardianIds): void {
                $query->whereIn('person_id', $personIds)->orWhereIn('guardian_id', $guardianIds);
            })
            ->get();

        return [
            $records->whereNotNull('person_id')->mapWithKeys(
                fn (GateEntryDeliveryRecipient $record) => [(int) $record->person_id => $record->recipient_label]
            ),
            $records->whereNotNull('guardian_id')->mapWithKeys(
                fn (GateEntryDeliveryRecipient $record) => [(int) $record->guardian_id => $record->recipient_label]
            ),
        ];
    }

    protected function resetFilters(): void
    {
        $this->activeTab = 'entry';
        $this->search = '';
        $this->selectedCategory = 'all';
        $this->selectedStatus = 'all';
        $this->selectedDeliveryMethod = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    protected function normalizedDateInput(string $date): ?string
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date) === 1) {
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
