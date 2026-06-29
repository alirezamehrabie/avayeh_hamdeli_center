<?php

namespace App\Livewire\SocialWorkers;

use App\Exports\SocialWorkersExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\AcademicLevel;
use App\Models\District;
use App\Models\Guardian;
use App\Models\Occupation;
use App\Models\Person;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\SocialWorkerSavedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class AdvancedSocialWorkerReport extends Component
{
    use WithPagination;

    public string $globalSearch = '';
    public array $filters = [];
    public string $saveFilterName = '';
    public ?int $selectedWorkerId = null;
    public bool $showWorkerModal = false;
    public array $columnFilters = [];
    public ?string $sortColumn = null;
    public string $sortDirection = 'desc';
    public array $columnFilterDefinitions = [];

    public array $visibleColumns = [
        'worker_code',
        'full_name',
        'mobile',
        'households_count',
        'beneficiaries_count',
        'deliveries_count',
    ];

    public array $availableColumns = [
        'worker_code'         => 'کد مددکار',
        'full_name'           => 'نام و نام خانوادگی',
        'national_id'         => 'کد ملی',
        'mobile'              => 'موبایل',
        'district'            => 'منطقه / ناحیه',
        'occupation'          => 'شغل',
        'academic_level'      => 'مقطع تحصیلی',
        'birth_date'          => 'تاریخ تولد',
        'age'                 => 'سن',
        'start_date'          => 'تاریخ شروع همکاری',
        'households_count'    => 'تعداد خانوارها',
        'beneficiaries_count' => 'تعداد مددجویان',
        'services_count'      => 'خدمات تخصیص‌یافته',
        'deliveries_count'    => 'تحویل‌های انجام‌شده',
        'delivered_value'     => 'ارزش تحویل‌ها (ریال)',
        'is_active'           => 'وضعیت',
        'created_at'          => 'تاریخ ثبت',
    ];

    public array $filterableFields = [
        'first_name'          => ['label' => 'نام', 'type' => 'text'],
        'last_name'           => ['label' => 'نام خانوادگی', 'type' => 'text'],
        'national_id'         => ['label' => 'کد ملی', 'type' => 'text'],
        'mobile'              => ['label' => 'موبایل', 'type' => 'text'],
        'worker_code'         => ['label' => 'کد مددکار', 'type' => 'text'],
        'status'              => ['label' => 'وضعیت فعالیت', 'type' => 'select', 'options' => [
            'active'   => 'فعال',
            'inactive' => 'غیرفعال',
            'all'      => 'همه (فعال و غیرفعال)',
        ]],
        'district'            => ['label' => 'منطقه / ناحیه', 'type' => 'select', 'options' => []],
        'occupation'          => ['label' => 'شغل', 'type' => 'select', 'options' => []],
        'academic_level'      => ['label' => 'مقطع تحصیلی', 'type' => 'select', 'options' => []],
        'birth_date'          => ['label' => 'تاریخ تولد', 'type' => 'date'],
        'start_date'          => ['label' => 'تاریخ شروع همکاری', 'type' => 'date'],
        'households_count'    => ['label' => 'تعداد خانوارهای تحت پوشش', 'type' => 'number'],
        'beneficiaries_count' => ['label' => 'تعداد مددجویان تحت پوشش', 'type' => 'number'],
        'services_count'      => ['label' => 'تعداد خدمات تخصیص‌یافته', 'type' => 'number'],
        'deliveries_count'    => ['label' => 'تعداد تحویل‌های انجام‌شده', 'type' => 'number'],
        'family_members_count' => ['label' => 'تعداد اعضای خانواده مددکار', 'type' => 'number'],
    ];

    public function mount(): void
    {
        $this->filterableFields['district']['options'] = District::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['occupation']['options'] = Occupation::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->filterableFields['academic_level']['options'] = AcademicLevel::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $this->columnFilterDefinitions = [
            'worker_code'         => ['type' => 'text', 'placeholder' => 'کد مددکار'],
            'full_name'           => ['type' => 'text', 'placeholder' => 'نام و نام خانوادگی'],
            'national_id'         => ['type' => 'text', 'placeholder' => 'کد ملی'],
            'mobile'              => ['type' => 'text', 'placeholder' => 'موبایل'],
            'district'            => ['type' => 'select', 'options' => $this->filterableFields['district']['options']],
            'occupation'          => ['type' => 'select', 'options' => $this->filterableFields['occupation']['options']],
            'academic_level'      => ['type' => 'select', 'options' => $this->filterableFields['academic_level']['options']],
            'households_count'    => ['type' => 'number', 'placeholder' => 'تعداد خانوارها'],
            'beneficiaries_count' => ['type' => 'number', 'placeholder' => 'تعداد مددجویان'],
            'services_count'      => ['type' => 'number', 'placeholder' => 'تعداد خدمات'],
            'deliveries_count'    => ['type' => 'number', 'placeholder' => 'تعداد تحویل‌ها'],
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
        $this->columnFilters = [];
        $this->globalSearch = '';
        $this->sortColumn = null;
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function updatedColumnFilters(): void
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

        SocialWorkerSavedFilter::create([
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
        $saved = SocialWorkerSavedFilter::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedFilterId);

        $this->filters = $saved->filters ?? [];
        $this->globalSearch = $saved->global_search ?? '';
        $this->visibleColumns = $saved->visible_columns ?: $this->visibleColumns;
        $this->columnFilters = [];
        $this->sortColumn = null;
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function deleteSavedFilter(int $savedFilterId): void
    {
        SocialWorkerSavedFilter::query()
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

        $filename = 'Export-(مددکاران)-' . Jalalian::now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new SocialWorkersExport(
                query: $query,
                visibleColumns: $this->visibleColumns,
                availableColumns: $this->availableColumns,
            ),
            $filename
        );
    }

    public function showWorkerInfo(int $workerId): void
    {
        $this->selectedWorkerId = $workerId;
        $this->showWorkerModal = true;
    }

    public function closeWorkerModal(): void
    {
        $this->showWorkerModal = false;
        $this->selectedWorkerId = null;
    }

    public function getSelectedWorkerProperty(): ?SocialWorker
    {
        if (!$this->selectedWorkerId) {
            return null;
        }

        return SocialWorker::query()
            ->withoutGlobalScope('active')
            ->withTrashed()
            ->withCount(['guardians', 'people', 'services', 'serviceDeliveries'])
            ->withSum('serviceDeliveries as service_deliveries_sum_delivered_total_value', 'delivered_total_value')
            ->with([
                'district:id,name',
                'occupation:id,name',
                'academicLevel:id,title',
                'guardians' => fn ($q) => $q
                    ->select('id', 'social_worker_id', 'first_name', 'last_name', 'guardian_code')
                    ->withCount('people')
                    ->orderByDesc('id'),
            ])
            ->find($this->selectedWorkerId);
    }

    /**
     * شروط فیلتر را روی یک کوئری تازه از مددکاران اعمال می‌کند.
     * تمام فیلترها سمت سرور و با اندیس‌های دیتابیس اجرا می‌شوند.
     */
    protected function buildQuery(): Builder
    {
        $query = SocialWorker::query();

        $this->applyStatusScope($query);

        if (trim($this->globalSearch) !== '') {
            $term = trim($this->globalSearch);
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%")
                    ->orWhere('worker_code', 'like', "%{$term}%");
            });
        }

        foreach ($this->filters as $filter) {
            $field = $filter['field'] ?? null;
            $type = $filter['type'] ?? null;

            if (!$field || !isset($this->filterableFields[$field])) {
                continue;
            }

            if ($field === 'status') {
                // وضعیت فعالیت در applyStatusScope مدیریت می‌شود
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
                if ($value === '') {
                    continue;
                }

                match ($field) {
                    'district' => $query->where('district_id', $value),
                    'occupation' => $query->where('occupation_id', $value),
                    'academic_level' => $query->where('academic_level_id', $value),
                    default => $query->where($field, $value),
                };
            }

            if ($type === 'date') {
                $this->applyDateFilter($query, $filter, $field);
            }

            if ($type === 'number') {
                $this->applyNumberFilter($query, $filter, $field);
            }
        }

        $this->applyColumnFilters($query);
        $this->applySorting($query);

        return $query->select('social_workers.*');
    }

    protected function applyStatusScope(Builder $query): void
    {
        $status = 'active';
        foreach ($this->filters as $filter) {
            if (($filter['field'] ?? null) === 'status' && filled($filter['value'] ?? null)) {
                $status = $filter['value'];
            }
        }

        if ($status === 'inactive') {
            $query->withoutGlobalScope('active')->withTrashed()->where('is_active', false);
        } elseif ($status === 'all') {
            $query->withoutGlobalScope('active')->withTrashed();
        }
        // 'active' => اسکوپ سراسری پیش‌فرض فقط مددکاران فعال را برمی‌گرداند
    }

    protected function applyDateFilter(Builder $query, array $filter, string $field): void
    {
        $prefix = $field === 'start_date' ? 'start' : 'birth';
        $mode = $filter['mode'] ?? 'exact';

        if ($mode === 'exact' && !empty($filter['exact'])) {
            [$year, $month, $day] = array_pad(explode('/', $filter['exact']), 3, null);
            $query->where("{$prefix}_year", $year)
                ->where("{$prefix}_month", $month)
                ->where("{$prefix}_day", $day);
        }

        if ($mode === 'range' && !empty($filter['from']) && !empty($filter['to'])) {
            [$fromYear, $fromMonth, $fromDay] = array_pad(explode('/', $filter['from']), 3, null);
            [$toYear, $toMonth, $toDay] = array_pad(explode('/', $filter['to']), 3, null);
            $query->whereRaw("CONCAT({$prefix}_year, '-', LPAD({$prefix}_month,2,'0'), '-', LPAD({$prefix}_day,2,'0')) BETWEEN ? AND ?", [
                sprintf('%04d-%02d-%02d', $fromYear, $fromMonth, $fromDay),
                sprintf('%04d-%02d-%02d', $toYear, $toMonth, $toDay),
            ]);
        }

        if ($mode === 'month' && !empty($filter['month'])) {
            $query->where("{$prefix}_month", (int) $filter['month']);
        }

        if ($mode === 'year' && !empty($filter['year'])) {
            $query->where("{$prefix}_year", (int) $filter['year']);
        }
    }

    protected function applyNumberFilter(Builder $query, array $filter, string $field): void
    {
        $operator = $filter['operator'] ?? 'eq';
        $value = $filter['value'] ?? null;
        if ($value === '' || $value === null || !is_numeric($value)) {
            return;
        }

        $map = ['eq' => '=', 'gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<='];
        $sqlOperator = $map[$operator] ?? '=';
        $numericValue = (int) $value;

        match ($field) {
            'households_count' => $query->has('guardians', $sqlOperator, $numericValue),
            'beneficiaries_count' => $query->has('people', $sqlOperator, $numericValue),
            'services_count' => $query->has('services', $sqlOperator, $numericValue),
            'deliveries_count' => $query->has('serviceDeliveries', $sqlOperator, $numericValue),
            default => $query->where($field, $sqlOperator, $numericValue),
        };
    }

    public function getWorkersProperty()
    {
        return $this->buildQuery()
            ->withCount(['guardians', 'people', 'services', 'serviceDeliveries'])
            ->withSum('serviceDeliveries as service_deliveries_sum_delivered_total_value', 'delivered_total_value')
            ->with([
                'district:id,name',
                'occupation:id,name',
                'academicLevel:id,title',
            ])
            ->paginate(20);
    }

    /**
     * خلاصه آماری روی کل مجموعهٔ فیلترشده (نه فقط صفحهٔ جاری).
     * چون تعداد مددکاران معمولاً محدود است، آمار با چند کوئری تجمیعی ارزان محاسبه می‌شود.
     */
    #[Computed]
    public function summary(): array
    {
        $workerIds = $this->buildQuery()->reorder()->pluck('social_workers.id');

        if ($workerIds->isEmpty()) {
            return [
                'workers' => 0,
                'households' => 0,
                'beneficiaries' => 0,
                'services' => 0,
                'deliveries' => 0,
                'delivered_value' => 0,
                'avg_households' => 0.0,
                'avg_beneficiaries' => 0.0,
            ];
        }

        $ids = $workerIds->all();
        $workersCount = $workerIds->count();

        $households = Guardian::whereIn('social_worker_id', $ids)->count();
        $beneficiaries = Person::whereHas('guardian', fn (Builder $q) => $q->whereIn('social_worker_id', $ids))->count();
        $services = ServiceWorkerAllocation::whereIn('social_worker_id', $ids)->distinct('service_id')->count('service_id');
        $deliveries = ServiceDelivery::whereIn('social_worker_id', $ids)->count();
        $deliveredValue = (int) ServiceDelivery::whereIn('social_worker_id', $ids)->sum('delivered_total_value');

        return [
            'workers' => $workersCount,
            'households' => $households,
            'beneficiaries' => $beneficiaries,
            'services' => $services,
            'deliveries' => $deliveries,
            'delivered_value' => $deliveredValue,
            'avg_households' => round($households / max($workersCount, 1), 1),
            'avg_beneficiaries' => round($beneficiaries / max($workersCount, 1), 1),
        ];
    }

    public function getSavedFiltersProperty()
    {
        return SocialWorkerSavedFilter::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.social-workers.advanced-social-worker-report');
    }

    public function isSortableColumn(string $column): bool
    {
        return in_array($column, [
            'worker_code',
            'full_name',
            'national_id',
            'mobile',
            'households_count',
            'beneficiaries_count',
            'services_count',
            'deliveries_count',
            'delivered_value',
            'birth_date',
            'age',
            'start_date',
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
                case 'worker_code':
                case 'full_name':
                case 'national_id':
                case 'mobile':
                    $query->where($column, 'like', '%' . $value . '%');
                    break;

                case 'district':
                    $query->where('district_id', $value);
                    break;

                case 'occupation':
                    $query->where('occupation_id', $value);
                    break;

                case 'academic_level':
                    $query->where('academic_level_id', $value);
                    break;

                case 'households_count':
                    $query->has('guardians', '=', (int) $value);
                    break;

                case 'beneficiaries_count':
                    $query->has('people', '=', (int) $value);
                    break;

                case 'services_count':
                    $query->has('services', '=', (int) $value);
                    break;

                case 'deliveries_count':
                    $query->has('serviceDeliveries', '=', (int) $value);
                    break;
            }
        }
    }

    protected function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        switch ($this->sortColumn) {
            case 'worker_code':
            case 'national_id':
            case 'mobile':
                $query->orderBy($this->sortColumn, $direction);
                break;

            case 'full_name':
                $query->orderBy('last_name', $direction)
                    ->orderBy('first_name', $direction);
                break;

            case 'birth_date':
            case 'age':
                $query->orderBy('birth_year', $direction)
                    ->orderBy('birth_month', $direction)
                    ->orderBy('birth_day', $direction);
                break;

            case 'start_date':
                $query->orderBy('start_year', $direction)
                    ->orderBy('start_month', $direction)
                    ->orderBy('start_day', $direction);
                break;

            case 'households_count':
                $query->orderBy(
                    Guardian::query()->selectRaw('count(*)')
                        ->whereColumn('guardians.social_worker_id', 'social_workers.id'),
                    $direction
                );
                break;

            case 'beneficiaries_count':
                $query->orderBy(
                    Person::query()->selectRaw('count(*)')
                        ->join('guardians', 'guardians.id', '=', 'people.guardian_id')
                        ->whereColumn('guardians.social_worker_id', 'social_workers.id'),
                    $direction
                );
                break;

            case 'services_count':
                $query->orderBy(
                    DB::table('service_social_worker')->selectRaw('count(*)')
                        ->whereColumn('service_social_worker.social_worker_id', 'social_workers.id'),
                    $direction
                );
                break;

            case 'deliveries_count':
                $query->orderBy(
                    DB::table('service_deliveries')->selectRaw('count(*)')
                        ->whereColumn('service_deliveries.social_worker_id', 'social_workers.id'),
                    $direction
                );
                break;

            case 'delivered_value':
                $query->orderBy(
                    DB::table('service_deliveries')->selectRaw('coalesce(sum(delivered_total_value), 0)')
                        ->whereColumn('service_deliveries.social_worker_id', 'social_workers.id'),
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
            $query->orderBy('social_workers.id', 'desc');
        }
    }
}
