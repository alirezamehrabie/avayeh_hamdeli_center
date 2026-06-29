<?php

namespace App\Livewire\Guardians;

use App\Helpers\Morilog\Jalalian;
use App\Models\District;
use App\Models\Guardian;
use App\Models\GuardianSavedFilter;
use App\Models\InsuranceType;
use App\Models\JobType;
use App\Models\Occupation;
use App\Models\Person;
use App\Models\ResidenceStatusType;
use App\Models\SocialWorker;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\GuardiansExport;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class AdvancedGuardianReport extends Component
{
    use WithPagination;

    public string $globalSearch = '';
    public array $filters = [];
    public string $saveFilterName = '';
    public ?int $selectedGuardianId = null;
    public bool $showGuardianModal = false;
    public array $socialWorkerSearch = [];
    public array $socialWorkerOptions = [];
    public array $socialWorkerHasMore = [];
    public array $columnFilters = [];
    public ?string $sortColumn = null;
    public string $sortDirection = 'desc';
    public array $columnFilterDefinitions = [];

    public array $visibleColumns = [
        'guardian_code',
        'full_name',
        'national_code',
        'guardian_phone_number',
        'responsible_social_worker',
    ];

    public array $availableColumns = [
        'guardian_code' => 'کد خانوار',
        'full_name' => 'نام و نام خانوادگی',
        'national_code' => 'کد ملی',
        'guardian_phone_number' => 'موبایل',
        'responsible_social_worker' => 'مددکار مسئول',
        'guardian_birth_date' => 'تاریخ تولد',
        'guardian_age' => 'سن',
        'occupation' => 'شغل',
        'job_type' => 'نوع شغل',
        'economic_decile' => 'دهک اقتصادی',
        'average_income' => 'میانگین درآمد ماهانه',
        'children_count' => 'تعداد فرزندان تحت تکفل',
        'children_in_house' => 'تعداد اعضای خانوار',
        'beneficiaries_count' => 'تعداد مددجویان',
        'insurance_status' => 'بیمه',
        'has_vehicle' => 'وسیله نقلیه',
        'district' => 'منطقه / ناحیه',
        'housing_status' => 'وضعیت مسکن',
        'created_at' => 'تاریخ ثبت',
    ];

    public array $filterableFields = [
        'first_name' => ['label' => 'نام', 'type' => 'text'],
        'last_name' => ['label' => 'نام خانوادگی', 'type' => 'text'],
        'national_code' => ['label' => 'کد ملی', 'type' => 'text'],
        'guardian_phone_number' => ['label' => 'موبایل', 'type' => 'text'],
        'guardian_code' => ['label' => 'کد خانوار', 'type' => 'text'],
        'caseworker' => ['label' => 'مددکار (نام یا کد)', 'type' => 'text'],
        'guardian_birth_date' => ['label' => 'تاریخ تولد', 'type' => 'date'],
        'occupation' => ['label' => 'شغل', 'type' => 'select', 'options' => []],
        'job_type' => ['label' => 'نوع شغل', 'type' => 'select', 'options' => []],
        'economic_decile' => ['label' => 'دهک اقتصادی', 'type' => 'select', 'options' => []],
        'insurance_status_type' => ['label' => 'بیمه و نوع بیمه', 'type' => 'select', 'options' => []],
        'vehicle_status_type' => ['label' => 'وسیله نقلیه و نوع آن', 'type' => 'select', 'options' => []],
        'any_family_employed' => ['label' => 'اشتغال اعضای خانواده', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'divorced_child_at_home' => ['label' => 'فرزند طلاق در منزل', 'type' => 'select', 'options' => [
            'none' => 'ندارد',
            'boy' => 'پسر',
            'girl' => 'دختر',
            'both' => 'هر دو',
        ]],
        'housing_status' => ['label' => 'وضعیت مسکن', 'type' => 'select', 'options' => []],
        'district' => ['label' => 'منطقه / ناحیه', 'type' => 'select', 'options' => []],
        'is_local_to_city' => ['label' => 'بومی شهر', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'children_count' => ['label' => 'تعداد فرزندان تحت تکفل', 'type' => 'number'],
        'children_in_house' => ['label' => 'تعداد اعضای خانوار', 'type' => 'number'],
        'average_income' => ['label' => 'میانگین درآمد ماهانه', 'type' => 'number'],
        'beneficiaries_count' => ['label' => 'تعداد مددجویان', 'type' => 'number'],
    ];

    public function mount(): void
    {
        $this->filterableFields['occupation']['options'] = Occupation::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['job_type']['options'] = JobType::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['economic_decile']['options'] = collect(range(1, 10))
            ->mapWithKeys(fn ($item) => [(string) $item => 'دهک ' . $item])
            ->toArray();

        $insuranceOptions = ['status:1' => 'دارای بیمه', 'status:0' => 'فاقد بیمه'];
        foreach (InsuranceType::query()->orderBy('name')->get(['id', 'name']) as $item) {
            $insuranceOptions['type:' . $item->id] = 'نوع: ' . $item->name;
        }
        $this->filterableFields['insurance_status_type']['options'] = $insuranceOptions;

        $vehicleOptions = ['status:1' => 'دارای وسیله نقلیه', 'status:0' => 'فاقد وسیله نقلیه'];
        foreach (VehicleType::query()->orderBy('name')->get(['id', 'name']) as $item) {
            $vehicleOptions['type:' . $item->id] = 'نوع: ' . $item->name;
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

        $this->columnFilterDefinitions = [
            'guardian_code' => ['type' => 'text', 'placeholder' => 'کد خانوار'],
            'full_name' => ['type' => 'text', 'placeholder' => 'نام و نام خانوادگی'],
            'national_code' => ['type' => 'text', 'placeholder' => 'کد ملی'],
            'guardian_phone_number' => ['type' => 'text', 'placeholder' => 'موبایل'],
            'responsible_social_worker' => ['type' => 'text', 'placeholder' => 'نام یا کد مددکار'],
            'occupation' => ['type' => 'select', 'options' => $this->filterableFields['occupation']['options']],
            'job_type' => ['type' => 'select', 'options' => $this->filterableFields['job_type']['options']],
            'economic_decile' => ['type' => 'select', 'options' => $this->filterableFields['economic_decile']['options']],
            'district' => ['type' => 'select', 'options' => $this->filterableFields['district']['options']],
            'housing_status' => ['type' => 'select', 'options' => $this->filterableFields['housing_status']['options']],
            'children_count' => ['type' => 'number', 'placeholder' => 'تعداد فرزندان'],
            'children_in_house' => ['type' => 'number', 'placeholder' => 'اعضای خانوار'],
            'beneficiaries_count' => ['type' => 'number', 'placeholder' => 'تعداد مددجویان'],
        ];
    }

    public function updatingGlobalSearch(): void
    {
        $this->resetPage();
    }

    public function addFilter(string $field): void
    {
        if (!isset($this->filterableFields[$field])) {
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
                'number' => ['field' => $field, 'type' => $type, 'operator' => 'eq', 'value' => ''],
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
        $this->resetPage();
    }

    public function updatedColumnFilters(): void
    {
        $this->resetPage();
    }

    public function updatedSocialWorkerSearch($value, $key): void
    {
        $index = (int) $key;
        $term = trim((string) $value);

        if (!isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
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
        if (!isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
            return;
        }

        $worker = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->find($workerId);

        if (!$worker) {
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
            'name' => $worker->full_name,
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
        if (!isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
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
        if (!isset($this->filters[$index]) || ($this->filters[$index]['field'] ?? null) !== 'caseworker') {
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
        if (!isset($this->availableColumns[$column])) {
            return;
        }

        if (in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns = array_values(array_filter($this->visibleColumns, fn ($col) => $col !== $column));
            return;
        }

        $this->visibleColumns[] = $column;
    }

    public function toggleSort(string $column): void
    {
        if (!$this->isSortableColumn($column)) {
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

        GuardianSavedFilter::create([
            'user_id' => auth()->id(),
            'name' => $this->saveFilterName,
            'filters' => $this->filters,
            'global_search' => $this->globalSearch,
            'visible_columns' => $this->visibleColumns,
        ]);

        $this->saveFilterName = '';
        session()->flash('success', 'فیلتر با موفقیت ذخیره شد.');
    }

    public function loadSavedFilter(int $savedFilterId): void
    {
        $saved = GuardianSavedFilter::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedFilterId);

        $this->filters = $saved->filters ?? [];
        $this->normalizeCaseworkerFilters();
        $this->globalSearch = $saved->global_search ?? '';
        $this->visibleColumns = $saved->visible_columns ?: $this->visibleColumns;
        $this->columnFilters = [];
        $this->sortColumn = null;
        $this->sortDirection = 'desc';
        $this->socialWorkerSearch = [];
        $this->socialWorkerOptions = [];
        $this->socialWorkerHasMore = [];
        $this->resetPage();
    }

    public function deleteSavedFilter(int $savedFilterId): void
    {
        GuardianSavedFilter::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedFilterId)
            ->delete();

        session()->flash('success', 'فیلتر ذخیره شده حذف شد.');
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

        $filename = 'Export-(سرپرستان)-' . Jalalian::now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new GuardiansExport(
                query: $query,
                visibleColumns: $this->visibleColumns,
                availableColumns: $this->availableColumns,
            ),
            $filename
        );
    }

    public function showGuardianInfo(int $guardianId): void
    {
        $this->selectedGuardianId = $guardianId;
        $this->showGuardianModal = true;
    }

    public function closeGuardianModal(): void
    {
        $this->showGuardianModal = false;
        $this->selectedGuardianId = null;
    }

    public function getSelectedGuardianProperty(): ?Guardian
    {
        if (!$this->selectedGuardianId) {
            return null;
        }

        return Guardian::query()
            ->withCount('people')
            ->with([
                'socialWorker',
                'occupation',
                'jobType',
                'insuranceType',
                'vehicleType',
                'residence.district',
                'residence.residenceStatus',
                'people:id,guardian_id,first_name,last_name,person_code,national_id',
            ])
            ->find($this->selectedGuardianId);
    }

    protected function buildQuery(): Builder
    {
        $query = Guardian::query();

        if (trim($this->globalSearch) !== '') {
            $term = trim($this->globalSearch);
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('national_code', 'like', "%{$term}%")
                    ->orWhere('guardian_phone_number', 'like', "%{$term}%")
                    ->orWhere('guardian_code', 'like', "%{$term}%");
            });
        }

        foreach ($this->filters as $filter) {
            $field = $filter['field'] ?? null;
            $type = $filter['type'] ?? null;

            if (!$field || !isset($this->filterableFields[$field])) {
                continue;
            }

            if ($type === 'text') {
                $value = trim((string) ($filter['value'] ?? ''));
                $operator = $filter['operator'] ?? 'contains';

                if ($field === 'caseworker') {
                    $selectedWorkerIds = collect($filter['selected'] ?? [])
                        ->pluck('id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    if ($selectedWorkerIds->isNotEmpty()) {
                        $query->whereIn('social_worker_id', $selectedWorkerIds->all());
                        continue;
                    }

                    if ($value === '') {
                        continue;
                    }

                    $query->whereHas('socialWorker', function (Builder $workerQuery) use ($value) {
                        $workerQuery->autocompleteSearch($value);
                    });
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                if ($operator === 'exact') {
                    $query->where($field, $value);
                } else {
                    $query->where($field, 'like', "%{$value}%");
                }
            }

            if ($type === 'select') {
                $value = $filter['value'] ?? '';
                if ($value === '') {
                    continue;
                }

                if ($field === 'occupation') {
                    $query->where('occupation_id', $value);
                    continue;
                }

                if ($field === 'job_type') {
                    $query->where('job_type_id', $value);
                    continue;
                }

                if ($field === 'economic_decile') {
                    $query->where('economic_decile', (int) $value);
                    continue;
                }

                if ($field === 'any_family_employed') {
                    $query->where('any_family_employed', (int) $value);
                    continue;
                }

                if ($field === 'divorced_child_at_home') {
                    $query->whereIn('divorced_child_at_home', Guardian::divorcedChildAtHomeAliases((string) $value));
                    continue;
                }

                if ($field === 'insurance_status_type') {
                    if (str_starts_with((string) $value, 'status:')) {
                        $query->where('insurance_status', (int) str_replace('status:', '', (string) $value));
                    }
                    if (str_starts_with((string) $value, 'type:')) {
                        $query->where('insurance_type_id', (int) str_replace('type:', '', (string) $value));
                    }
                    continue;
                }

                if ($field === 'vehicle_status_type') {
                    if (str_starts_with((string) $value, 'status:')) {
                        $query->where('has_vehicle', (int) str_replace('status:', '', (string) $value));
                    }
                    if (str_starts_with((string) $value, 'type:')) {
                        $query->where('vehicle_type_id', (int) str_replace('type:', '', (string) $value));
                    }
                    continue;
                }

                if ($field === 'housing_status') {
                    $query->whereHas('residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('residence_status_id', $value);
                    });
                    continue;
                }

                if ($field === 'district') {
                    $query->whereHas('residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('district_id', $value);
                    });
                    continue;
                }

                if ($field === 'is_local_to_city') {
                    $query->whereHas('residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('is_local_to_city', (int) $value);
                    });
                    continue;
                }

                $query->where($field, $value);
            }

            if ($type === 'date') {
                $mode = $filter['mode'] ?? 'exact';
                if ($mode === 'exact' && !empty($filter['exact'])) {
                    [$year, $month, $day] = array_pad(explode('/', $filter['exact']), 3, null);
                    $query->where('guardian_birth_year', $year)
                        ->where('guardian_birth_month', $month)
                        ->where('guardian_birth_day', $day);
                }

                if ($mode === 'range' && !empty($filter['from']) && !empty($filter['to'])) {
                    [$fromYear, $fromMonth, $fromDay] = array_pad(explode('/', $filter['from']), 3, null);
                    [$toYear, $toMonth, $toDay] = array_pad(explode('/', $filter['to']), 3, null);
                    $query->whereRaw("CONCAT(guardian_birth_year, '-', LPAD(guardian_birth_month,2,'0'), '-', LPAD(guardian_birth_day,2,'0')) BETWEEN ? AND ?", [
                        sprintf('%04d-%02d-%02d', $fromYear, $fromMonth, $fromDay),
                        sprintf('%04d-%02d-%02d', $toYear, $toMonth, $toDay),
                    ]);
                }

                if ($mode === 'month' && !empty($filter['month'])) {
                    $query->where('guardian_birth_month', (int) $filter['month']);
                }

                if ($mode === 'year' && !empty($filter['year'])) {
                    $query->where('guardian_birth_year', (int) $filter['year']);
                }
            }

            if ($type === 'number') {
                $operator = $filter['operator'] ?? 'eq';
                $value = $filter['value'] ?? null;
                if ($value === '' || $value === null || !is_numeric($value)) {
                    continue;
                }

                $map = [
                    'eq' => '=',
                    'gt' => '>',
                    'lt' => '<',
                    'gte' => '>=',
                    'lte' => '<=',
                ];
                $sqlOperator = $map[$operator] ?? '=';
                $numericValue = (int) $value;

                if ($field === 'beneficiaries_count') {
                    $query->has('people', $sqlOperator, $numericValue);
                    continue;
                }

                $query->where($field, $sqlOperator, $numericValue);
            }
        }

        $this->applyColumnFilters($query);
        $this->applySorting($query);

        return $query->select('guardians.*');
    }

    public function getGuardiansProperty()
    {
        return $this->buildQuery()
            ->withCount('people')
            ->with([
                'socialWorker:id,first_name,last_name',
                'occupation:id,name',
                'jobType:id,name',
                'residence:id,guardian_id,residence_status_id,district_id',
                'residence.district:id,name',
                'residence.residenceStatus:id,name',
            ])
            ->paginate(20);
    }

    public function getSavedFiltersProperty()
    {
        return GuardianSavedFilter::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.guardians.advanced-guardian-report');
    }

    protected function normalizeCaseworkerFilters(): void
    {
        foreach ($this->filters as $index => $filter) {
            if (($filter['field'] ?? null) !== 'caseworker') {
                continue;
            }

            $selected = $filter['selected'] ?? [];
            if (!is_array($selected)) {
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

        $workers = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
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
            ->map(fn (SocialWorker $worker) => [
                'id' => $worker->id,
                'name' => $worker->full_name,
                'code' => (string) $worker->worker_code,
                'label' => sprintf('%s - ID: %s', $worker->full_name, $worker->worker_code),
            ])
            ->values()
            ->all();

        $this->socialWorkerHasMore[$index] = $hasMore;
    }

    public function isSortableColumn(string $column): bool
    {
        return in_array($column, [
            'guardian_code',
            'full_name',
            'national_code',
            'guardian_phone_number',
            'responsible_social_worker',
            'guardian_birth_date',
            'guardian_age',
            'economic_decile',
            'average_income',
            'children_count',
            'children_in_house',
            'beneficiaries_count',
            'created_at',
        ], true);
    }

    protected function applyColumnFilters(Builder $query): void
    {
        foreach ($this->columnFilters as $column => $value) {
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                continue;
            }

            switch ($column) {
                case 'guardian_code':
                case 'national_code':
                case 'guardian_phone_number':
                    $query->where($column, 'like', '%' . $value . '%');
                    break;

                case 'full_name':
                    $query->where(function (Builder $subQuery) use ($value) {
                        $subQuery->where('first_name', 'like', '%' . $value . '%')
                            ->orWhere('last_name', 'like', '%' . $value . '%');
                    });
                    break;

                case 'occupation':
                    $query->where('occupation_id', $value);
                    break;

                case 'job_type':
                    $query->where('job_type_id', $value);
                    break;

                case 'economic_decile':
                    $query->where('economic_decile', (int) $value);
                    break;

                case 'children_count':
                case 'children_in_house':
                    $query->where($column, (int) $value);
                    break;

                case 'beneficiaries_count':
                    $query->has('people', '=', (int) $value);
                    break;

                case 'district':
                    $query->whereHas('residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('district_id', $value);
                    });
                    break;

                case 'housing_status':
                    $query->whereHas('residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('residence_status_id', $value);
                    });
                    break;

                case 'responsible_social_worker':
                    $query->whereHas('socialWorker', function (Builder $workerQuery) use ($value) {
                        $workerQuery->autocompleteSearch((string) $value);
                    });
                    break;
            }
        }
    }

    protected function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        switch ($this->sortColumn) {
            case 'guardian_code':
            case 'national_code':
            case 'guardian_phone_number':
            case 'economic_decile':
            case 'average_income':
            case 'children_count':
            case 'children_in_house':
                $query->orderBy($this->sortColumn, $direction);
                break;

            case 'full_name':
                $query->orderBy('last_name', $direction)
                    ->orderBy('first_name', $direction);
                break;

            case 'guardian_birth_date':
            case 'guardian_age':
                $query->orderBy('guardian_birth_year', $direction)
                    ->orderBy('guardian_birth_month', $direction)
                    ->orderBy('guardian_birth_day', $direction);
                break;

            case 'beneficiaries_count':
                $query->orderBy(
                    Person::query()
                        ->selectRaw('count(*)')
                        ->whereColumn('people.guardian_id', 'guardians.id'),
                    $direction
                );
                break;

            case 'responsible_social_worker':
                $query->orderBy(
                    SocialWorker::query()
                        ->select('last_name')
                        ->whereColumn('social_workers.id', 'guardians.social_worker_id')
                        ->limit(1),
                    $direction
                )->orderBy(
                    SocialWorker::query()
                        ->select('first_name')
                        ->whereColumn('social_workers.id', 'guardians.social_worker_id')
                        ->limit(1),
                    $direction
                );
                break;

            case 'created_at':
                $query->orderBy('created_at', $direction);
                break;

            default:
                $query->latest();
                break;
        }

        if ($this->sortColumn !== null && !in_array($this->sortColumn, ['created_at'], true)) {
            $query->orderBy('created_at', 'desc');
        }
    }
}
