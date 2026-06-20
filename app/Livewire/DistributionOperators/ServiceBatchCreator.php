<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ServiceBatchCreator extends Component
{
    public const MODE_PREDEFINED = 'predefined';
    public const MODE_MISC = 'misc';
    protected const MISC_NAME_PREFIX = 'متفرقه - ';
    protected const LEGACY_MISC_NAME_PREFIX = 'Misc - ';

    public string $mode = self::MODE_PREDEFINED;

    public ?int $selectedServiceId = null;

    public ?int $socialWorkerId = null;

    public string $socialWorkerQuery = '';

    public bool $showSocialWorkerSuggestions = false;

    public ?int $editingServiceId = null;

    /**
     * @var array<int, string|int|float|null>
     */
    public array $predefinedAllocations = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $miscCategories = [];

    public string $miscServiceType = 'individual';

    public string $miscDescription = '';

    public string $dateDay = '';

    public string $dateMonth = '';

    public string $dateYear = '';

    public string $date = '';

    public function mount(?int $editingServiceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->editingServiceId = $editingServiceId;

        $today = Jalalian::now();
        $this->dateDay = (string) $today->getDay();
        $this->dateMonth = (string) $today->getMonth();
        $this->dateYear = (string) $today->getYear();
        $this->date = $today->format('Y/m/d');
        $this->miscCategories = [$this->makeMiscCategory()];

        if ($this->editingServiceId) {
            $this->loadEditableMiscService($this->resolveEditableService($this->editingServiceId));
        }
    }

    public function updatedMode(): void
    {
        if ($this->editingServiceId) {
            $this->mode = self::MODE_MISC;

            return;
        }

        if (! in_array($this->mode, [self::MODE_PREDEFINED, self::MODE_MISC], true)) {
            $this->mode = self::MODE_PREDEFINED;
        }

        $this->resetValidation();
    }

    public function updatedSelectedServiceId(): void
    {
        $this->predefinedAllocations = [];
        $this->resetValidation();
    }

    public function updatedSocialWorkerQuery(mixed $value): void
    {
        $this->socialWorkerQuery = trim((string) $value);
        $this->socialWorkerId = null;
        $this->showSocialWorkerSuggestions = true;

        if ($this->socialWorkerQuery === '') {
            $this->socialWorkerId = null;
        }
    }

    public function updatedDateDay(): void
    {
        $this->synchronizeDate();
    }

    public function updatedDateMonth(): void
    {
        $this->synchronizeDate();
    }

    public function updatedDateYear(): void
    {
        $this->synchronizeDate();
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        $worker = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->findOrFail($socialWorkerId);

        $this->socialWorkerId = $worker->id;
        $this->socialWorkerQuery = trim($worker->full_name . ' - کد ' . $worker->worker_code);
        $this->showSocialWorkerSuggestions = false;
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->socialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->showSocialWorkerSuggestions = true;
    }

    public function addCategory(): void
    {
        $this->miscCategories[] = $this->makeMiscCategory();
    }

    public function removeCategory(int $index): void
    {
        if (count($this->miscCategories) === 1) {
            return;
        }

        unset($this->miscCategories[$index]);
        $this->miscCategories = array_values($this->miscCategories);
    }

    public function saveBatch()
    {
        $this->synchronizeDate();

        return ($this->mode === self::MODE_MISC || $this->editingServiceId)
            ? $this->saveMiscService()
            : $this->savePredefinedAllocation();
    }

    public function cancelEditing()
    {
        if (! $this->editingServiceId) {
            return;
        }

        return redirect()->route('distribution-operator.service-list');
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-batch-creator', [
            'services' => $this->predefinedServices(),
            'selectedService' => $this->selectedPredefinedService,
            'selectedServiceCategories' => $this->selectedPredefinedServiceCategories,
            'socialWorkerSuggestions' => $this->socialWorkerSuggestions,
            'isEditing' => $this->editingServiceId !== null,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'nextMiscName' => $this->nextMiscName(),
        ]);
    }

    protected function savePredefinedAllocation()
    {
        $validated = $this->validate($this->predefinedRules(), [], $this->validationAttributes());
        $service = $this->resolvePredefinedService((int) $validated['selectedServiceId']);

        $rows = collect($validated['predefinedAllocations'] ?? [])
            ->map(fn ($quantity, $categoryId): array => [
                'category_id' => (int) $categoryId,
                'quantity' => (float) $quantity,
            ])
            ->filter(fn (array $row): bool => $row['quantity'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'predefinedAllocations' => 'حداقل برای یک دسته‌بندی مقدار تخصیص وارد کنید.',
            ]);
        }

        DB::transaction(function () use ($service, $rows, $validated): void {
            $categories = $service->categories()->lockForUpdate()->get()->keyBy('id');

            foreach ($rows as $row) {
                $category = $categories->get($row['category_id']);

                if (! $category) {
                    throw ValidationException::withMessages([
                        'predefinedAllocations' => 'دسته‌بندی انتخاب‌شده متعلق به خدمت انتخاب‌شده نیست.',
                    ]);
                }

                $remainingAssignable = $this->remainingAssignableForPredefinedCategory(
                    $service,
                    (int) $category->id
                );

                if ($row['quantity'] > $remainingAssignable) {
                    throw ValidationException::withMessages([
                        'predefinedAllocations.' . $category->id => 'مقدار تخصیص نمی‌تواند از موجودی دسته‌بندی بیشتر باشد.',
                    ]);
                }

                $allocation = ServiceWorkerAllocation::query()->firstOrNew([
                    'service_id' => $service->id,
                    'service_category_id' => $category->id,
                    'social_worker_id' => (int) $validated['socialWorkerId'],
                ]);

                $allocation->fill([
                    'allocated_quantity' => (float) $allocation->allocated_quantity + $row['quantity'],
                    'assigned_by_user_id' => auth()->id(),
                ])->save();

            }
        });

        session()->flash('success', 'تخصیص خدمت انتخاب‌شده با موفقیت ثبت شد.');

        return redirect()->route('distribution-operator.service-list');
    }

    protected function saveMiscService()
    {
        $validated = $this->validate($this->miscRules(), [], $this->validationAttributes());
        $miscName = $this->nextMiscName();

        DB::transaction(function () use ($validated, $miscName): void {
            $serviceName = ServiceName::query()->firstOrCreate(
                ['name' => $miscName],
                [
                    'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
                    'created_by' => auth()->id(),
                ]
            );

            $totalQuantity = collect($validated['miscCategories'])
                ->sum(fn (array $category): float => (float) $category['quantity']);

            if ($this->editingServiceId) {
                $service = $this->resolveEditableService($this->editingServiceId);
                $service->categories()->delete();
                $service->workerAllocations()->delete();
            } else {
                $service = new Service([
                    'code' => Service::generateNextCode(),
                    'created_by' => auth()->id(),
                    'quantity_delivered' => 0,
                ]);
            }

            $service->fill([
                'name' => $miscName,
                'service_name_id' => $serviceName->id,
                'service_type' => $validated['miscServiceType'],
                'supports_gate_delivery' => true,
                'supports_home_delivery' => true,
                'description' => $validated['miscDescription'] ?? null,
                'total_quantity' => $totalQuantity,
                'total_service_value' => 0,
                'district_id' => null,
                'distribution_start_date' => $this->jalaliToGregorian($validated['date']),
                'distribution_end_date' => $this->jalaliToGregorian($validated['date']),
                'priority' => null,
                'status' => 'in_distribution',
                'status_notes' => 'خدمت متفرقه ایجادشده توسط اپراتور توزیع.',
            ])->save();

            foreach (array_values($validated['miscCategories']) as $index => $categoryRow) {
                $category = $service->categories()->create([
                    'service_name_id' => $serviceName->id,
                    'name' => trim((string) $categoryRow['name']),
                    'quantity' => (float) $categoryRow['quantity'],
                    'unit' => $categoryRow['unit'],
                    'value' => 0,
                    'sort_id' => $index + 1,
                    'created_by' => auth()->id(),
                ]);

                $service->workerAllocations()->create([
                    'social_worker_id' => (int) $validated['socialWorkerId'],
                    'service_category_id' => $category->id,
                    'allocated_quantity' => (float) $categoryRow['quantity'],
                    'assigned_by_user_id' => auth()->id(),
                ]);
            }

            $service->refreshFinancialTotals();
        });

        session()->flash('success', 'خدمت متفرقه با موفقیت ایجاد و تخصیص داده شد.');

        return redirect()->route('distribution-operator.service-list');
    }

    protected function predefinedRules(): array
    {
        return [
            'selectedServiceId' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->whereIn('status', ['approved', 'in_distribution'])),
            ],
            'socialWorkerId' => ['required', 'integer', 'exists:social_workers,id'],
            'predefinedAllocations' => ['array'],
            'predefinedAllocations.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function miscRules(): array
    {
        return [
            'miscServiceType' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'miscDescription' => ['nullable', 'string', 'max:5000'],
            'dateDay' => ['required', 'integer', 'min:1', 'max:31'],
            'dateMonth' => ['required', 'integer', 'min:1', 'max:12'],
            'dateYear' => ['required', 'integer', 'min:1300', 'max:1600'],
            'date' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isValidJalaliDate((string) $value)) {
                    $fail('تاریخ واردشده معتبر نیست.');
                }
            }],
            'socialWorkerId' => ['required', 'integer', 'exists:social_workers,id'],
            'miscCategories' => ['required', 'array', 'min:1'],
            'miscCategories.*.name' => ['required', 'string', 'max:255'],
            'miscCategories.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'miscCategories.*.unit' => ['required', Rule::in(Service::unitKeys())],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedServiceId' => 'خدمت / پویش',
            'socialWorkerId' => 'مددکار',
            'predefinedAllocations.*' => 'مقدار تخصیص',
            'miscServiceType' => 'نوع خدمت',
            'miscDescription' => 'توضیحات',
            'dateDay' => 'روز',
            'dateMonth' => 'ماه',
            'dateYear' => 'سال',
            'date' => 'تاریخ',
            'miscCategories.*.name' => 'نام دسته‌بندی',
            'miscCategories.*.quantity' => 'مقدار دسته‌بندی',
            'miscCategories.*.unit' => 'واحد',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeMiscCategory(): array
    {
        return [
            'name' => '',
            'quantity' => '',
            'unit' => array_key_first(Service::unitOptions()) ?? 'count',
        ];
    }

    protected function loadEditableMiscService(Service $service): void
    {
        $worker = $service->socialWorkers()->select(['social_workers.id', 'first_name', 'last_name', 'worker_code'])->first();
        $jalaliDate = Jalalian::fromDateTime($service->distribution_start_date);

        $this->mode = self::MODE_MISC;
        $this->miscServiceType = (string) $service->service_type;
        $this->miscDescription = (string) $service->description;
        $this->dateDay = (string) $jalaliDate->getDay();
        $this->dateMonth = (string) $jalaliDate->getMonth();
        $this->dateYear = (string) $jalaliDate->getYear();
        $this->date = $jalaliDate->format('Y/m/d');
        $this->socialWorkerId = $worker?->id;
        $this->socialWorkerQuery = $worker ? trim($worker->full_name . ' - کد ' . $worker->worker_code) : '';
        $this->miscCategories = $service->categories()
            ->ordered()
            ->get()
            ->map(fn (ServiceCategory $category): array => [
                'name' => $category->name,
                'quantity' => $this->formatDecimal($category->quantity),
                'unit' => $category->unit,
            ])
            ->values()
            ->all() ?: [$this->makeMiscCategory()];
    }

    protected function synchronizeDate(): void
    {
        $year = str_pad(trim($this->dateYear), 4, '0', STR_PAD_LEFT);
        $month = str_pad(trim($this->dateMonth), 2, '0', STR_PAD_LEFT);
        $day = str_pad(trim($this->dateDay), 2, '0', STR_PAD_LEFT);

        $this->date = implode('/', [$year, $month, $day]);
    }

    protected function resolvePredefinedService(int $serviceId): Service
    {
        return Service::query()
            ->with(['categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id')])
            ->whereIn('status', ['approved', 'in_distribution'])
            ->where(function (Builder $query): void {
                $query->whereNull('created_by')
                    ->orWhereDoesntHave('creator', fn (Builder $creatorQuery) => $creatorQuery
                        ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR));
            })
            ->findOrFail($serviceId);
    }

    protected function resolveEditableService(int $serviceId): Service
    {
        $service = Service::query()
            ->with(['socialWorkers', 'categories'])
            ->where('created_by', auth()->id())
            ->findOrFail($serviceId);

        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        return $service;
    }

    protected function predefinedServices(): Collection
    {
        return Service::query()
            ->with(['serviceName', 'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id')])
            ->whereIn('status', ['approved', 'in_distribution'])
            ->where(function (Builder $query): void {
                $query->whereNull('created_by')
                    ->orWhereDoesntHave('creator', fn (Builder $creatorQuery) => $creatorQuery
                        ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR));
            })
            ->latest()
            ->get();
    }

    public function getSelectedPredefinedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return $this->predefinedServices()->firstWhere('id', $this->selectedServiceId);
    }

    public function getSelectedPredefinedServiceCategoriesProperty(): Collection
    {
        return $this->selectedPredefinedService?->categories?->values() ?? collect();
    }

    public function predefinedAllocationForCategory(int $categoryId): float
    {
        return max(0, (float) ($this->predefinedAllocations[$categoryId] ?? 0));
    }

    public function predefinedAssignableForCategory(int $categoryId): float
    {
        $service = $this->selectedPredefinedService;

        return $service
            ? $this->remainingAssignableForPredefinedCategory($service, $categoryId)
            : 0.0;
    }

    public function predefinedRemainingForCategory(int $categoryId): float
    {
        $service = $this->selectedPredefinedService;

        if (! $service) {
            return 0.0;
        }

        return max(
            0,
            $this->remainingAssignableForPredefinedCategory($service, $categoryId)
                - $this->predefinedAllocationForCategory($categoryId)
        );
    }

    protected function remainingAssignableForPredefinedCategory(Service $service, int $categoryId): float
    {
        $category = $service->relationLoaded('categories')
            ? $service->categories->firstWhere('id', $categoryId)
            : $service->categories()->whereKey($categoryId)->first();

        if (! $category) {
            return 0.0;
        }

        $allocatedQuantity = (float) ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->where('service_category_id', $categoryId)
            ->sum('allocated_quantity');

        return max(0, (float) $category->quantity - $allocatedQuantity);
    }

    public function getSocialWorkerSuggestionsProperty(): Collection
    {
        $query = trim($this->socialWorkerQuery);

        if (! $this->showSocialWorkerSuggestions || mb_strlen($query) < 2) {
            return collect();
        }

        return SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->where(function (Builder $workerQuery) use ($query): void {
                $workerQuery->where('first_name', 'like', $query . '%')
                    ->orWhere('last_name', 'like', $query . '%')
                    ->orWhere('worker_code', 'like', $query . '%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query . '%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%' . $query . '%']);
            })
            ->orderBy('worker_code')
            ->limit(6)
            ->get();
    }

    protected function nextMiscName(): string
    {
        if ($this->editingServiceId) {
            return (string) $this->resolveEditableService($this->editingServiceId)->name;
        }

        $maxServiceNumber = Service::query()
            ->where('name', 'like', self::MISC_NAME_PREFIX . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxLegacyServiceNumber = Service::query()
            ->where('name', 'like', self::LEGACY_MISC_NAME_PREFIX . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::LEGACY_MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxServiceNameNumber = ServiceName::query()
            ->where('name', 'like', self::MISC_NAME_PREFIX . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxLegacyServiceNameNumber = ServiceName::query()
            ->where('name', 'like', self::LEGACY_MISC_NAME_PREFIX . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::LEGACY_MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        return self::MISC_NAME_PREFIX . (((int) max(
            $maxServiceNumber,
            $maxLegacyServiceNumber,
            $maxServiceNameNumber,
            $maxLegacyServiceNameNumber
        )) + 1);
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return \App\Helpers\Morilog\CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }
}
