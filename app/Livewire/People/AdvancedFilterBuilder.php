<?php

namespace App\Livewire\People;

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
use App\Models\SupportOrganization;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AdvancedFilterBuilder extends Component
{
    use WithPagination;

    public string $globalSearch = '';
    public array $filters = [];
    public string $saveFilterName = '';
    public ?int $selectedPersonId = null;
    public bool $showPersonModal = false;

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
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'national_id' => 'کد ملی',
        'phone_number' => 'موبایل',
        'gender' => 'جنسیت',
        'sadaat_status' => 'سادات',
        'birth_date' => 'تاریخ تولد',
        'birth_year' => 'سال تولد',
        'birth_month' => 'ماه تولد',
        'created_at' => 'تاریخ ثبت',
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

        $this->filters[] = match ($type) {
            'text' => ['field' => $field, 'type' => $type, 'operator' => 'contains', 'value' => ''],
            'select' => ['field' => $field, 'type' => $type, 'value' => ''],
            'date' => ['field' => $field, 'type' => $type, 'mode' => 'exact', 'exact' => '', 'from' => '', 'to' => '', 'month' => '', 'year' => ''],
            'number' => ['field' => $field, 'type' => $type, 'operator' => 'eq', 'value' => ''],
            default => ['field' => $field, 'type' => $type],
        };

        $this->resetPage();
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->filters = [];
        $this->globalSearch = '';
        $this->resetPage();
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
        $this->globalSearch = $saved->global_search ?? '';
        $this->visibleColumns = $saved->visible_columns ?: $this->visibleColumns;
        $this->resetPage();
    }

    public function exportToExcel(): void {}

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
        $query = Person::query()->latest();

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

                if ($field === 'caseworker') {
                    $query->whereHas('guardian.socialWorker', function (Builder $workerQuery) use ($value) {
                        $workerQuery->where(function (Builder $nameOrCodeQuery) use ($value) {
                            $nameOrCodeQuery->where('first_name', 'like', "%{$value}%")
                                ->orWhere('last_name', 'like', "%{$value}%")
                                ->orWhere('worker_code', 'like', "%{$value}%")
                                ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$value}%"])
                                ->orWhereRaw("CONCAT_WS(' ', last_name, first_name) like ?", ["%{$value}%"]);
                        });
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
                    $query->whereHas('residenceContact', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('residence_status_id', $value);
                    });
                    continue;
                }

                if ($field === 'district') {
                    $query->whereHas('residenceContact', function (Builder $residenceQuery) use ($value) {
                        $residenceQuery->where('district_id', $value);
                    });
                    continue;
                }

                if ($field === 'is_local_to_city') {
                    $query->whereHas('residenceContact', function (Builder $residenceQuery) use ($value) {
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

        return $query;
    }

    public function getPeopleProperty()
    {
        return $this->buildQuery()->paginate(20);
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
}
