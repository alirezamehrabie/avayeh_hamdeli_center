<?php

namespace App\Livewire\People;

use App\Contracts\Ai\GeneratesBeneficiarySearchFilters;
use App\Exceptions\AiBeneficiarySearchException;
use App\Exports\PeopleExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\BeneficiarySavedFilter;
use App\Models\DisabilityType;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\GuardianRelationType;
use App\Models\HarmType;
use App\Models\InsuranceType;
use App\Models\JobType;
use App\Models\NeedLevelType;
use App\Models\Occupation;
use App\Models\Person;
use App\Models\ResidenceStatusType;
use App\Models\Skill;
use App\Models\SocialWorker;
use App\Models\SupportOrganization;
use App\Models\VehicleType;
use App\Queries\People\BeneficiaryReportQuery;
use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportFieldRegistry;
use App\Reports\People\BeneficiaryReportSemantics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

#[Layout('layouts.app')]
class AdvancedFilterBuilder extends Component
{
    use WithPagination;

    public string $globalSearch = '';

    public array $filters = [];

    public string $saveFilterName = '';

    public ?int $selectedPersonId = null;

    public bool $showPersonModal = false;

    public array $socialWorkerSearch = [];

    public array $socialWorkerOptions = [];

    public array $socialWorkerHasMore = [];

    public array $columnFilters = [];

    public ?string $sortColumn = null;

    public string $sortDirection = 'desc';

    public array $columnFilterDefinitions = [];

    public string $aiSearchQuery = '';

    public ?array $pendingAiSearch = null;

    public ?string $aiSearchError = null;

    public array $visibleColumns = BeneficiaryReportColumnRegistry::DEFAULT_COLUMNS;

    public array $availableColumns = [];

    public array $filterableFields = [];

    public function mount(): void
    {
        $fieldRegistry = app(BeneficiaryReportFieldRegistry::class);
        $columnRegistry = app(BeneficiaryReportColumnRegistry::class);

        $this->filterableFields = $fieldRegistry->definitions();
        $this->availableColumns = $columnRegistry->labels();
        $this->visibleColumns = $columnRegistry->normalizeVisibleColumns($this->visibleColumns);

        $this->filterableFields['skills']['options'] = Skill::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['harm_types']['options'] = HarmType::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $disabilityOptions = [
            'has:1' => 'دارای معلولیت',
            'has:0' => 'فاقد معلولیت',
        ];
        foreach (DisabilityType::query()->orderBy('name')->get(['id', 'name']) as $type) {
            $disabilityOptions['type:'.$type->id] = 'نوع: '.$type->name;
        }
        $this->filterableFields['disability_status_type']['options'] = $disabilityOptions;

        $this->filterableFields['education_level']['options'] = EducationLevel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['guardian_relation']['options'] = GuardianRelationType::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $this->filterableFields['economic_decile']['options'] = collect(range(1, 10))
            ->mapWithKeys(fn ($item) => [(string) $item => 'دهک '.$item])
            ->toArray();

        $this->filterableFields['guardian_occupation']['options'] = Occupation::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['guardian_job_type']['options'] = JobType::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $insuranceOptions = ['status:1' => 'دارای بیمه', 'status:0' => 'فاقد بیمه'];
        foreach (InsuranceType::query()->orderBy('name')->get(['id', 'name']) as $item) {
            $insuranceOptions['type:'.$item->id] = 'نوع: '.$item->name;
        }
        $this->filterableFields['insurance_status_type']['options'] = $insuranceOptions;

        $vehicleOptions = ['status:1' => 'دارای وسیله نقلیه', 'status:0' => 'فاقد وسیله نقلیه'];
        foreach (VehicleType::query()->orderBy('name')->get(['id', 'name']) as $item) {
            $vehicleOptions['type:'.$item->id] = 'نوع: '.$item->name;
        }
        $this->filterableFields['vehicle_status_type']['options'] = $vehicleOptions;

        $this->filterableFields['housing_status']['options'] = ResidenceStatusType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['district']['options'] = District::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['support_organization']['options'] = SupportOrganization::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['need_level']['options'] = NeedLevelType::query()
            ->orderBy('severity_order')
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $this->columnFilterDefinitions = $columnRegistry->filterDefinitions(
            $this->filterableFields['gender']['options'],
            $this->filterableFields['sadaat_status']['options'],
        );
    }

    public function updatingGlobalSearch(): void
    {
        $this->resetPage();
    }

    public function addFilter(string $field): void
    {
        if (! isset($this->filterableFields[$field])) {
            return;
        }

        $type = $this->filterableFields[$field]['type'];

        if ($field === 'caseworker') {
            $this->filters[] = ['field' => $field, 'type' => 'text', 'operator' => 'contains', 'value' => '', 'selected' => []];
        } else {
            $this->filters[] = match ($type) {
                'text' => ['field' => $field, 'type' => $type, 'operator' => 'contains', 'value' => ''],
                'select' => ['field' => $field, 'type' => $type, 'value' => ''],
                'date' => ['field' => $field, 'type' => $type, 'mode' => 'exact', 'exact' => '', 'from' => '', 'to' => '', 'month' => '', 'year' => ''],
                'number' => [
                    'field' => $field,
                    'type' => $type,
                    'operator' => $field === 'months_without_service' ? 'gte' : 'eq',
                    'value' => '',
                ],
                default => ['field' => $field, 'type' => $type],
            };
        }

        $this->resetPage();
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
        $this->socialWorkerSearch = array_values($this->socialWorkerSearch);
        $this->socialWorkerOptions = array_values($this->socialWorkerOptions);
        $this->socialWorkerHasMore = array_values($this->socialWorkerHasMore);
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->normalizeCaseworkerFilters();
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->filters = [];
        $this->columnFilters = [];
        $this->globalSearch = '';
        $this->sortColumn = null;
        $this->sortDirection = 'desc';
        $this->socialWorkerSearch = [];
        $this->socialWorkerOptions = [];
        $this->socialWorkerHasMore = [];
        $this->pendingAiSearch = null;
        $this->aiSearchError = null;
        $this->resetPage();
    }

    public function interpretAiSearch(GeneratesBeneficiarySearchFilters $generator): void
    {
        $this->validate([
            'aiSearchQuery' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'aiSearchQuery.required' => 'درخواست جستجو را وارد کنید.',
            'aiSearchQuery.min' => 'درخواست جستجو باید حداقل ۳ نویسه باشد.',
            'aiSearchQuery.max' => 'درخواست جستجو بیش از حد طولانی است.',
        ]);

        $this->pendingAiSearch = null;
        $this->aiSearchError = null;

        try {
            $interpretation = $generator->generate($this->aiSearchQuery, $this->aiFilterCatalog());
        } catch (AiBeneficiarySearchException $exception) {
            report($exception);
            $this->aiSearchError = 'تفسیر هوشمند جستجو انجام نشد. پیکربندی سرویس را بررسی کنید یا دوباره تلاش کنید.';

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->aiSearchError = 'تفسیر جستجو با خطا روبه‌رو شد. دوباره تلاش کنید.';

            return;
        }

        if ($interpretation['filters'] === []) {
            $this->aiSearchError = $interpretation['unresolved'][0] ?? 'هیچ فیلتر قابل اجرایی از این درخواست استخراج نشد.';

            return;
        }

        $this->pendingAiSearch = $interpretation;
    }

    public function confirmAiSearch(): void
    {
        if (! is_array($this->pendingAiSearch) || ! is_array($this->pendingAiSearch['filters'] ?? null)) {
            return;
        }

        $this->filters = array_values($this->pendingAiSearch['filters']);
        $this->globalSearch = '';
        $this->columnFilters = [];
        $this->socialWorkerSearch = [];
        $this->socialWorkerOptions = [];
        $this->socialWorkerHasMore = [];
        $this->normalizeCaseworkerFilters();
        $this->pendingAiSearch = null;
        $this->aiSearchError = null;
        $this->resetPage();

        session()->flash('success', 'فیلترهای جستجوی هوشمند اعمال شدند.');
    }

    public function cancelAiSearch(): void
    {
        $this->pendingAiSearch = null;
        $this->aiSearchError = null;
    }

    public function updatedColumnFilters(): void
    {
        $this->resetPage();
    }

    public function updatedSocialWorkerSearch($value, $key): void
    {
        $index = (int) $key;
        $term = trim((string) $value);

        if (! isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
            return;
        }

        if (mb_strlen($term) < 2) {
            $this->socialWorkerOptions[$index] = [];
            $this->socialWorkerHasMore[$index] = false;

            return;
        }

        $this->loadSocialWorkerOptions($index, $term);
    }

    public function selectSocialWorker(int $index, int $workerId): void
    {
        if (! isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
            return;
        }

        $worker = app(BeneficiaryReportSemantics::class)
            ->historicalWorkerQuery()
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'is_active', 'deleted_at'])
            ->find($workerId);

        if (! $worker) {
            return;
        }

        $selected = collect($this->filters[$index]['selected'] ?? []);
        if ($selected->contains(fn ($item) => (int) ($item['id'] ?? 0) === $worker->id)) {
            $this->socialWorkerSearch[$index] = '';
            $this->socialWorkerOptions[$index] = [];

            return;
        }

        $selected->push([
            'id' => $worker->id,
            'name' => app(BeneficiaryReportSemantics::class)->workerLabel($worker),
            'code' => (string) $worker->worker_code,
        ]);

        $this->filters[$index]['selected'] = $selected->values()->all();
        $this->filters[$index]['value'] = '';
        $this->socialWorkerSearch[$index] = '';
        $this->socialWorkerOptions[$index] = [];
        $this->socialWorkerHasMore[$index] = false;
        $this->syncCaseworkerFilterState();
    }

    public function removeSocialWorker(int $index, int $workerId): void
    {
        if (! isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
            return;
        }

        $this->filters[$index]['selected'] = collect($this->filters[$index]['selected'] ?? [])
            ->reject(fn ($item) => (int) ($item['id'] ?? 0) === $workerId)
            ->values()
            ->all();

        $this->syncCaseworkerFilterState();
    }

    public function loadMoreSocialWorkers(int $index): void
    {
        if (! isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
            return;
        }

        $term = trim((string) ($this->socialWorkerSearch[$index] ?? ''));
        if (mb_strlen($term) < 2) {
            return;
        }

        $this->loadSocialWorkerOptions($index, $term, true);
    }

    public function toggleColumn(string $column): void
    {
        if (! isset($this->availableColumns[$column])) {
            return;
        }

        if (in_array($column, $this->visibleColumns, true)) {
            if (count($this->visibleColumns) === 1) {
                return;
            }

            $this->visibleColumns = array_values(array_filter($this->visibleColumns, fn ($col) => $col !== $column));

            return;
        }

        $this->visibleColumns[] = $column;
    }

    public function toggleSort(string $column): void
    {
        if (! $this->isSortableColumn($column)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = in_array($column, ['created_at'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function saveCurrentFilters(): void
    {
        $this->validate([
            'saveFilterName' => ['required', 'string', 'max:120'],
        ]);

        $criteria = $this->reportCriteria();
        $columnRegistry = app(BeneficiaryReportColumnRegistry::class);

        BeneficiarySavedFilter::create([
            'user_id' => auth()->id(),
            'criteria_version' => $criteria->version,
            'name' => $this->saveFilterName,
            'filters' => $criteria->filters,
            'global_search' => $criteria->globalSearch,
            'visible_columns' => $columnRegistry->normalizeVisibleColumns($this->visibleColumns),
        ]);

        $this->saveFilterName = '';
        session()->flash('success', 'فیلتر با موفقیت ذخیره شد.');
    }

    public function loadSavedFilter(int $savedFilterId): void
    {
        $saved = BeneficiarySavedFilter::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedFilterId);

        $fieldRegistry = app(BeneficiaryReportFieldRegistry::class);
        $columnRegistry = app(BeneficiaryReportColumnRegistry::class);

        $this->filters = $fieldRegistry->normalizeFilters($saved->filters ?? []);
        $this->globalSearch = trim((string) ($saved->global_search ?? ''));
        $this->visibleColumns = $columnRegistry->normalizeVisibleColumns(
            $saved->visible_columns ?: $this->visibleColumns
        );
        $this->columnFilters = [];
        $this->sortColumn = null;
        $this->sortDirection = 'desc';
        $this->socialWorkerSearch = [];
        $this->socialWorkerOptions = [];
        $this->socialWorkerHasMore = [];
        $this->resetPage();
    }

    public function exportToExcel()
    {
        // محدودیت ۵۰۰۰ رکورد برای جلوگیری از timeout
        $maxRows = 5000;
        $count = $this->buildQuery()->count();

        if ($count === 0) {
            session()->flash('error', 'هیچ رکوردی برای خروجی وجود ندارد.');

            return null;
        }

        if ($count > $maxRows) {
            session()->flash('error', "تعداد نتایج ({$count}) بیش از حد مجاز ({$maxRows}) است. لطفاً فیلترها را محدودتر کنید.");

            return null;
        }

        $query = $this->buildQuery()->limit($maxRows);

        $filename = 'Export-(مددجویان)-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new PeopleExport(
                query: $query,
                visibleColumns: $this->visibleColumns,
            ),
            $filename
        );
    }

    public function deleteSavedFilter(int $savedFilterId): void
    {
        BeneficiarySavedFilter::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedFilterId)
            ->delete();

        session()->flash('success', 'فیلتر ذخیره شده حذف شد.');
    }

    public function exportToPdf()
    {
        if (! app()->bound('dompdf.wrapper')) {
            session()->flash('error', 'امکان تولید PDF فعال نیست. لطفاً پکیج barryvdh/laravel-dompdf را نصب کنید.');

            return null;
        }

        $people = $this->buildQuery()->limit(1000)->get();
        $totalCount = $people->count();
        $maleCount = $people->where('gender', 'male')->count();
        $femaleCount = $people->where('gender', 'female')->count();
        $sadaatCount = $people->where('sadaat_status', 'sadaat')->count();
        $generalCount = $people->where('sadaat_status', 'general')->count();

        $birthMonthStats = collect(Person::$months)->map(function ($label, $month) use ($people) {
            return [
                'label' => $label,
                'count' => $people->where('birth_month', (int) $month)->count(),
            ];
        })->values();

        $pdf = app('dompdf.wrapper')->loadView('livewire.people.partials.advanced-beneficiary-report-pdf', [
            'people' => $people,
            'totalCount' => $totalCount,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'sadaatCount' => $sadaatCount,
            'generalCount' => $generalCount,
            'birthMonthStats' => $birthMonthStats,
            'globalSearch' => $this->globalSearch,
            'activeFilters' => $this->filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'advanced-beneficiary-report-'.Str::slug((string) now()).'.pdf');
    }

    public function showPersonInfo(int $personId): void
    {
        $this->selectedPersonId = $personId;
        $this->showPersonModal = true;
    }

    public function closePersonModal(): void
    {
        $this->showPersonModal = false;
        $this->selectedPersonId = null;
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if (! $this->selectedPersonId) {
            return null;
        }

        return Person::with([
            'guardian.occupation',
            'guardian.jobType',
            'guardian.residence',
            'guardian.socialWorker' => fn ($query) => app(BeneficiaryReportSemantics::class)
                ->includeHistoricalWorkers($query),
            'education.educationLevel',
            'education.educationDegreeLevel',
            'supportCoverage.organization',
            'disabilityType',
            'familyStatus.guardianRelationType',
            'skills',
            'harmTypes',
            'needsLevel.levelType',
        ])->find($this->selectedPersonId);
    }

    protected function buildQuery(): Builder
    {
        return app(BeneficiaryReportQuery::class)->build($this->reportCriteria());
    }

    public function getPeopleProperty()
    {
        return $this->buildQuery()
            ->with(app(BeneficiaryReportColumnRegistry::class)->eagerLoads())
            ->paginate(20);
    }

    protected function reportCriteria(): BeneficiaryReportCriteria
    {
        return BeneficiaryReportCriteria::fromLegacyState(
            globalSearch: $this->globalSearch,
            filters: $this->filters,
            columnFilters: $this->columnFilters,
            sortColumn: $this->sortColumn,
            sortDirection: $this->sortDirection,
            fieldRegistry: app(BeneficiaryReportFieldRegistry::class),
            columnRegistry: app(BeneficiaryReportColumnRegistry::class),
        );
    }

    public function getSavedFiltersProperty()
    {
        return BeneficiarySavedFilter::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.people.advanced-filter-builder', [
            'reportColumnRegistry' => app(BeneficiaryReportColumnRegistry::class),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function aiFilterCatalog(): array
    {
        return collect($this->filterableFields)
            ->except(['caseworker'])
            ->map(fn (array $definition): array => [
                'label' => $definition['label'],
                'type' => $definition['type'],
                'options' => $definition['options'] ?? [],
            ])
            ->all();
    }

    protected function normalizeCaseworkerFilters(): void
    {
        foreach ($this->filters as $index => $filter) {
            if (($filter['field'] ?? null) !== 'caseworker') {
                continue;
            }

            $selected = $filter['selected'] ?? [];
            if (! is_array($selected)) {
                $selected = [];
            }

            $this->filters[$index]['selected'] = collect($selected)
                ->filter(fn ($item) => is_array($item) && isset($item['id']))
                ->map(fn ($item) => [
                    'id' => (int) $item['id'],
                    'name' => (string) ($item['name'] ?? ''),
                    'code' => (string) ($item['code'] ?? ''),
                ])
                ->values()
                ->all();
        }
    }

    protected function syncCaseworkerFilterState(): void
    {
        $this->normalizeCaseworkerFilters();
        $this->filters = array_values($this->filters);
        $this->resetPage();
    }

    protected function loadSocialWorkerOptions(int $index, string $term, bool $append = false): void
    {
        $limit = $append ? count($this->socialWorkerOptions[$index] ?? []) + 25 : 25;

        $workers = app(BeneficiaryReportSemantics::class)
            ->historicalWorkerQuery()
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'is_active', 'deleted_at'])
            ->autocompleteSearch($term)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit + 1)
            ->get();

        $selectedIds = collect($this->filters[$index]['selected'] ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $hasMore = $workers->count() > $limit;

        $this->socialWorkerOptions[$index] = $workers
            ->take($limit)
            ->reject(fn (SocialWorker $worker) => in_array($worker->id, $selectedIds, true))
            ->map(function (SocialWorker $worker): array {
                $workerLabel = app(BeneficiaryReportSemantics::class)->workerLabel($worker);

                return [
                    'id' => $worker->id,
                    'name' => $workerLabel,
                    'code' => (string) $worker->worker_code,
                    'label' => sprintf('%s - ID: %s', $workerLabel, $worker->worker_code),
                ];
            })
            ->values()
            ->all();

        $this->socialWorkerHasMore[$index] = $hasMore;
    }

    public function isSortableColumn(string $column): bool
    {
        return app(BeneficiaryReportColumnRegistry::class)->isSortable($column);
    }
}
