<?php

namespace App\Livewire\People;

use App\Helpers\Morilog\Jalalian;
use App\Models\BeneficiarySavedFilter;
use App\Models\DisabilityType;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Guardian;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\PeopleExport;
use Maatwebsite\Excel\Facades\Excel;

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

    public array $visibleColumns = [
        'person_code',
        'full_name',
        'national_id',
        'phone_number',
        'birth_date',
    ];

    public array $availableColumns = [
        'person_code' => 'کد مددجو',
        'full_name' => 'نام و نام خانوادگی',
        'responsible_social_worker' => 'مددکار مسئول',
        'guardian_beneficiary_with_code' => 'سرپرست و کد خانوار',
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'national_id' => 'کد ملی',
        'phone_number' => 'موبایل',
        'gender' => 'جنسیت',
        'sadaat_status' => 'سادات',
        'birth_date' => 'تاریخ تولد',
        'birth_year' => 'سال تولد',
        'birth_month' => 'ماه تولد',
        'beneficiary_injury_disability_type' => 'آسیب / نوع آسیب',
        'related_description' => 'معلولیت / نوع معلولیت',
        'disability_description' => 'شرح معلولیت / بیماری',
        'client_case_history' => 'سوابق / شرح وضعیت مددجو',
//        'created_at' => 'تاریخ ثبت',
    ];

    public array $filterableFields = [
        'first_name' => ['label' => 'نام', 'type' => 'text'],
        'last_name' => ['label' => 'نام خانوادگی', 'type' => 'text'],
        'birth_date' => ['label' => 'تاریخ تولد', 'type' => 'date'],
        'national_id' => ['label' => 'کد ملی', 'type' => 'text'],
        'phone_number' => ['label' => 'موبایل', 'type' => 'text'],
        'sadaat_status' => ['label' => 'سادات', 'type' => 'select', 'options' => [
            'sadaat' => 'سادات',
            'general' => 'غیر سادات',
        ]],
        'gender' => ['label' => 'جنسیت', 'type' => 'select', 'options' => [
            'male' => 'پسر',
            'female' => 'دختر',
        ]],
        'skills' => ['label' => 'مهارت‌ها', 'type' => 'select', 'options' => []],
        'harm_types' => ['label' => 'نوع آسیب', 'type' => 'select', 'options' => []],
        'disability_status_type' => ['label' => 'معلولیت و نوع معلولیت', 'type' => 'select', 'options' => []],
        'education_status' => ['label' => 'وضعیت تحصیلی', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'education_level' => ['label' => 'مقطع تحصیلی', 'type' => 'select', 'options' => []],
        'works_alongside_study' => ['label' => 'همزمان با تحصیل کار می‌کند', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'guardian_relation' => ['label' => 'نسبت با سرپرست', 'type' => 'select', 'options' => []],
        'guardian_national_code' => ['label' => 'کد ملی سرپرست', 'type' => 'text'],
        'has_parent_disability' => ['label' => 'معلولیت والدین', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'guardian_full_name' => ['label' => 'نام و نام خانوادگی سرپرست', 'type' => 'text'],
        'guardian_mobile' => ['label' => 'موبایل سرپرست', 'type' => 'text'],
        'caseworker' => ['label' => 'مددکار (نام یا کد)', 'type' => 'text'],
        'economic_decile' => ['label' => 'دهک اقتصادی', 'type' => 'select', 'options' => []],
        'guardian_occupation' => ['label' => 'شغل سرپرست', 'type' => 'select', 'options' => []],
        'guardian_job_type' => ['label' => 'نوع شغل سرپرست', 'type' => 'select', 'options' => []],
        'supported_children_count' => ['label' => 'تعداد فرزندان تحت تکفل', 'type' => 'number'],
        'insurance_status_type' => ['label' => 'بیمه و نوع بیمه', 'type' => 'select', 'options' => []],
        'vehicle_status_type' => ['label' => 'وسیله نقلیه و نوع آن', 'type' => 'select', 'options' => []],
        'average_income' => ['label' => 'میانگین درآمد ماهانه', 'type' => 'number'],
        'housing_status' => ['label' => 'وضعیت مسکن', 'type' => 'select', 'options' => []],
        'district' => ['label' => 'منطقه / ناحیه', 'type' => 'select', 'options' => []],
        'is_local_to_city' => ['label' => 'بومی شهر', 'type' => 'select', 'options' => [
            '1' => 'بله',
            '0' => 'خیر',
        ]],
        'support_organization' => ['label' => 'نوع نهاد حمایتی', 'type' => 'select', 'options' => []],
        'need_level' => ['label' => 'سطح نیازمندی', 'type' => 'select', 'options' => []],
    ];

    public function mount(): void
    {
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
            $disabilityOptions['type:' . $type->id] = 'نوع: ' . $type->name;
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
            ->mapWithKeys(fn ($item) => [(string) $item => 'دهک ' . $item])
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

        $this->filterableFields['support_organization']['options'] = SupportOrganization::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['need_level']['options'] = NeedLevelType::query()
            ->orderBy('severity_order')
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $this->columnFilterDefinitions = [
            'person_code' => ['type' => 'text', 'placeholder' => 'کد مددجو'],
            'full_name' => ['type' => 'text', 'placeholder' => 'نام و نام خانوادگی'],
            'responsible_social_worker' => ['type' => 'text', 'placeholder' => 'نام یا کد مددکار'],
            'guardian_beneficiary_with_code' => ['type' => 'text', 'placeholder' => 'سرپرست یا کد خانوار'],
            'first_name' => ['type' => 'text', 'placeholder' => 'نام'],
            'last_name' => ['type' => 'text', 'placeholder' => 'نام خانوادگی'],
            'national_id' => ['type' => 'text', 'placeholder' => 'کد ملی'],
            'phone_number' => ['type' => 'text', 'placeholder' => 'موبایل'],
            'gender' => ['type' => 'select', 'options' => $this->filterableFields['gender']['options']],
            'sadaat_status' => ['type' => 'select', 'options' => $this->filterableFields['sadaat_status']['options']],
            'birth_year' => ['type' => 'number', 'placeholder' => 'سال تولد'],
            'birth_month' => ['type' => 'select', 'options' => Person::$months],
            'related_description' => ['type' => 'text', 'placeholder' => 'شرح معلولیت'],
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

        BeneficiarySavedFilter::create([
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
        $saved = BeneficiarySavedFilter::query()
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

        $filename = 'Export-(مددجویان)-' . Jalalian::now()->format('Y-m-d') . '.xlsx';


        return Excel::download(
            new PeopleExport(
                query: $query,
                visibleColumns: $this->visibleColumns,
                availableColumns: $this->availableColumns,
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
        if (!app()->bound('dompdf.wrapper')) {
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
        }, 'advanced-beneficiary-report-' . Str::slug((string) now()) . '.pdf');
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
        if (!$this->selectedPersonId) {
            return null;
        }

        return Person::with([
            'guardian.occupation',
            'guardian.jobType',
            'guardian.residence',
            'guardian.socialWorker',
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
        $query = Person::query();

        if (trim($this->globalSearch) !== '') {
            $term = trim($this->globalSearch);
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->where('full_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('phone_number', 'like', "%{$term}%");
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
                        $query->whereHas('guardian.socialWorker', function (Builder $workerQuery) use ($selectedWorkerIds) {
                            $workerQuery->whereIn('social_workers.id', $selectedWorkerIds->all());
                        });
                        continue;
                    }

                    if ($value === '') {
                        continue;
                    }

                    $query->whereHas('guardian.socialWorker', function (Builder $workerQuery) use ($value) {
                        $workerQuery->autocompleteSearch($value);
                    });
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                if ($field === 'guardian_national_code') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value, $operator) {
                        if ($operator === 'exact') {
                            $guardianQuery->where('national_code', $value);
                        } else {
                            $guardianQuery->where('national_code', 'like', "%{$value}%");
                        }
                    });
                    continue;
                }

                if ($field === 'guardian_full_name') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                        $guardianQuery->where(function (Builder $nameQuery) use ($value) {
                            $nameQuery->where('first_name', 'like', "%{$value}%")
                                ->orWhere('last_name', 'like', "%{$value}%");
                        });
                    });
                    continue;
                }

                if ($field === 'guardian_mobile') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value, $operator) {
                        if ($operator === 'exact') {
                            $guardianQuery->where('guardian_phone_number', $value);
                        } else {
                            $guardianQuery->where('guardian_phone_number', 'like', "%{$value}%");
                        }
                    });
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

                if ($field === 'skills') {
                    $query->whereHas('skills', function (Builder $skillQuery) use ($value) {
                        $skillQuery->where('skills.id', $value);
                    });
                    continue;
                }

                if ($field === 'harm_types') {
                    $query->whereHas('harmTypes', function (Builder $harmQuery) use ($value) {
                        $harmQuery->where('harm_types.id', $value);
                    });
                    continue;
                }

                if ($field === 'disability_status_type') {
                    if (str_starts_with((string) $value, 'has:')) {
                        $query->where('has_disability', (int) str_replace('has:', '', (string) $value));
                    }
                    if (str_starts_with((string) $value, 'type:')) {
                        $query->where('disability_type_id', (int) str_replace('type:', '', (string) $value));
                    }
                    continue;
                }

                if ($field === 'education_status') {
                    $query->whereHas('education', function (Builder $educationQuery) use ($value) {
                        $educationQuery->where('is_studying', (int) $value);
                    });
                    continue;
                }

                if ($field === 'education_level') {
                    $query->whereHas('education', function (Builder $educationQuery) use ($value) {
                        $educationQuery->where('education_level_id', $value);
                    });
                    continue;
                }

                if ($field === 'works_alongside_study') {
                    $query->whereHas('education', function (Builder $educationQuery) use ($value) {
                        $educationQuery->where('works_alongside_study', (int) $value);
                    });
                    continue;
                }

                if ($field === 'guardian_relation') {
                    $query->whereHas('familyStatus', function (Builder $familyStatusQuery) use ($value) {
                        $familyStatusQuery->where('guardian_relation_type_id', $value);
                    });
                    continue;
                }

                if ($field === 'has_parent_disability') {
                    $query->whereHas('familyStatus', function (Builder $familyStatusQuery) use ($value) {
                        $familyStatusQuery->where('has_parent_disability', (int) $value);
                    });
                    continue;
                }

                if ($field === 'economic_decile') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                        $guardianQuery->where('economic_decile', (int) $value);
                    });
                    continue;
                }

                if ($field === 'guardian_occupation') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                        $guardianQuery->where('occupation_id', $value);
                    });
                    continue;
                }

                if ($field === 'guardian_job_type') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                        $guardianQuery->where('job_type_id', $value);
                    });
                    continue;
                }

                if ($field === 'insurance_status_type') {
                    if (str_starts_with((string) $value, 'status:')) {
                        $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                            $guardianQuery->where('insurance_status', (int) str_replace('status:', '', (string) $value));
                        });
                    }
                    if (str_starts_with((string) $value, 'type:')) {
                        $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                            $guardianQuery->where('insurance_type_id', (int) str_replace('type:', '', (string) $value));
                        });
                    }
                    continue;
                }

                if ($field === 'vehicle_status_type') {
                    if (str_starts_with((string) $value, 'status:')) {
                        $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                            $guardianQuery->where('has_vehicle', (int) str_replace('status:', '', (string) $value));
                        });
                    }
                    if (str_starts_with((string) $value, 'type:')) {
                        $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                            $guardianQuery->where('vehicle_type_id', (int) str_replace('type:', '', (string) $value));
                        });
                    }
                    continue;
                }

                if ($field === 'housing_status') {
                    $query->whereHas('guardian.residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('residence_status_id', $value);
                    });
                    continue;
                }

                if ($field === 'district') {
                    $query->whereHas('guardian.residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('district_id', $value);
                    });
                    continue;
                }

                if ($field === 'is_local_to_city') {
                    $query->whereHas('guardian.residence', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('is_local_to_city', (int) $value);
                    });
                    continue;
                }

                if ($field === 'support_organization') {
                    $query->whereHas('supportCoverage', function (Builder $supportCoverageQuery) use ($value) {
                        $supportCoverageQuery->where('support_organization_id', $value);
                    });
                    continue;
                }

                if ($field === 'need_level') {
                    $query->whereHas('needsLevel', function (Builder $needsLevelQuery) use ($value) {
                        $needsLevelQuery->where('need_level_id', $value);
                    });
                    continue;
                }

                if ($value !== '') {
                    $query->where($field, $value);
                }
            }

            if ($type === 'date') {
                $mode = $filter['mode'] ?? 'exact';
                if ($mode === 'exact' && !empty($filter['exact'])) {
                    [$year, $month, $day] = array_pad(explode('/', $filter['exact']), 3, null);
                    $query->where('birth_year', $year)->where('birth_month', $month)->where('birth_day', $day);
                }

                if ($mode === 'range' && !empty($filter['from']) && !empty($filter['to'])) {
                    [$fromYear, $fromMonth, $fromDay] = array_pad(explode('/', $filter['from']), 3, null);
                    [$toYear, $toMonth, $toDay] = array_pad(explode('/', $filter['to']), 3, null);
                    $query->whereRaw("CONCAT(birth_year, '-', LPAD(birth_month,2,'0'), '-', LPAD(birth_day,2,'0')) BETWEEN ? AND ?", [
                        sprintf('%04d-%02d-%02d', $fromYear, $fromMonth, $fromDay),
                        sprintf('%04d-%02d-%02d', $toYear, $toMonth, $toDay),
                    ]);
                }

                if ($mode === 'month' && !empty($filter['month'])) {
                    $query->where('birth_month', (int) $filter['month']);
                }

                if ($mode === 'year' && !empty($filter['year'])) {
                    $query->where('birth_year', (int) $filter['year']);
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

                if ($field === 'supported_children_count') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($sqlOperator, $numericValue) {
                        $guardianQuery->where('children_count', $sqlOperator, $numericValue);
                    });
                    continue;
                }

                if ($field === 'average_income') {
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($sqlOperator, $numericValue) {
                        $guardianQuery->where('average_income', $sqlOperator, $numericValue);
                    });
                    continue;
                }
            }

        }

        $this->applyColumnFilters($query);
        $this->applySorting($query);

        return $query->select('people.*');
    }

    public function getPeopleProperty()
    {
        return $this->buildQuery()
            ->with([
                'disabilityType:id,name',
                'harmTypes:id,title',
                'guardian:id,social_worker_id,guardian_code,first_name,last_name',
                'guardian.socialWorker:id,first_name,last_name',
            ])
            ->paginate(20);
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
        return view('livewire.people.advanced-filter-builder');
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
            'person_code',
            'full_name',
            'responsible_social_worker',
            'guardian_beneficiary_with_code',
            'first_name',
            'last_name',
            'national_id',
            'phone_number',
            'gender',
            'sadaat_status',
            'birth_date',
            'birth_year',
            'birth_month',
            'related_description',
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
                case 'person_code':
                case 'full_name':
                case 'first_name':
                case 'last_name':
                case 'national_id':
                case 'phone_number':
                    $query->where($column, 'like', '%' . $value . '%');
                    break;

                case 'gender':
                case 'sadaat_status':
                case 'birth_month':
                    $query->where($column, $value);
                    break;

                case 'birth_year':
                    $query->where('birth_year', (int) $value);
                    break;

                case 'related_description':
                    $query->where('disability_description', 'like', '%' . $value . '%');
                    break;

                case 'responsible_social_worker':
                    $query->whereHas('guardian.socialWorker', function (Builder $workerQuery) use ($value) {
                        $workerQuery->autocompleteSearch((string) $value);
                    });
                    break;

                case 'guardian_beneficiary_with_code':
                    $query->whereHas('guardian', function (Builder $guardianQuery) use ($value) {
                        $guardianQuery->where(function (Builder $subQuery) use ($value) {
                            $subQuery->where('first_name', 'like', '%' . $value . '%')
                                ->orWhere('last_name', 'like', '%' . $value . '%')
                                ->orWhere('guardian_code', 'like', '%' . $value . '%');
                        });
                    });
                    break;
            }
        }
    }

    protected function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        switch ($this->sortColumn) {
            case 'person_code':
            case 'first_name':
            case 'last_name':
            case 'national_id':
            case 'phone_number':
            case 'gender':
            case 'sadaat_status':
            case 'birth_year':
            case 'birth_month':
                $query->orderBy($this->sortColumn, $direction);
                break;

            case 'full_name':
                $query->orderBy('last_name', $direction)
                    ->orderBy('first_name', $direction);
                break;

            case 'birth_date':
                $query->orderBy('birth_year', $direction)
                    ->orderBy('birth_month', $direction)
                    ->orderBy('birth_day', $direction);
                break;

            case 'responsible_social_worker':
                $query->orderBy(
                    SocialWorker::query()
                        ->select('last_name')
                        ->join('guardians', 'guardians.social_worker_id', '=', 'social_workers.id')
                        ->whereColumn('guardians.id', 'people.guardian_id')
                        ->limit(1),
                    $direction
                )->orderBy(
                    SocialWorker::query()
                        ->select('first_name')
                        ->join('guardians', 'guardians.social_worker_id', '=', 'social_workers.id')
                        ->whereColumn('guardians.id', 'people.guardian_id')
                        ->limit(1),
                    $direction
                );
                break;

            case 'guardian_beneficiary_with_code':
                $query->orderBy(
                    Guardian::query()
                        ->select('guardian_code')
                        ->whereColumn('guardians.id', 'people.guardian_id')
                        ->limit(1),
                    $direction
                )->orderBy(
                    Guardian::query()
                        ->select('last_name')
                        ->whereColumn('guardians.id', 'people.guardian_id')
                        ->limit(1),
                    $direction
                )->orderBy(
                    Guardian::query()
                        ->select('first_name')
                        ->whereColumn('guardians.id', 'people.guardian_id')
                        ->limit(1),
                    $direction
                );
                break;

            case 'related_description':
                $query->orderBy('disability_description', $direction);
                break;

            case 'created_at':
                $query->orderBy('created_at', $direction);
                break;

            default:
                $query->latest();
                break;
        }

        if ($this->sortColumn === 'guardian_beneficiary_with_code') {
            $query->orderBy('last_name', $direction)
                ->orderBy('first_name', $direction);
        } elseif ($this->sortColumn !== 'full_name' && $this->sortColumn !== 'birth_date') {
            $query->orderBy('created_at', 'desc');
        }
    }
}
