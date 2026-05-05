<?php

namespace App\Livewire\People;

use App\Models\BeneficiarySavedFilter;
use App\Models\Person;
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
            'male' => 'مرد',
            'female' => 'زن',
        ]],
    ];

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
                if ($operator === 'exact') {
                    $query->where($field, $value);
                } else {
                    $query->where($field, 'like', "%{$value}%");
                }
            }

            if ($type === 'select') {
                $value = $filter['value'] ?? '';
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
