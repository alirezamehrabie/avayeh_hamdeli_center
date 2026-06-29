<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\GateEntryAssignment;
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
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceBatchCreator extends Component
{
    public const MODE_PREDEFINED = 'predefined';

    public const MODE_MISC = 'misc';

    protected const MISC_NAME_PREFIX = 'متفرقه - ';

    protected const LEGACY_MISC_NAME_PREFIX = 'Misc - ';

    public string $mode = '';

    public ?int $selectedServiceId = null;

    public string $serviceSearch = '';

    public ?int $socialWorkerId = null;

    public string $socialWorkerQuery = '';

    public string $selectedSocialWorkerCode = '';

    public string $selectedSocialWorkerDisplay = '';

    public bool $showSocialWorkerSuggestions = false;

    public ?int $editingServiceId = null;

    public bool $confirmingBatchSave = false;

    public bool $hasUnsavedChanges = false;

    /**
     * @var array<int, string|int|float|null>
     */
    public array $predefinedAllocations = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $miscCategories = [];

    /**
     * Edit-only: misc service grouped by social worker.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $miscWorkerGroups = [];

    /**
     * Edit-only: index of the worker group whose search dropdown is open.
     */
    public ?int $activeWorkerGroupIndex = null;

    public string $miscServiceType = '';

    public string $miscDescription = '';

    public string $date = '';

    public string $editingServiceName = '';

    protected ?Collection $predefinedServicesCache = null;

    protected ?array $predefinedServiceOptionsCache = null;

    protected ?string $predefinedServiceOptionsCacheKey = null;

    protected ?Service $selectedPredefinedServiceCache = null;

    /**
     * @var array<int, float>|null
     */
    protected ?array $selectedPredefinedAllocatedByCategoryCache = null;

    protected ?int $selectedPredefinedAllocatedByCategoryCacheServiceId = null;

    protected ?array $selectedPredefinedCategoryMetricsCache = null;

    protected ?int $selectedPredefinedCategoryMetricsCacheServiceId = null;

    protected ?Collection $socialWorkerSuggestionsCache = null;

    protected ?string $socialWorkerSuggestionsCacheKey = null;

    protected ?string $nextMiscNameCache = null;

    public function mount(?int $editingServiceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->editingServiceId = $editingServiceId;

        $this->date = Jalalian::now()->format('Y/m/d');
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
            $this->mode = '';

            return;
        }

        $this->resetValidation();
        $this->confirmingBatchSave = false;
    }

    public function updatedSelectedServiceId(): void
    {
        $this->predefinedAllocations = [];
        $this->selectedPredefinedServiceCache = null;
        $this->flushSelectedPredefinedServiceMetrics();
        $this->flushPredefinedServiceOptions();
        $this->confirmingBatchSave = false;
        $this->resetValidation();
    }

    public function updatedServiceSearch(mixed $value): void
    {
        $this->serviceSearch = trim((string) $value);
        $this->predefinedServicesCache = null;
        $this->flushPredefinedServiceOptions();
    }

    public function updatedPredefinedAllocations(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        $categoryId = (int) str($key)->before('.')->toString();

        if ($categoryId <= 0) {
            $categoryId = (int) $key;
        }

        if ($categoryId <= 0) {
            return;
        }

        $this->validatePredefinedAllocationField($categoryId);
    }

    public function selectPredefinedService(int $serviceId): void
    {
        $service = $this->resolvePredefinedService($serviceId);

        $this->selectedServiceId = $service->id;
        $this->predefinedAllocations = [];
        $this->serviceSearch = '';
        $this->selectedPredefinedServiceCache = $service;
        $this->flushSelectedPredefinedServiceMetrics();
        $this->flushPredefinedServiceOptions();
        $this->predefinedServicesCache = null;
        $this->confirmingBatchSave = false;
        $this->resetValidation();
    }

    public function clearPredefinedServiceSelection(): void
    {
        $this->selectedServiceId = null;
        $this->predefinedAllocations = [];
        $this->serviceSearch = '';
        $this->selectedPredefinedServiceCache = null;
        $this->flushSelectedPredefinedServiceMetrics();
        $this->flushPredefinedServiceOptions();
        $this->predefinedServicesCache = null;
        $this->confirmingBatchSave = false;
        $this->resetValidation();
    }

    public function updatedSocialWorkerQuery(mixed $value): void
    {
        $this->socialWorkerQuery = trim((string) $value);
        $this->socialWorkerId = null;
        $this->showSocialWorkerSuggestions = true;
        $this->flushSocialWorkerSuggestions();

        if ($this->socialWorkerQuery === '') {
            $this->socialWorkerId = null;
        }

        $this->confirmingBatchSave = false;
    }

    public function updatedDate(): void
    {
        $this->date = $this->normalizeJalaliDate($this->date);
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
    }

    public function updatedMiscServiceType(): void
    {
        if (! in_array($this->miscServiceType, array_keys(Service::TYPE_OPTIONS), true)) {
            $this->miscServiceType = '';
        }

        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
    }

    #[On('confirm-misc-service-type-change')]
    public function confirmMiscServiceTypeChange(string $serviceType): void
    {
        if (! in_array($serviceType, array_keys(Service::TYPE_OPTIONS), true)) {
            return;
        }

        $this->miscServiceType = $serviceType;
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
    }

    public function updatedMiscDescription(): void
    {
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
    }

    public function updatedMiscCategories(): void
    {
        $this->confirmingBatchSave = false;
    }

    public function updatedMiscWorkerGroups(mixed $value = null, ?string $key = null): void
    {
        $this->confirmingBatchSave = false;

        if (! $this->editingServiceId) {
            return;
        }

        if ($key !== null) {
            $field = 'miscWorkerGroups.'.$key;

            if (str($key)->endsWith('.worker_search')) {
                $this->activeWorkerGroupIndex = (int) str($key)->before('.')->toString();
                $this->showSocialWorkerSuggestions = true;
                $this->flushSocialWorkerSuggestions();

                return;
            }

            $this->markEditingDirty();
            $this->resetValidation($field);
            $this->validateOnly($field, $this->miscEditRules(), [], $this->validationAttributes());
        } else {
            $this->markEditingDirty();
        }

        $this->validateDistinctWorkerGroups();
        $this->validateDistinctWorkerGroupCategories();
        $this->validateEditableServiceUsageConstraints(
            $this->resolveEditableService($this->editingServiceId),
            $this->miscWorkerGroups
        );
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        $worker = SocialWorker::query()
            ->with('district:id,name')
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'district_id'])
            ->findOrFail($socialWorkerId);

        $this->socialWorkerId = $worker->id;
        $this->socialWorkerQuery = trim($worker->full_name.' - کد '.$worker->worker_code);
        $this->selectedSocialWorkerCode = $worker->worker_code ? (string) $worker->worker_code : '-';
        $this->selectedSocialWorkerDisplay = $this->formatSelectedSocialWorkerDisplay($worker);
        $this->showSocialWorkerSuggestions = false;
        $this->flushSocialWorkerSuggestions();
        $this->confirmingBatchSave = false;
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->socialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->selectedSocialWorkerCode = '';
        $this->selectedSocialWorkerDisplay = '';
        $this->showSocialWorkerSuggestions = true;
        $this->flushSocialWorkerSuggestions();
        $this->confirmingBatchSave = false;
    }

    public function useMaxPredefinedAllocation(int $categoryId): void
    {
        $assignableQuantity = $this->predefinedAssignableForCategory($categoryId);

        $this->predefinedAllocations[$categoryId] = $this->formatDecimal(max(0, $assignableQuantity));
        $this->confirmingBatchSave = false;
        $this->resetValidation('predefinedAllocations.'.$categoryId);
    }

    public function clearPredefinedAllocation(int $categoryId): void
    {
        unset($this->predefinedAllocations[$categoryId]);
        $this->confirmingBatchSave = false;
        $this->resetValidation('predefinedAllocations.'.$categoryId);
    }

    public function addCategory(): void
    {
        $this->miscCategories[] = $this->makeMiscCategory();
        $this->confirmingBatchSave = false;

        $this->dispatch('service-category-added', index: array_key_last($this->miscCategories));
    }

    public function removeCategory(int $index): void
    {
        if (count($this->miscCategories) === 1) {
            return;
        }

        unset($this->miscCategories[$index]);
        $this->miscCategories = array_values($this->miscCategories);
        $this->confirmingBatchSave = false;
    }

    #[On('confirm-misc-category-delete')]
    public function confirmMiscCategoryDelete(int $index): void
    {
        $this->removeCategory($index);
    }

    public function addWorkerGroup(): void
    {
        $this->miscWorkerGroups[] = $this->makeWorkerGroup();
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation('miscWorkerGroups');

        $newIndex = array_key_last($this->miscWorkerGroups);
        $this->activeWorkerGroupIndex = $newIndex;

        $this->dispatch('worker-group-added', index: $newIndex);
    }

    public function removeWorkerGroup(int $index): void
    {
        if (count($this->miscWorkerGroups) <= 1) {
            return;
        }

        unset($this->miscWorkerGroups[$index]);
        $this->miscWorkerGroups = array_values($this->miscWorkerGroups);
        $this->activeWorkerGroupIndex = null;
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation('miscWorkerGroups');
    }

    #[On('confirm-misc-worker-group-delete')]
    public function confirmMiscWorkerGroupDelete(int $index): void
    {
        $this->removeWorkerGroup($index);
    }

    public function toggleGroupLock(int $index): void
    {
        if (! isset($this->miscWorkerGroups[$index])) {
            return;
        }

        $this->miscWorkerGroups[$index]['locked'] = ! (bool) ($this->miscWorkerGroups[$index]['locked'] ?? false);
        $this->confirmingBatchSave = false;
    }

    public function openGroupWorkerSearch(int $index): void
    {
        if (! isset($this->miscWorkerGroups[$index])) {
            return;
        }

        $this->activeWorkerGroupIndex = $index;
        $this->showSocialWorkerSuggestions = true;
        $this->flushSocialWorkerSuggestions();
        $this->confirmingBatchSave = false;
    }

    public function selectGroupWorker(int $index, int $socialWorkerId): void
    {
        if (! isset($this->miscWorkerGroups[$index])) {
            return;
        }

        if ($this->assignedWorkerIdsExceptGroup($index)->contains((int) $socialWorkerId)) {
            throw ValidationException::withMessages([
                "miscWorkerGroups.{$index}.social_worker_id" => 'این مددکار قبلاً در همین خدمت انتخاب شده است.',
            ]);
        }

        $worker = SocialWorker::query()
            ->with('district:id,name')
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'district_id'])
            ->findOrFail($socialWorkerId);

        $this->miscWorkerGroups[$index]['social_worker_id'] = (int) $worker->id;
        $this->miscWorkerGroups[$index]['worker_query'] = trim($worker->full_name.' - کد '.$worker->worker_code);
        $this->miscWorkerGroups[$index]['worker_search'] = '';
        $this->miscWorkerGroups[$index]['worker_code'] = $worker->worker_code ? (string) $worker->worker_code : '-';
        $this->miscWorkerGroups[$index]['worker_display'] = $this->formatSelectedSocialWorkerDisplay($worker);

        $this->showSocialWorkerSuggestions = false;
        $this->activeWorkerGroupIndex = null;
        $this->flushSocialWorkerSuggestions();
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation("miscWorkerGroups.{$index}.social_worker_id");
        $this->validateDistinctWorkerGroups();
    }

    public function clearGroupWorker(int $index): void
    {
        if (! isset($this->miscWorkerGroups[$index])) {
            return;
        }

        $this->miscWorkerGroups[$index]['social_worker_id'] = null;
        $this->miscWorkerGroups[$index]['worker_query'] = '';
        $this->miscWorkerGroups[$index]['worker_search'] = '';
        $this->miscWorkerGroups[$index]['worker_code'] = '';
        $this->miscWorkerGroups[$index]['worker_display'] = '';
        $this->showSocialWorkerSuggestions = true;
        $this->activeWorkerGroupIndex = $index;
        $this->flushSocialWorkerSuggestions();
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation("miscWorkerGroups.{$index}.social_worker_id");
    }

    public function addGroupCategory(int $index): void
    {
        if (! isset($this->miscWorkerGroups[$index])) {
            return;
        }

        $this->miscWorkerGroups[$index]['categories'][] = $this->makeMiscCategory();
        $this->miscWorkerGroups[$index]['categories'] = array_values($this->miscWorkerGroups[$index]['categories']);
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation("miscWorkerGroups.{$index}.categories");
    }

    public function removeGroupCategory(int $groupIndex, int $categoryIndex): void
    {
        if (! isset($this->miscWorkerGroups[$groupIndex]['categories'])) {
            return;
        }

        if (count($this->miscWorkerGroups[$groupIndex]['categories']) <= 1) {
            return;
        }

        unset($this->miscWorkerGroups[$groupIndex]['categories'][$categoryIndex]);
        $this->miscWorkerGroups[$groupIndex]['categories'] = array_values($this->miscWorkerGroups[$groupIndex]['categories']);
        $this->confirmingBatchSave = false;
        $this->markEditingDirty();
        $this->resetValidation("miscWorkerGroups.{$groupIndex}.categories");
    }

    #[On('confirm-misc-worker-category-delete')]
    public function confirmMiscWorkerCategoryDelete(int $groupIndex, int $categoryIndex): void
    {
        $this->removeGroupCategory($groupIndex, $categoryIndex);
    }

    public function requestSaveConfirmation(): void
    {
        $this->date = $this->normalizeJalaliDate($this->date);

        if ($this->editingServiceId) {
            $validated = $this->validate($this->miscEditRules(), [], $this->validationAttributes());
            $this->validateDistinctWorkerGroups();
            $this->validateDistinctWorkerGroupCategories();
            $this->validateEditableServiceUsageConstraints(
                $this->resolveEditableService($this->editingServiceId),
                $validated['miscWorkerGroups']
            );
        } elseif ($this->mode === self::MODE_MISC) {
            $this->validate($this->miscRules(), [], $this->validationAttributes());
        } else {
            $this->validate($this->predefinedRules(), [], $this->validationAttributes());
            $this->validatePredefinedAllocationRowsForConfirmation();
        }

        $this->confirmingBatchSave = true;
    }

    public function cancelSaveConfirmation(): void
    {
        $this->confirmingBatchSave = false;
    }

    public function confirmSaveBatch()
    {
        if (! $this->confirmingBatchSave) {
            $this->requestSaveConfirmation();

            return null;
        }

        $this->confirmingBatchSave = false;

        return ($this->mode === self::MODE_MISC || $this->editingServiceId)
            ? $this->saveMiscService()
            : $this->savePredefinedAllocation();
    }

    public function saveBatch()
    {
        $this->confirmingBatchSave = true;

        return $this->confirmSaveBatch();
    }

    public function cancelEditing()
    {
        if (! $this->editingServiceId) {
            return null;
        }

        return redirect()->route('distribution-operator.service-list');
    }

    public function render()
    {
        $isPredefinedMode = $this->mode === self::MODE_PREDEFINED && ! $this->editingServiceId;
        $services = $isPredefinedMode ? $this->predefinedServices() : collect();
        $selectedService = $isPredefinedMode ? $this->selectedPredefinedService : null;
        $selectedServiceCategories = $selectedService ? $this->selectedPredefinedServiceCategories : collect();
        $selectedServiceCategoryMetrics = $selectedService ? $this->selectedPredefinedCategoryMetrics() : [];

        return view('livewire.distribution-operators.service-batch-creator', [
            'services' => $services,
            'serviceOptions' => $isPredefinedMode ? $this->predefinedServiceOptions($services) : [],
            'selectedService' => $selectedService,
            'selectedServiceCategories' => $selectedServiceCategories,
            'selectedServiceCategoryMetrics' => $selectedServiceCategoryMetrics,
            'hasPredefinedOverAllocation' => $isPredefinedMode && $this->hasPredefinedOverAllocation($selectedServiceCategoryMetrics),
            'canRequestSaveConfirmation' => $this->canRequestSaveConfirmation($selectedServiceCategoryMetrics),
            'savePreventionMessages' => $this->savePreventionMessages($selectedServiceCategoryMetrics),
            'reviewSummary' => $this->confirmationSummary(),
            'reviewWarnings' => $this->reviewWarnings($selectedServiceCategoryMetrics),
            'confirmationSummary' => $this->confirmingBatchSave ? $this->confirmationSummary() : [],
            'socialWorkerSuggestions' => $this->showSocialWorkerSuggestions ? $this->socialWorkerSuggestions : collect(),
            'isEditing' => $this->editingServiceId !== null,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'nextMiscName' => $this->editingServiceId ? $this->editingServiceName : $this->nextMiscName(),
            'categoryNameSuggestions' => $this->editingServiceId ? $this->categoryNameSuggestions() : [],
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
            $allocatedByCategory = ServiceWorkerAllocation::query()
                ->where('service_id', $service->id)
                ->whereIn('service_category_id', $rows->pluck('category_id')->all())
                ->lockForUpdate()
                ->get()
                ->groupBy('service_category_id')
                ->map(fn (Collection $allocations): float => (float) $allocations->sum(fn (ServiceWorkerAllocation $allocation) => (float) $allocation->allocated_quantity));

            foreach ($rows as $row) {
                $category = $categories->get($row['category_id']);

                if (! $category) {
                    throw ValidationException::withMessages([
                        'predefinedAllocations' => 'دسته‌بندی انتخاب‌شده متعلق به خدمت انتخاب‌شده نیست.',
                    ]);
                }

                $remainingAssignable = max(
                    0,
                    (float) $category->quantity - (float) ($allocatedByCategory[$category->id] ?? 0)
                );

                if ($row['quantity'] > $remainingAssignable) {
                    throw ValidationException::withMessages([
                        'predefinedAllocations.'.$category->id => 'مقدار تخصیص نمی‌تواند از موجودی دسته‌بندی بیشتر باشد.',
                    ]);
                }

                $allocation = ServiceWorkerAllocation::query()
                    ->where('service_id', $service->id)
                    ->where('service_category_id', $category->id)
                    ->where('social_worker_id', (int) $validated['socialWorkerId'])
                    ->first();

                if (! $allocation) {
                    $allocation = new ServiceWorkerAllocation;
                    $allocation->fill([
                        'service_id' => $service->id,
                        'service_category_id' => $category->id,
                        'social_worker_id' => (int) $validated['socialWorkerId'],
                    ]);
                }

                $allocation->fill([
                    'allocated_quantity' => (float) $allocation->allocated_quantity + $row['quantity'],
                    'assigned_by_user_id' => auth()->id(),
                ])->save();

            }
        });

        return redirect()
            ->route('distribution-operator.service-list')
            ->with('distribution-operator-notification', $this->serviceSavedNotification(
                'تخصیص خدمت ثبت شد',
                'تخصیص خدمت انتخاب‌شده برای مددکار ذخیره شد. می‌توانید از همین صفحه وضعیت خدمت را دنبال کنید.',
                'مشاهده فهرست'
            ));
    }

    protected function validatePredefinedAllocationRowsForConfirmation(): void
    {
        $rows = collect($this->predefinedAllocations)
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

        foreach ($rows as $row) {
            $assignableQuantity = $this->predefinedAssignableForCategory((int) $row['category_id']);

            if ((float) $row['quantity'] > $assignableQuantity) {
                throw ValidationException::withMessages([
                    'predefinedAllocations.'.$row['category_id'] => 'مقدار واردشده از موجودی قابل تخصیص این دسته‌بندی بیشتر است.',
                ]);
            }
        }
    }

    protected function saveMiscService()
    {
        if ($this->editingServiceId) {
            return $this->saveMiscServiceEdit();
        }

        $validated = $this->validate($this->miscRules(), [], $this->validationAttributes());
        $miscName = $this->nextMiscName();

        DB::transaction(function () use ($validated, $miscName): void {
            $serviceName = ServiceName::query()
                ->where('name', $miscName)
                ->first();

            if (! $serviceName) {
                $serviceName = new ServiceName;
                $serviceName->fill([
                    'name' => $miscName,
                    'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
                    'created_by' => auth()->id(),
                ])->save();
            }

            $totalQuantity = collect($validated['miscCategories'])
                ->sum(fn (array $category): float => (float) $category['quantity']);

            $service = new Service;

            $service->fill([
                'code' => Service::generateNextCode(),
                'created_by' => auth()->id(),
                'quantity_delivered' => 0,
                'name' => $miscName,
                'service_name_id' => $serviceName->id,
                'service_type' => $validated['miscServiceType'],
                'supports_gate_delivery' => false,
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
                $category = new ServiceCategory;
                $category->fill([
                    'service_name_id' => $serviceName->id,
                    'name' => trim((string) $categoryRow['name']),
                    'quantity' => (float) $categoryRow['quantity'],
                    'unit' => $categoryRow['unit'],
                    'value' => 0,
                    'sort_id' => $index + 1,
                    'created_by' => auth()->id(),
                ]);
                $service->categories()->save($category);

                $allocation = new ServiceWorkerAllocation;
                $allocation->fill([
                    'social_worker_id' => (int) $validated['socialWorkerId'],
                    'service_category_id' => $category->id,
                    'allocated_quantity' => (float) $categoryRow['quantity'],
                    'assigned_by_user_id' => auth()->id(),
                ]);
                $service->workerAllocations()->save($allocation);
            }

            $service->refreshFinancialTotals();
        });

        return redirect()
            ->route('distribution-operator.service-list')
            ->with('distribution-operator-notification', $this->serviceSavedNotification(
                'خدمت متفرقه ایجاد شد',
                'خدمت متفرقه جدید ایجاد و سهمیه آن برای مددکار انتخاب‌شده ثبت شد.',
                'مشاهده خدمت'
            ));
    }

    protected function saveMiscServiceEdit()
    {
        $validated = $this->validate($this->miscEditRules(), [], $this->validationAttributes());
        $this->validateDistinctWorkerGroups();
        $this->validateDistinctWorkerGroupCategories();

        DB::transaction(function () use ($validated): void {
            $service = $this->resolveEditableService($this->editingServiceId);
            $serviceNameId = $service->service_name_id;

            $this->validateEditableServiceUsageConstraints($service, $validated['miscWorkerGroups']);

            $sortId = 1;
            $totalQuantity = 0.0;
            $retainedCategoryIds = [];
            $existingCategories = $service->categories()
                ->withTrashed()
                ->get()
                ->keyBy('id');

            foreach (array_values($validated['miscWorkerGroups']) as $group) {
                $workerId = (int) $group['social_worker_id'];

                foreach (array_values($group['categories']) as $categoryRow) {
                    $quantity = (float) $categoryRow['quantity'];
                    $categoryId = (int) ($categoryRow['id'] ?? 0);
                    $category = $categoryId > 0 ? $existingCategories->get($categoryId) : null;

                    if (! $category) {
                        $category = new ServiceCategory;
                    }

                    $category->fill([
                        'service_name_id' => $serviceNameId,
                        'name' => trim((string) $categoryRow['name']),
                        'quantity' => $quantity,
                        'unit' => $categoryRow['unit'],
                        'value' => 0,
                        'sort_id' => $sortId++,
                        'created_by' => auth()->id(),
                    ]);

                    $service->categories()->save($category);

                    if (method_exists($category, 'trashed') && $category->trashed()) {
                        $category->restore();
                    }

                    $retainedCategoryIds[] = (int) $category->id;

                    ServiceWorkerAllocation::query()->updateOrCreate([
                        'service_id' => $service->id,
                        'service_category_id' => $category->id,
                    ], [
                        'social_worker_id' => $workerId,
                        'allocated_quantity' => $quantity,
                        'assigned_by_user_id' => auth()->id(),
                    ]);

                    $totalQuantity += $quantity;
                }
            }

            $service->workerAllocations()
                ->when($retainedCategoryIds !== [], fn ($query) => $query->whereNotIn('service_category_id', $retainedCategoryIds))
                ->delete();

            $service->categories()
                ->when($retainedCategoryIds !== [], fn ($query) => $query->whereNotIn('id', $retainedCategoryIds))
                ->delete();

            $service->fill([
                'service_type' => $validated['miscServiceType'],
                'description' => $validated['miscDescription'] ?? null,
                'total_quantity' => $totalQuantity,
                'distribution_start_date' => $this->jalaliToGregorian($validated['date']),
                'distribution_end_date' => $this->jalaliToGregorian($validated['date']),
            ])->save();

            $service->refreshFinancialTotals();
        });

        $this->hasUnsavedChanges = false;

        return redirect()
            ->route('distribution-operator.define-service')
            ->with('distribution-operator-notification', $this->serviceSavedNotification(
                'ویرایش خدمت ذخیره شد',
                'تغییرات خدمت متفرقه ذخیره شد و فرم برای ادامه کار آماده است.',
                'ادامه کار'
            ));
    }

    protected function serviceSavedNotification(string $title, string $message, string $buttonLabel): array
    {
        return [
            'type' => 'success',
            'title' => $title,
            'message' => $message,
            'icon' => 'success',
            'buttons' => [
                [
                    'label' => $buttonLabel,
                    'action' => 'close',
                    'variant' => 'success',
                ],
            ],
        ];
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

    protected function miscEditRules(): array
    {
        return [
            'miscServiceType' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'miscDescription' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isValidJalaliDate((string) $value)) {
                    $fail('تاریخ واردشده معتبر نیست.');
                }
            }],
            'miscWorkerGroups' => ['required', 'array', 'min:1'],
            'miscWorkerGroups.*.social_worker_id' => ['required', 'integer', 'exists:social_workers,id'],
            'miscWorkerGroups.*.categories' => ['required', 'array', 'min:1'],
            'miscWorkerGroups.*.categories.*.id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'miscWorkerGroups.*.categories.*.name' => ['required', 'string', 'max:255'],
            'miscWorkerGroups.*.categories.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'miscWorkerGroups.*.categories.*.unit' => ['required', Rule::in(Service::unitKeys())],
        ];
    }

    protected function markEditingDirty(): void
    {
        if ($this->editingServiceId) {
            $this->hasUnsavedChanges = true;
        }
    }

    /**
     * Ensure no social worker appears in more than one group, otherwise the
     * recreated allocations would collide on the (service, worker, category)
     * unique key and duplicate the same worker pointlessly.
     */
    protected function validateDistinctWorkerGroups(): void
    {
        $seenWorkerIndexes = [];

        foreach ($this->miscWorkerGroups as $groupIndex => $group) {
            $workerId = (int) ($group['social_worker_id'] ?? 0);

            if ($workerId <= 0) {
                continue;
            }

            if (array_key_exists($workerId, $seenWorkerIndexes)) {
                throw ValidationException::withMessages([
                    "miscWorkerGroups.{$groupIndex}.social_worker_id" => 'هر مددکار فقط می‌تواند یک‌بار در این خدمت ثبت شود؛ مددکار تکراری را حذف کنید.',
                ]);
            }

            $seenWorkerIndexes[$workerId] = $groupIndex;
        }
    }

    protected function validateDistinctWorkerGroupCategories(): void
    {
        foreach ($this->miscWorkerGroups as $groupIndex => $group) {
            $seenCategoryIndexes = [];

            foreach (($group['categories'] ?? []) as $categoryIndex => $category) {
                $categoryName = mb_strtolower(trim((string) ($category['name'] ?? '')));

                if ($categoryName === '') {
                    continue;
                }

                if (array_key_exists($categoryName, $seenCategoryIndexes)) {
                    throw ValidationException::withMessages([
                        "miscWorkerGroups.{$groupIndex}.categories.{$categoryIndex}.name" => 'هر دسته‌بندی فقط می‌تواند یک‌بار برای این مددکار ثبت شود.',
                    ]);
                }

                $seenCategoryIndexes[$categoryName] = $categoryIndex;
            }
        }
    }

    protected function validateEditableServiceUsageConstraints(Service $service, array $workerGroups): void
    {
        $existingCategories = $service->categories()
            ->withTrashed()
            ->get()
            ->keyBy('id');

        $submittedByCategoryId = $this->submittedEditableRowsByCategoryId($workerGroups);
        $submittedCategoryIds = array_keys($submittedByCategoryId);
        $invalidCategoryIds = array_diff($submittedCategoryIds, $existingCategories->keys()->map(fn ($id): int => (int) $id)->all());

        if ($invalidCategoryIds !== []) {
            throw ValidationException::withMessages([
                'miscWorkerGroups' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
            ]);
        }

        $usageByCategoryId = $this->editableServiceUsageByCategoryId($service);

        foreach ($usageByCategoryId as $categoryId => $usage) {
            $category = $existingCategories->get($categoryId);

            if (! $category) {
                continue;
            }

            $submitted = $submittedByCategoryId[$categoryId] ?? null;

            if (! $submitted) {
                throw ValidationException::withMessages([
                    'miscWorkerGroups' => 'دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل حذف نیستند.',
                ]);
            }

            if ((int) $submitted['social_worker_id'] !== (int) $usage['social_worker_id']) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.social_worker_id' => 'مددکار دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل تغییر نیست.',
                ]);
            }

            if ((string) $submitted['unit'] !== (string) $category->unit) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.categories.'.$submitted['category_index'].'.unit' => 'واحد دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل تغییر نیست.',
                ]);
            }

            $minimumQuantity = max((float) $usage['delivered_quantity'], (float) $usage['assigned_quantity']);

            if ((float) $submitted['quantity'] + 0.00001 < $minimumQuantity) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.categories.'.$submitted['category_index'].'.quantity' => 'مقدار این دسته‌بندی کمتر از مقدار استفاده‌شده در تحویل یا گیت ورود است.',
                ]);
            }
        }
    }

    protected function submittedEditableRowsByCategoryId(array $workerGroups): array
    {
        $rows = [];
        $seen = [];

        foreach (array_values($workerGroups) as $groupIndex => $group) {
            $workerId = (int) ($group['social_worker_id'] ?? 0);

            foreach (array_values($group['categories'] ?? []) as $categoryIndex => $category) {
                $categoryId = (int) ($category['id'] ?? 0);

                if ($categoryId <= 0) {
                    continue;
                }

                if (isset($seen[$categoryId])) {
                    throw ValidationException::withMessages([
                        'miscWorkerGroups' => 'یک دسته‌بندی موجود نمی‌تواند بیش از یک‌بار در فرم ویرایش ثبت شود.',
                    ]);
                }

                $seen[$categoryId] = true;

                $rows[$categoryId] = [
                    'social_worker_id' => $workerId,
                    'category_index' => $categoryIndex,
                    'field_prefix' => 'miscWorkerGroups.'.$groupIndex,
                    'quantity' => (float) ($category['quantity'] ?? 0),
                    'unit' => (string) ($category['unit'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    protected function editableServiceUsageByCategoryId(Service $service): array
    {
        $allocatedWorkerIds = ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->pluck('social_worker_id', 'service_category_id')
            ->map(fn ($workerId): int => (int) $workerId)
            ->all();

        $deliveredByCategory = $service->deliveries()
            ->select('service_category_id')
            ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
            ->groupBy('service_category_id')
            ->pluck('delivered_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity);

        $assignedByCategory = GateEntryAssignment::query()
            ->where('service_id', $service->id)
            ->select('service_category_id')
            ->selectRaw('COUNT(*) as assigned_quantity')
            ->groupBy('service_category_id')
            ->pluck('assigned_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity);

        return $deliveredByCategory
            ->keys()
            ->merge($assignedByCategory->keys())
            ->map(fn ($categoryId): int => (int) $categoryId)
            ->unique()
            ->mapWithKeys(fn (int $categoryId): array => [
                $categoryId => [
                    'social_worker_id' => (int) ($allocatedWorkerIds[$categoryId] ?? 0),
                    'delivered_quantity' => (float) ($deliveredByCategory[$categoryId] ?? 0),
                    'assigned_quantity' => (float) ($assignedByCategory[$categoryId] ?? 0),
                ],
            ])
            ->all();
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedServiceId' => 'خدمت / پویش',
            'socialWorkerId' => 'مددکار',
            'predefinedAllocations.*' => 'مقدار تخصیص',
            'miscServiceType' => 'نوع خدمت',
            'miscDescription' => 'توضیحات',
            'date' => 'تاریخ',
            'miscCategories.*.name' => 'نام دسته‌بندی',
            'miscCategories.*.quantity' => 'مقدار دسته‌بندی',
            'miscCategories.*.unit' => 'واحد',
            'miscWorkerGroups.*.social_worker_id' => 'مددکار',
            'miscWorkerGroups.*.categories.*.id' => 'دسته‌بندی',
            'miscWorkerGroups.*.categories.*.name' => 'نام دسته‌بندی',
            'miscWorkerGroups.*.categories.*.quantity' => 'مقدار دسته‌بندی',
            'miscWorkerGroups.*.categories.*.unit' => 'واحد',
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
        $jalaliDate = Jalalian::fromDateTime($service->distribution_start_date);

        $this->mode = self::MODE_MISC;
        $this->editingServiceName = (string) $service->name;
        $this->miscServiceType = (string) $service->service_type;
        $this->miscDescription = (string) $service->description;
        $this->date = $jalaliDate->format('Y/m/d');

        $this->miscWorkerGroups = $this->buildWorkerGroupsFromService($service);
    }

    /**
     * Build the edit-mode worker groups from an existing misc service.
     *
     * Each group is one social worker plus the category rows allocated to that
     * worker (resolved through the service worker allocations).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildWorkerGroupsFromService(Service $service): array
    {
        $categories = $service->categories()->ordered()->get();

        $allocationsByCategory = $service->workerAllocations()
            ->with('socialWorker.district:id,name')
            ->get()
            ->keyBy('service_category_id');

        $groups = [];
        $groupIndexByWorker = [];

        foreach ($categories as $category) {
            $allocation = $allocationsByCategory->get($category->id);
            $worker = $allocation?->socialWorker;

            if (! $worker) {
                continue;
            }

            $workerId = (int) $worker->id;

            if (! array_key_exists($workerId, $groupIndexByWorker)) {
                $groupIndexByWorker[$workerId] = count($groups);
                $groups[] = [
                    'uid' => 'group-'.$workerId,
                    'social_worker_id' => $workerId,
                    'worker_query' => trim($worker->full_name.' - کد '.$worker->worker_code),
                    'worker_search' => '',
                    'worker_code' => $worker->worker_code ? (string) $worker->worker_code : '-',
                    'worker_display' => $this->formatSelectedSocialWorkerDisplay($worker),
                    'locked' => true,
                    'is_existing' => true,
                    'categories' => [],
                ];
            }

            $groups[$groupIndexByWorker[$workerId]]['categories'][] = [
                'id' => (int) $category->id,
                'name' => $category->name,
                'quantity' => $this->formatDecimal($category->quantity),
                'unit' => $category->unit,
            ];
        }

        if ($groups === []) {
            return [$this->makeWorkerGroup()];
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Distinct category names already used across the current worker groups,
     * offered as autocomplete suggestions to discourage near-duplicate labels.
     *
     * @return array<int, string>
     */
    protected function categoryNameSuggestions(): array
    {
        $seen = [];
        $names = [];

        foreach ($this->miscWorkerGroups as $group) {
            foreach (($group['categories'] ?? []) as $category) {
                $name = trim((string) ($category['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $key = mb_strtolower($name);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $names[] = $name;
            }
        }

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeWorkerGroup(): array
    {
        return [
            'uid' => 'group-new-'.\Illuminate\Support\Str::random(8),
            'social_worker_id' => null,
            'worker_query' => '',
            'worker_search' => '',
            'worker_code' => '',
            'worker_display' => '',
            'locked' => false,
            'is_existing' => false,
            'categories' => [$this->makeMiscCategory()],
        ];
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
        if ($this->predefinedServicesCache instanceof Collection) {
            return $this->predefinedServicesCache;
        }

        $search = trim($this->serviceSearch);

        $services = Service::query()
            ->with(['serviceName', 'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id')])
            ->whereIn('status', ['approved', 'in_distribution'])
            ->where(function (Builder $query): void {
                $query->whereNull('created_by')
                    ->orWhereDoesntHave('creator', fn (Builder $creatorQuery) => $creatorQuery
                        ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR));
            })
            ->whereRaw('(select coalesce(sum(quantity), 0) from service_categories where service_categories.service_id = services.id and service_categories.deleted_at is null) > (select coalesce(sum(allocated_quantity), 0) from service_social_worker where service_social_worker.service_id = services.id)')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhereHas('serviceName', fn (Builder $serviceNameQuery) => $serviceNameQuery
                            ->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->limit(25)
            ->get();

        if ($this->selectedServiceId && ! $services->contains('id', (int) $this->selectedServiceId)) {
            $selectedService = $this->selectedPredefinedService;

            if ($selectedService) {
                $services->prepend($selectedService);
            }
        }

        return $this->predefinedServicesCache = $services->unique('id')->values();
    }

    public function getSelectedPredefinedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        if ($this->selectedPredefinedServiceCache instanceof Service
            && (int) $this->selectedPredefinedServiceCache->id === (int) $this->selectedServiceId) {
            return $this->selectedPredefinedServiceCache;
        }

        return $this->selectedPredefinedServiceCache = $this->resolvePredefinedService((int) $this->selectedServiceId);
    }

    public function getSelectedPredefinedServiceCategoriesProperty(): Collection
    {
        return $this->selectedPredefinedService?->categories?->values() ?? collect();
    }

    public function predefinedAllocationForCategory(int $categoryId): float
    {
        return max(0, (float) ($this->predefinedAllocations[$categoryId] ?? 0));
    }

    public function hasPredefinedOverAllocation(?array $metrics = null): bool
    {
        if ($this->mode !== self::MODE_PREDEFINED || $this->editingServiceId || ! $this->selectedServiceId) {
            return false;
        }

        foreach (($metrics ?? $this->selectedPredefinedCategoryMetrics()) as $categoryId => $categoryMetrics) {
            if ($this->predefinedAllocationForCategory((int) $categoryId) > (float) $categoryMetrics['assignable']) {
                return true;
            }
        }

        return false;
    }

    public function predefinedAssignableForCategory(int $categoryId): float
    {
        return $this->selectedPredefinedCategoryMetrics()[$categoryId]['assignable'] ?? 0.0;
    }

    public function predefinedRemainingForCategory(int $categoryId): float
    {
        return max(
            0,
            $this->predefinedAssignableForCategory($categoryId)
                - $this->predefinedAllocationForCategory($categoryId)
        );
    }

    public function hasPositivePredefinedAllocation(): bool
    {
        return collect($this->predefinedAllocations)
            ->contains(fn ($quantity): bool => (float) $quantity > 0);
    }

    protected function hasValidMiscCategoryRows(): bool
    {
        return collect($this->miscCategories)
            ->contains(fn (array $category): bool => trim((string) ($category['name'] ?? '')) !== ''
                && (float) ($category['quantity'] ?? 0) > 0
                && in_array((string) ($category['unit'] ?? ''), Service::unitKeys(), true));
    }

    protected function hasSelectedMiscServiceType(): bool
    {
        return in_array($this->miscServiceType, array_keys(Service::TYPE_OPTIONS), true);
    }

    protected function isValidMiscCategoryRow(mixed $category): bool
    {
        return is_array($category)
            && trim((string) ($category['name'] ?? '')) !== ''
            && (float) ($category['quantity'] ?? 0) > 0
            && in_array((string) ($category['unit'] ?? ''), Service::unitKeys(), true);
    }

    protected function hasValidWorkerGroups(): bool
    {
        if (empty($this->miscWorkerGroups)) {
            return false;
        }

        foreach ($this->miscWorkerGroups as $group) {
            if ((int) ($group['social_worker_id'] ?? 0) <= 0) {
                return false;
            }

            $categories = $group['categories'] ?? [];

            if (empty($categories)) {
                return false;
            }

            foreach ($categories as $category) {
                if (! $this->isValidMiscCategoryRow($category)) {
                    return false;
                }
            }
        }

        return ! $this->hasDuplicateWorkerGroups();
    }

    protected function hasDuplicateWorkerGroups(): bool
    {
        $workerIds = collect($this->miscWorkerGroups)
            ->pluck('social_worker_id')
            ->filter(fn ($id): bool => (int) $id > 0)
            ->map(fn ($id): int => (int) $id);

        return $workerIds->count() !== $workerIds->unique()->count();
    }

    protected function canRequestSaveConfirmation(?array $predefinedMetrics = null): bool
    {
        if ($this->editingServiceId) {
            return $this->hasSelectedMiscServiceType()
                && $this->hasValidWorkerGroups()
                && $this->isValidJalaliDate($this->date);
        }

        if ($this->mode === self::MODE_PREDEFINED) {
            return $this->selectedServiceId !== null
                && $this->socialWorkerId !== null
                && $this->hasPositivePredefinedAllocation()
                && ! $this->hasPredefinedOverAllocation($predefinedMetrics);
        }

        return $this->hasSelectedMiscServiceType()
            && $this->socialWorkerId !== null
            && $this->hasValidMiscCategoryRows()
            && $this->isValidJalaliDate($this->date);
    }

    protected function savePreventionMessages(?array $predefinedMetrics = null): array
    {
        $messages = [];

        if ($this->editingServiceId) {
            if (! $this->hasSelectedMiscServiceType()) {
                $messages[] = 'نوع خدمت را انتخاب کنید.';
            }

            if (empty($this->miscWorkerGroups)) {
                $messages[] = 'حداقل یک مددکار به خدمت اضافه کنید.';
            } else {
                $hasWorkerlessGroup = collect($this->miscWorkerGroups)
                    ->contains(fn ($group): bool => (int) ($group['social_worker_id'] ?? 0) <= 0);

                if ($hasWorkerlessGroup) {
                    $messages[] = 'برای هر گروه، مددکار را انتخاب کنید.';
                }

                $hasInvalidCategoryRows = collect($this->miscWorkerGroups)
                    ->contains(function ($group): bool {
                        $categories = $group['categories'] ?? [];

                        if (empty($categories)) {
                            return true;
                        }

                        foreach ($categories as $category) {
                            if (! $this->isValidMiscCategoryRow($category)) {
                                return true;
                            }
                        }

                        return false;
                    });

                if ($hasInvalidCategoryRows) {
                    $messages[] = 'هر مددکار باید حداقل یک دسته‌بندی با نام، مقدار و واحد معتبر داشته باشد.';
                }

                if ($this->hasDuplicateWorkerGroups()) {
                    $messages[] = 'یک مددکار بیش از یک‌بار انتخاب شده است؛ مددکار تکراری را حذف کنید.';
                }
            }

            if (! $this->isValidJalaliDate($this->date)) {
                $messages[] = 'تاریخ ثبت را به‌صورت معتبر وارد کنید.';
            }

            return $messages;
        }

        if ($this->mode === self::MODE_PREDEFINED && ! $this->editingServiceId) {
            if (! $this->selectedServiceId) {
                $messages[] = 'ابتدا خدمت تاییدشده را انتخاب کنید.';
            }

            if (! $this->socialWorkerId) {
                $messages[] = 'مددکار دریافت‌کننده را انتخاب کنید.';
            }

            if (! $this->hasPositivePredefinedAllocation()) {
                $messages[] = 'برای حداقل یک دسته‌بندی مقدار تخصیص وارد کنید.';
            }

            if ($this->hasPredefinedOverAllocation($predefinedMetrics)) {
                $messages[] = 'مقدارهای بیشتر از موجودی را اصلاح کنید یا دکمه حداکثر را بزنید.';
            }

            return $messages;
        }

        if (! $this->hasSelectedMiscServiceType()) {
            $messages[] = 'ابتدا نوع خدمت را انتخاب کنید.';
        }

        if (! $this->socialWorkerId) {
            $messages[] = 'مددکار دریافت‌کننده را انتخاب کنید.';
        }

        if (! $this->hasValidMiscCategoryRows()) {
            $messages[] = 'حداقل یک دسته‌بندی با نام و واحد معتبر وارد کنید.';
        }

        if (! $this->isValidJalaliDate($this->date)) {
            $messages[] = 'تاریخ ثبت را به‌صورت معتبر وارد کنید.';
        }

        return $messages;
    }

    protected function reviewWarnings(?array $predefinedMetrics = null): array
    {
        $warnings = $this->savePreventionMessages($predefinedMetrics);

        if (($this->mode === self::MODE_MISC || $this->editingServiceId) && trim($this->miscDescription) === '') {
            $warnings[] = 'توضیحات ثبت نشده است؛ اگر این خدمت نیاز به توضیح دارد، قبل از تأیید آن را کامل کنید.';
        }

        return array_values(array_unique($warnings));
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

    protected function validatePredefinedAllocationField(int $categoryId): void
    {
        $field = 'predefinedAllocations.'.$categoryId;
        $quantity = $this->predefinedAllocationForCategory($categoryId);
        $assignableQuantity = $this->predefinedAssignableForCategory($categoryId);

        if ($quantity > $assignableQuantity) {
            $this->addError($field, 'مقدار واردشده از موجودی قابل تخصیص این دسته‌بندی بیشتر است.');

            return;
        }

        $this->resetValidation($field);
    }

    protected function confirmationSummary(): array
    {
        if ($this->editingServiceId) {
            return $this->miscEditConfirmationSummary();
        }

        return $this->mode === self::MODE_MISC
            ? $this->miscConfirmationSummary()
            : $this->predefinedConfirmationSummary();
    }

    protected function miscEditConfirmationSummary(): array
    {
        $groups = collect($this->miscWorkerGroups)
            ->map(function (array $group): ?array {
                $workerId = (int) ($group['social_worker_id'] ?? 0);

                $rows = collect($group['categories'] ?? [])
                    ->map(function (array $category): array {
                        $quantity = (float) ($category['quantity'] ?? 0);
                        $unit = (string) ($category['unit'] ?? '');

                        return [
                            'name' => trim((string) ($category['name'] ?? '')),
                            'unit' => $unit,
                            'unit_label' => Service::unitOptions()[$unit] ?? $unit,
                            'quantity' => $quantity,
                            'quantity_label' => number_format($quantity, 2),
                            'remaining_label' => '0.00',
                            'is_large_quantity' => $this->isLargeMiscQuantity($quantity),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['name'] !== '' && $row['quantity'] > 0)
                    ->values()
                    ->all();

                if ($rows === []) {
                    return null;
                }

                return [
                    'worker_name' => trim((string) ($group['worker_display'] ?? '')) ?: (trim((string) ($group['worker_query'] ?? '')) ?: 'مددکار'),
                    'worker_code' => (string) ($group['worker_code'] ?? ''),
                    'rows' => $rows,
                    'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $allRows = collect($groups)->flatMap(fn (array $group): array => $group['rows'])->values()->all();
        $largeQuantityRowsCount = collect($allRows)
            ->filter(fn (array $row): bool => (bool) ($row['is_large_quantity'] ?? false))
            ->count();

        return [
            'mode' => self::MODE_MISC,
            'is_edit' => true,
            'title' => 'تأیید ویرایش خدمت متفرقه',
            'service_name' => $this->editingServiceName,
            'service_code' => '',
            'service_type' => Service::TYPE_OPTIONS[$this->miscServiceType] ?? $this->miscServiceType,
            'worker_name' => count($groups) === 1 ? ($groups[0]['worker_name'] ?? '-') : (count($groups).' مددکار'),
            'worker_code' => count($groups) === 1 ? ($groups[0]['worker_code'] ?? '') : '',
            'date_label' => $this->date,
            'description' => trim($this->miscDescription),
            'total_quantity_label' => number_format(collect($allRows)->sum('quantity'), 2),
            'rows' => $allRows,
            'groups' => $groups,
            'large_quantity_rows_count' => $largeQuantityRowsCount,
            'has_empty_description_warning' => trim($this->miscDescription) === '',
        ];
    }

    protected function predefinedConfirmationSummary(): array
    {
        $service = $this->selectedPredefinedService;
        $worker = $this->selectedSocialWorker();
        $metrics = $this->selectedPredefinedCategoryMetrics();
        $rows = $this->selectedPredefinedServiceCategories
            ->map(function (ServiceCategory $category) use ($metrics): ?array {
                $quantity = $this->predefinedAllocationForCategory((int) $category->id);

                if ($quantity <= 0) {
                    return null;
                }

                $assignable = (float) ($metrics[$category->id]['assignable'] ?? 0);

                return [
                    'name' => $category->name,
                    'unit' => $category->unit,
                    'unit_label' => Service::unitOptions()[$category->unit] ?? $category->unit,
                    'quantity' => $quantity,
                    'quantity_label' => number_format($quantity, 2),
                    'remaining_label' => number_format(max(0, $assignable - $quantity), 2),
                    'consumes_all_remaining' => $assignable > 0 && max(0, $assignable - $quantity) <= 0.00001,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $depletingRowsCount = collect($rows)
            ->filter(fn (array $row): bool => (bool) ($row['consumes_all_remaining'] ?? false))
            ->count();

        return [
            'mode' => self::MODE_PREDEFINED,
            'title' => 'تأیید تخصیص خدمت موجود',
            'service_name' => $service?->name ?: ($service?->serviceName?->name ?? 'خدمت انتخاب‌شده'),
            'service_code' => (string) ($service?->code ?? ''),
            'worker_name' => $worker?->full_name ?? $this->socialWorkerQuery,
            'worker_code' => $worker?->worker_code ? (string) $worker->worker_code : '',
            'date_label' => 'ثبت تخصیص پس از تأیید نهایی انجام می‌شود',
            'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
            'rows' => $rows,
            'depleting_rows_count' => $depletingRowsCount,
        ];
    }

    protected function miscConfirmationSummary(): array
    {
        $worker = $this->selectedSocialWorker();
        $rows = collect($this->miscCategories)
            ->map(function (array $category): array {
                $quantity = (float) ($category['quantity'] ?? 0);
                $unit = (string) ($category['unit'] ?? '');

                return [
                    'name' => trim((string) ($category['name'] ?? '')),
                    'unit' => $unit,
                    'unit_label' => Service::unitOptions()[$unit] ?? $unit,
                    'quantity' => $quantity,
                    'quantity_label' => number_format($quantity, 2),
                    'remaining_label' => '0.00',
                    'is_large_quantity' => $this->isLargeMiscQuantity($quantity),
                ];
            })
            ->filter(fn (array $row): bool => $row['name'] !== '' && $row['quantity'] > 0)
            ->values()
            ->all();

        $largeQuantityRowsCount = collect($rows)
            ->filter(fn (array $row): bool => (bool) ($row['is_large_quantity'] ?? false))
            ->count();

        return [
            'mode' => self::MODE_MISC,
            'title' => $this->editingServiceId ? 'تأیید ویرایش خدمت متفرقه' : 'تأیید ایجاد خدمت متفرقه',
            'service_name' => $this->nextMiscName(),
            'service_code' => '',
            'service_type' => Service::TYPE_OPTIONS[$this->miscServiceType] ?? $this->miscServiceType,
            'worker_name' => $worker?->full_name ?? $this->socialWorkerQuery,
            'worker_code' => $worker?->worker_code ? (string) $worker->worker_code : '',
            'date_label' => $this->date,
            'description' => trim($this->miscDescription),
            'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
            'rows' => $rows,
            'large_quantity_rows_count' => $largeQuantityRowsCount,
            'has_empty_description_warning' => trim($this->miscDescription) === '',
        ];
    }

    protected function selectedSocialWorker(): ?SocialWorker
    {
        if (! $this->socialWorkerId) {
            return null;
        }

        return SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->find($this->socialWorkerId);
    }

    protected function isLargeMiscQuantity(float $quantity): bool
    {
        return $quantity >= 1000;
    }

    /**
     * @return array<int, array{quantity: float, allocated: float, assignable: float}>
     */
    protected function selectedPredefinedCategoryMetrics(): array
    {
        $service = $this->selectedPredefinedService;

        if (! $service) {
            return [];
        }

        if (is_array($this->selectedPredefinedCategoryMetricsCache)
            && $this->selectedPredefinedCategoryMetricsCacheServiceId === (int) $service->id) {
            return $this->selectedPredefinedCategoryMetricsCache;
        }

        $allocatedByCategory = $this->allocatedQuantitiesByCategoryForSelectedPredefinedService($service);

        $this->selectedPredefinedCategoryMetricsCacheServiceId = (int) $service->id;

        return $this->selectedPredefinedCategoryMetricsCache = $service->categories
            ->mapWithKeys(function (ServiceCategory $category) use ($allocatedByCategory): array {
                $quantity = (float) $category->quantity;
                $allocated = (float) ($allocatedByCategory[$category->id] ?? 0);

                return [
                    (int) $category->id => [
                        'quantity' => $quantity,
                        'allocated' => $allocated,
                        'assignable' => max(0, $quantity - $allocated),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, float>
     */
    protected function allocatedQuantitiesByCategoryForSelectedPredefinedService(Service $service): array
    {
        if (is_array($this->selectedPredefinedAllocatedByCategoryCache)
            && $this->selectedPredefinedAllocatedByCategoryCacheServiceId === (int) $service->id) {
            return $this->selectedPredefinedAllocatedByCategoryCache;
        }

        $this->selectedPredefinedAllocatedByCategoryCacheServiceId = (int) $service->id;

        return $this->selectedPredefinedAllocatedByCategoryCache = ServiceWorkerAllocation::query()
            ->select('service_category_id')
            ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
            ->where('service_id', $service->id)
            ->groupBy('service_category_id')
            ->pluck('allocated_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->all();
    }

    protected function flushSelectedPredefinedServiceMetrics(): void
    {
        $this->selectedPredefinedAllocatedByCategoryCache = null;
        $this->selectedPredefinedAllocatedByCategoryCacheServiceId = null;
        $this->selectedPredefinedCategoryMetricsCache = null;
        $this->selectedPredefinedCategoryMetricsCacheServiceId = null;
    }

    protected function predefinedServiceOptions(Collection $services): array
    {
        if ($services->isEmpty()) {
            return [];
        }

        $cacheKey = $services->pluck('id')->implode(',').'|'.(int) $this->selectedServiceId;

        if ($this->predefinedServiceOptionsCacheKey === $cacheKey && is_array($this->predefinedServiceOptionsCache)) {
            return $this->predefinedServiceOptionsCache;
        }

        $categoryIds = $services
            ->flatMap(fn (Service $service) => $service->categories->pluck('id'))
            ->map(fn ($categoryId) => (int) $categoryId)
            ->filter()
            ->values();

        $allocatedByCategory = $categoryIds->isEmpty()
            ? collect()
            : ServiceWorkerAllocation::query()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
                ->whereIn('service_category_id', $categoryIds->all())
                ->groupBy('service_category_id')
                ->pluck('allocated_quantity', 'service_category_id');

        $this->predefinedServiceOptionsCacheKey = $cacheKey;

        return $this->predefinedServiceOptionsCache = $services
            ->map(function (Service $service) use ($allocatedByCategory): array {
                $categories = $service->categories->values();
                $remainingAssignable = $categories->sum(function (ServiceCategory $category) use ($allocatedByCategory): float {
                    $allocated = (float) ($allocatedByCategory[$category->id] ?? 0);

                    return max(0, (float) $category->quantity - $allocated);
                });

                return [
                    'id' => (int) $service->id,
                    'code' => (string) $service->code,
                    'name' => $service->name ?: ($service->serviceName?->name ?? 'بدون عنوان'),
                    'status' => Service::STATUS_OPTIONS[$service->status] ?? $service->status,
                    'remaining' => $remainingAssignable,
                    'remainingLabel' => number_format($remainingAssignable, 2),
                    'categoriesCount' => $categories->count(),
                    'categorySummary' => $categories->pluck('name')->filter()->take(4)->implode('، '),
                ];
            })
            ->filter(fn (array $service): bool => $service['remaining'] > 0 || (int) $service['id'] === (int) $this->selectedServiceId)
            ->values()
            ->all();
    }

    protected function flushPredefinedServiceOptions(): void
    {
        $this->predefinedServiceOptionsCache = null;
        $this->predefinedServiceOptionsCacheKey = null;
    }

    public function getSocialWorkerSuggestionsProperty(): Collection
    {
        $activeGroupIndex = $this->editingServiceId ? $this->activeWorkerGroupIndex : null;
        $query = trim($this->editingServiceId && $activeGroupIndex !== null
            ? (string) ($this->miscWorkerGroups[$activeGroupIndex]['worker_search'] ?? '')
            : $this->socialWorkerQuery);

        if (! $this->showSocialWorkerSuggestions || mb_strlen($query) < 2) {
            return collect();
        }

        $excludedWorkerIds = $this->assignedWorkerIdsExceptGroup($activeGroupIndex);
        $selectedWorkerId = $this->editingServiceId && $activeGroupIndex !== null
            ? (int) ($this->miscWorkerGroups[$activeGroupIndex]['social_worker_id'] ?? 0)
            : (int) $this->socialWorkerId;
        $cacheKey = mb_strtolower($query).'|'.$selectedWorkerId.'|'.($activeGroupIndex ?? 'main').'|'.$excludedWorkerIds->implode(',');

        if ($this->socialWorkerSuggestionsCacheKey === $cacheKey && $this->socialWorkerSuggestionsCache instanceof Collection) {
            return $this->socialWorkerSuggestionsCache;
        }

        $workers = SocialWorker::query()
            ->with('district:id,name')
            ->withCount([
                'workerAllocations as open_allocations_count' => fn (Builder $allocationQuery) => $allocationQuery
                    ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                        ->whereIn('status', ['approved', 'in_distribution'])),
            ])
            ->select([
                'id',
                'first_name',
                'last_name',
                'worker_code',
                'mobile',
                'district_id',
                'covered_people_count',
                'covered_households_count',
                'covered_children_count',
            ])
            ->where(function (Builder $workerQuery) use ($query): void {
                $workerQuery->where('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%')
                    ->orWhere('worker_code', 'like', $query.'%')
                    ->orWhere('mobile', 'like', $query.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query.'%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$query.'%']);
            })
            ->orderBy('worker_code')
            ->limit(10)
            ->get();

        $workerIds = $workers->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $allocatedQuantities = $workerIds === []
            ? collect()
            : ServiceWorkerAllocation::query()
                ->select('social_worker_id')
                ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
                ->whereIn('social_worker_id', $workerIds)
                ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                    ->whereIn('status', ['approved', 'in_distribution']))
                ->groupBy('social_worker_id')
                ->pluck('allocated_quantity', 'social_worker_id');

        if ($selectedWorkerId > 0 && ! $workers->contains('id', $selectedWorkerId)) {
            $selectedWorker = SocialWorker::query()
                ->with('district:id,name')
                ->withCount([
                    'workerAllocations as open_allocations_count' => fn (Builder $allocationQuery) => $allocationQuery
                        ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                            ->whereIn('status', ['approved', 'in_distribution'])),
                ])
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'worker_code',
                    'mobile',
                    'district_id',
                    'covered_people_count',
                    'covered_households_count',
                    'covered_children_count',
                ])
                ->find($selectedWorkerId);

            if ($selectedWorker) {
                $workers->prepend($selectedWorker);

                if (! $allocatedQuantities->has($selectedWorker->id)) {
                    $allocatedQuantities->put($selectedWorker->id, ServiceWorkerAllocation::query()
                        ->where('social_worker_id', $selectedWorker->id)
                        ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                            ->whereIn('status', ['approved', 'in_distribution']))
                        ->sum('allocated_quantity'));
                }
            }
        }

        $this->socialWorkerSuggestionsCacheKey = $cacheKey;

        return $this->socialWorkerSuggestionsCache = $workers
            ->unique('id')
            ->map(fn (SocialWorker $worker): array => $this->socialWorkerSuggestionPayload(
                $worker,
                (float) ($allocatedQuantities[$worker->id] ?? 0),
                $excludedWorkerIds->contains((int) $worker->id)
            ))
            ->values();
    }

    protected function flushSocialWorkerSuggestions(): void
    {
        $this->socialWorkerSuggestionsCache = null;
        $this->socialWorkerSuggestionsCacheKey = null;
    }

    protected function assignedWorkerIdsExceptGroup(?int $groupIndex): Collection
    {
        return collect($this->miscWorkerGroups)
            ->reject(fn (array $group, int $index): bool => $groupIndex !== null && $index === $groupIndex)
            ->pluck('social_worker_id')
            ->filter(fn ($id): bool => (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    protected function socialWorkerSuggestionPayload(SocialWorker $worker, float $allocatedQuantity, bool $isDuplicate = false): array
    {
        return [
            'id' => (int) $worker->id,
            'duplicate' => $isDuplicate,
            'name' => trim($worker->full_name) ?: 'مددکار بدون نام',
            'code' => $worker->worker_code ? (string) $worker->worker_code : '-',
            'mobile' => $worker->mobile ?: '-',
            'district' => $worker->district?->name ?: 'بدون منطقه',
            'covered_people' => (int) ($worker->covered_people_count ?? 0),
            'covered_households' => (int) ($worker->covered_households_count ?? 0),
            'covered_children' => (int) ($worker->covered_children_count ?? 0),
            'open_allocations' => (int) ($worker->open_allocations_count ?? 0),
            'allocated_quantity_label' => number_format($allocatedQuantity, 2),
        ];
    }

    protected function formatSelectedSocialWorkerDisplay(SocialWorker $worker): string
    {
        $name = trim($worker->full_name) ?: 'مددکار بدون نام';
        $district = trim((string) ($worker->district?->name ?? ''));

        return $district !== '' ? $name.' - '.$district : $name;
    }

    protected function nextMiscName(): string
    {
        if ($this->nextMiscNameCache !== null) {
            return $this->nextMiscNameCache;
        }

        if ($this->editingServiceId) {
            return $this->nextMiscNameCache = (string) $this->resolveEditableService($this->editingServiceId)->name;
        }

        $maxServiceNumber = Service::query()
            ->where('name', 'like', self::MISC_NAME_PREFIX.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxLegacyServiceNumber = Service::query()
            ->where('name', 'like', self::LEGACY_MISC_NAME_PREFIX.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::LEGACY_MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxServiceNameNumber = ServiceName::query()
            ->where('name', 'like', self::MISC_NAME_PREFIX.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        $maxLegacyServiceNameNumber = ServiceName::query()
            ->where('name', 'like', self::LEGACY_MISC_NAME_PREFIX.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(name, ?) AS UNSIGNED)) as sequence', [mb_strlen(self::LEGACY_MISC_NAME_PREFIX) + 1])
            ->value('sequence');

        return $this->nextMiscNameCache = self::MISC_NAME_PREFIX.(((int) max(
            $maxServiceNumber,
            $maxLegacyServiceNumber,
            $maxServiceNameNumber,
            $maxLegacyServiceNameNumber
        )) + 1);
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', $this->normalizeJalaliDate($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return \App\Helpers\Morilog\CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
    }

    protected function normalizeJalaliDate(?string $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        return (string) str($normalized)->before(' ')->trim();
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
