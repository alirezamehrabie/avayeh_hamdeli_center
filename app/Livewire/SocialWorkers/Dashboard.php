<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Services\QrIdentityService;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.social-worker')]
class Dashboard extends Component
{
    use InteractsWithNotificationModal;

    public string $activeSection = 'service-delivery';

    public ?int $selectedServiceId = null;

    public array $quotaState = [
        'service_type' => '',
    ];

    public string $serviceSelectionWarning = '';

    public array $recipientEntries = [];

    public string $deliveredAt = '';

    public string $notes = '';

    public ?int $activeRecipientSearchIndex = null;

    protected ?Collection $assignedServicesCache = null;

    protected ?Service $selectedServiceCache = null;

    protected ?Collection $assignableCategoriesCache = null;

    protected ?array $selectedServiceTotalsCache = null;

    protected ?array $selectedServiceCategoryMetricsCache = null;

    protected ?int $assignableCategoriesCacheServiceId = null;

    protected ?int $selectedServiceTotalsCacheServiceId = null;

    protected ?int $selectedServiceCategoryMetricsCacheServiceId = null;

    protected ?array $unitOptionsCache = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);
        $this->recipientEntries = [$this->blankEntry()];
        $this->deliveredAt = $this->defaultDeliveredAt();
    }

    public function updatedSelectedServiceId(): void
    {
        $this->flushServiceMetricCaches();
        $service = $this->selectedService;
        $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId($service))];
        $this->activeRecipientSearchIndex = null;
        $this->syncQuotaState($service);
        $this->serviceSelectionWarning = '';
        $this->closeNotificationModal();
        $this->resetValidation();

        // Guide the user (on mobile) to the recipient step once a service is chosen.
        if ($service) {
            $this->dispatch('service-selected');
        }
    }

    public function requireServiceSelection(): void
    {
        if ($this->selectedService) {
            $this->serviceSelectionWarning = '';

            return;
        }

        $this->serviceSelectionWarning = 'لطفاً یک خدمت انتخاب کنید';
    }

    public function persianNumber(mixed $value): string
    {
        return strtr((string) $value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
            ',' => '٬',
        ]);
    }

    public function serviceDropdownLabel(Service $service): string
    {
        return trim($this->persianNumber((string) $service->code).' - '.(string) ($service->serviceName?->name ?? ''));
    }

    public function addRecipientField(): void
    {
        $this->recipientEntries[] = $this->blankEntry($this->defaultServiceCategoryId($this->selectedService));
    }

    public function removeRecipientField(int $index): void
    {
        unset($this->recipientEntries[$index]);
        $this->recipientEntries = array_values($this->recipientEntries);

        if ($this->recipientEntries === []) {
            $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId($this->selectedService))];
        }
    }

    public function updatedRecipientEntries($value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        if (str_ends_with($key, '.full_name') || str_ends_with($key, '.mobile')) {
            [$index] = explode('.', $key);
            $index = (int) $index;

            $this->recipientEntries[$index]['is_unregistered'] = (bool) ($this->recipientEntries[$index]['is_unregistered'] ?? false);

            return;
        }

        if (! str_ends_with($key, '.national_id')) {
            return;
        }

        [$index] = explode('.', $key);
        $index = (int) $index;
        $query = trim((string) ($this->recipientEntries[$index]['national_id'] ?? ''));

        $this->clearResolvedEntry($index, preserveNationalId: true);

        if ($query === '') {
            $this->activeRecipientSearchIndex = null;

            return;
        }

        $this->activeRecipientSearchIndex = mb_strlen($query) >= 2 ? $index : null;

        $service = $this->selectedService;

        if (! $service) {
            return;
        }

        if (! preg_match('/^\d{10}$/', $query)) {
            return;
        }

        if ($service->service_type === 'family') {
            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->where('national_code', $query)
                ->first();

            if ($guardian) {
                $this->fillGuardianEntry($index, $guardian, $guardian->people_count.' نفر تحت تکفل');
                $this->activeRecipientSearchIndex = null;
                $this->recipientEntries[$index]['not_found_notice'] = '';
                $this->recipientEntries[$index]['is_unregistered'] = false;

                return;
            }

            $this->markUnregisteredRecipient($index, $query);

            return;
        }

        $person = Person::query()
            ->with('guardian:id,children_count,children_in_house')
            ->where('national_id', $query)
            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
            ->first();

        if ($person) {
            $this->fillPersonEntry($index, $person, 'مددجو');
            $this->activeRecipientSearchIndex = null;
            $this->recipientEntries[$index]['not_found_notice'] = '';
            $this->recipientEntries[$index]['is_unregistered'] = false;

            return;
        }

        $this->markUnregisteredRecipient($index, $query);
    }

    public function resolveRecipientQr(int $index): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $rawToken = trim((string) ($this->recipientEntries[$index]['qr_token'] ?? ''));

        if ($rawToken === '') {
            $this->addError('recipientEntries.'.$index.'.qr_token', 'وارد کردن توکن یا نشانی QR الزامی است.');

            return;
        }

        $token = $this->extractQrToken($rawToken);
        $identity = $this->resolveServiceDeliveryQrIdentity($token);

        if (! $identity) {
            $this->clearResolvedEntry($index, preserveNationalId: false);
            $this->addError('recipientEntries.'.$index.'.qr_token', 'QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.');

            return;
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            if ($service->service_type !== 'family') {
                $this->addError('recipientEntries.'.$index.'.qr_token', 'QR سرپرست فقط برای خدمات خانوادگی قابل استفاده است.');

                return;
            }

            $guardian = Guardian::query()
                ->select([
                    'id',
                    'social_worker_id',
                    'national_code',
                    'first_name',
                    'last_name',
                    'guardian_phone_number',
                    'children_count',
                    'children_in_house',
                ])
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->find($identity->subject_id);

            if (! $guardian) {
                $this->addError('recipientEntries.'.$index.'.qr_token', 'این سرپرست به حساب شما تخصیص داده نشده است.');

                return;
            }

            $this->fillGuardianEntry($index, $guardian, $guardian->people_count.' نفر تحت تکفل');

            return;
        }

        $person = Person::query()
            ->select(['id', 'guardian_id', 'national_id', 'first_name', 'last_name'])
            ->with('guardian:id,social_worker_id,children_count,children_in_house,national_code,first_name,last_name,guardian_phone_number')
            ->whereHas('guardian', fn (Builder $query) => $query
                ->where('social_worker_id', $this->currentSocialWorkerId()))
            ->find($identity->subject_id);

        if (! $person) {
            $this->addError('recipientEntries.'.$index.'.qr_token', 'این مددجو به حساب شما تخصیص داده نشده است.');

            return;
        }

        if ($service->service_type === 'family') {
            $guardian = $person->guardian;

            if (! $guardian) {
                $this->addError('recipientEntries.'.$index.'.qr_token', 'این مددجو برای ثبت خدمت خانوادگی سرپرست مرتبط ندارد.');

                return;
            }

            $guardian->loadCount('people');
            $this->fillGuardianEntry($index, $guardian, $guardian->people_count.' نفر تحت تکفل');

            return;
        }

        $this->fillPersonEntry($index, $person, 'مددجو');
    }

    public function resolveScannedRecipientQr(int $index, string $payload): array
    {
        $field = 'recipientEntries.'.$index.'.qr_token';

        if (! array_key_exists($index, $this->recipientEntries)) {
            return $this->recipientQrScanResponse(false, 'ردیف گیرنده پیدا نشد.');
        }

        $this->resetErrorBag($field);
        $this->recipientEntries[$index]['qr_token'] = trim($payload);
        $this->resolveRecipientQr($index);

        if ($this->getErrorBag()->has($field)) {
            return $this->recipientQrScanResponse(false, (string) $this->getErrorBag()->first($field));
        }

        $name = (string) ($this->recipientEntries[$index]['resolved_name'] ?? '');

        return $this->recipientQrScanResponse(
            $name !== '',
            $name !== '' ? 'گیرنده خدمت شناسایی شد.' : 'اطلاعات گیرنده از QR پیدا نشد.',
            $name
        );
    }

    public function setActiveRecipientSearch(int $index): void
    {
        $query = trim((string) ($this->recipientEntries[$index]['national_id'] ?? ''));
        $this->activeRecipientSearchIndex = mb_strlen($query) >= 2 ? $index : null;
    }

    public function selectRecipientSuggestion(int $index, string $type, int $id): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        if ($service->service_type === 'family') {
            abort_unless($type === 'guardian', 404);

            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->findOrFail($id);

            $this->fillGuardianEntry($index, $guardian, $guardian->people_count.' نفر تحت تکفل');
            $this->activeRecipientSearchIndex = null;

            return;
        }

        abort_unless($type === 'person', 404);

        $person = Person::query()
            ->with('guardian:id,children_count,children_in_house')
            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
            ->findOrFail($id);

        $this->fillPersonEntry($index, $person, 'مددجو');
        $this->activeRecipientSearchIndex = null;
    }

    public function saveDelivery(): void
    {
        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $exception) {
            $this->showValidationErrorModal(
                $exception->validator->errors()->all(),
                (string) $exception->validator->errors()->keys()[0]
            );

            throw $exception;
        }

        $service = $this->selectedService;
        abort_unless($service, 404);

        try {
            $deliveryEntries = $this->normalizedDeliveryEntries($validated['recipientEntries']);
            $this->validateNoDuplicateRecipientCategoryRows($deliveryEntries);
        } catch (ValidationException $exception) {
            $this->showValidationErrorModal(
                $exception->validator->errors()->all(),
                (string) $exception->validator->errors()->keys()[0]
            );

            throw $exception;
        }

        try {
            DB::transaction(function () use ($service, $validated, $deliveryEntries): void {
                $deliveryBatchIds = collect($deliveryEntries)
                    ->pluck('_recipient_index')
                    ->unique()
                    ->mapWithKeys(fn ($index) => [(int) $index => (string) Str::uuid()]);
                $categoryQuantities = collect($deliveryEntries)
                    ->groupBy(fn (array $entry) => (int) $entry['service_category_id'])
                    ->map(fn ($entries) => $entries->sum(fn (array $entry) => (float) $entry['quantity']));
                $categoryIds = $categoryQuantities->keys()
                    ->map(fn ($categoryId) => (int) $categoryId)
                    ->values()
                    ->all();

                $lockedCategories = ServiceCategory::query()
                    ->where('service_id', $service->id)
                    ->whereIn('id', $categoryIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $lockedAllocations = ServiceWorkerAllocation::query()
                    ->where('service_id', $service->id)
                    ->where('social_worker_id', $this->currentSocialWorkerId())
                    ->whereIn('service_category_id', $categoryIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('service_category_id');

                $deliveredByCategory = ServiceDelivery::query()
                    ->select('service_category_id')
                    ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                    ->where('service_id', $service->id)
                    ->whereIn('service_category_id', $categoryIds)
                    ->groupBy('service_category_id')
                    ->pluck('delivered_quantity', 'service_category_id');

                $workerDeliveredByCategory = ServiceDelivery::query()
                    ->select('service_category_id')
                    ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                    ->where('service_id', $service->id)
                    ->where('social_worker_id', $this->currentSocialWorkerId())
                    ->whereIn('service_category_id', $categoryIds)
                    ->groupBy('service_category_id')
                    ->pluck('delivered_quantity', 'service_category_id');

                foreach ($categoryQuantities as $categoryId => $quantity) {
                    $categoryId = (int) $categoryId;
                    $category = $lockedCategories->get($categoryId);
                    $allocation = $lockedAllocations->get($categoryId);
                    $allocatedQuantity = (float) ($allocation?->allocated_quantity ?? 0);
                    $workerDeliveredQuantity = (float) ($workerDeliveredByCategory[$categoryId] ?? 0);
                    $remainingWorkerAllocation = max(0, $allocatedQuantity - $workerDeliveredQuantity);

                    if (! $allocation || (float) $quantity > $remainingWorkerAllocation) {
                        throw ValidationException::withMessages([
                            'recipientEntries' => 'جمع مقادیر ثبت‌شده از سهمیه تخصیص‌یافته شما برای این آیتم بیشتر است.',
                        ]);
                    }

                    $deliveredQuantity = (float) ($deliveredByCategory[$categoryId] ?? 0);
                    $remainingStock = $category ? max(0, (float) $category->quantity - $deliveredQuantity) : 0;

                    if (! $category || $remainingStock < (float) $quantity) {
                        throw ValidationException::withMessages([
                            'recipientEntries' => 'مقدار تحویل برای یکی از دسته‌بندی‌ها از موجودی همان دسته‌بندی بیشتر است.',
                        ]);
                    }
                }

                foreach ($deliveryEntries as $entry) {
                    $personId = null;
                    $guardianId = null;
                    $fullName = trim((string) ($entry['full_name'] ?? ''));
                    $mobile = trim((string) ($entry['mobile'] ?? '')) ?: null;
                    $serviceCategoryId = (int) $entry['service_category_id'];
                    $category = $lockedCategories->get($serviceCategoryId);
                    $deliveryUnitValue = (int) ($category?->value ?? 0);

                    if ($service->service_type === 'family') {
                        $guardian = Guardian::query()
                            ->where('social_worker_id', $this->currentSocialWorkerId())
                            ->where('national_code', trim((string) $entry['national_id']))
                            ->first();

                        if ($guardian) {
                            $guardianId = $guardian->id;
                            $fullName = $guardian->full_name !== '' ? $guardian->full_name : $fullName;
                            $mobile = $guardian->guardian_phone_number ?: $mobile;
                        }
                    } else {
                        $person = Person::query()
                            ->where('national_id', trim((string) $entry['national_id']))
                            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
                            ->first();

                        if ($person) {
                            $personId = $person->id;
                            $fullName = trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: $fullName;
                        }
                    }

                    ServiceDelivery::query()->create([
                        'delivery_batch_id' => $deliveryBatchIds[(int) $entry['_recipient_index']],
                        'service_id' => $service->id,
                        'social_worker_id' => $this->currentSocialWorkerId(),
                        'person_id' => $personId,
                        'guardian_id' => $guardianId,
                        'national_id' => trim((string) $entry['national_id']),
                        'full_name' => $fullName,
                        'mobile' => $mobile,
                        'delivered_quantity' => $entry['quantity'],
                        'service_category_id' => $serviceCategoryId,
                        'value_per_unit_snapshot' => $deliveryUnitValue,
                        'delivered_total_value' => (int) round((float) $entry['quantity'] * $deliveryUnitValue),
                        'delivered_at' => $this->jalaliToGregorian($validated['deliveredAt']),
                        'notes' => $validated['notes'] ?: null,
                        'created_by' => auth()->id(),
                    ]);
                }
            });
        } catch (ValidationException $exception) {
            $this->showValidationErrorModal(
                $exception->validator->errors()->all(),
                (string) $exception->validator->errors()->keys()[0]
            );

            throw $exception;
        }

        $freshService = $this->refreshSelectedServiceQuotaStats();

        $this->openNotificationModal([
            'type' => 'success',
            'template' => 'service-delivery-success',
            'title' => 'ثبت موفق تحویل',
            'message' => 'تحویل ثبت شد و فرم برای ثبت تحویل بعدی همین خدمت آماده است.',
            'buttons' => [
                [
                    'label' => 'ثبت تحویل جدید برای همین خدمت',
                    'action' => 'close',
                    'variant' => 'success',
                ],
                [
                    'label' => 'مشاهده سوابق',
                    'href' => route('social-worker.delivery-history'),
                    'variant' => 'secondary',
                ],
            ],
            'meta' => [
                'service_name' => $freshService?->serviceName?->name ?? '',
                'code' => $freshService?->code ?? '',
                'remaining_quota' => number_format(
                    $freshService
                        ? $this->remainingAllocationForService($freshService, $this->currentSocialWorkerId())
                        : 0,
                    2
                ),
                'next_action_hint' => 'برای ادامه، گیرنده بعدی را وارد کنید یا سوابق ثبت‌شده را بررسی کنید.',
            ],
        ]);
        $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId($freshService))];
        $this->notes = '';
        $this->deliveredAt = $this->defaultDeliveredAt();
        $this->flushSelectedServiceDerivedCaches();
    }

    public function getAssignedServicesProperty(): Collection
    {
        if ($this->assignedServicesCache instanceof Collection) {
            return $this->assignedServicesCache;
        }

        $services = Service::query()
            ->with(['serviceName', 'categories', 'workerAllocations'])
            ->whereHas('socialWorkers', function (Builder $query) {
                $query->where('social_workers.id', $this->currentSocialWorkerId())
                    ->where('service_social_worker.allocated_quantity', '>', 0);
            })
            ->supportsHomeDelivery()
            ->whereIn('status', ['approved', 'in_distribution'])
            ->latest()
            ->get();

        $deliveredByService = $services->isEmpty()
            ? collect()
            : ServiceDelivery::query()
                ->select('service_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->whereIn('service_id', $services->pluck('id')->all())
                ->groupBy('service_id')
                ->pluck('delivered_quantity', 'service_id');

        $services->each(function (Service $service) use ($deliveredByService): void {
            $allocated = $this->allocatedQuantityFromLoadedService($service, $this->currentSocialWorkerId());
            $delivered = (float) ($deliveredByService[$service->id] ?? 0);

            $service->setAttribute('worker_allocated_quantity', $allocated);
            $service->setAttribute('worker_delivered_quantity', $delivered);
            $service->setAttribute('worker_remaining_allocation', max(0, $allocated - $delivered));
        });

        return $this->assignedServicesCache = $services;
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if ($this->selectedServiceCache instanceof Service
            && (int) $this->selectedServiceCache->id === (int) $this->selectedServiceId) {
            return $this->selectedServiceCache;
        }

        $this->flushSelectedServiceDerivedCaches();

        if (! $this->selectedServiceId) {
            return null;
        }

        return $this->selectedServiceCache = $this->assignedServices
            ->firstWhere('id', (int) $this->selectedServiceId);
    }

    public function getCurrentAllocationProperty(): float
    {
        return (float) $this->selectedServiceTotals['allocated'];
    }

    public function getCurrentDeliveredProperty(): float
    {
        return (float) $this->selectedServiceTotals['delivered'];
    }

    public function getCurrentRemainingAllocationProperty(): float
    {
        return (float) $this->selectedServiceTotals['remaining'];
    }

    public function getAssignableCategoriesProperty(): Collection
    {
        if ($this->assignableCategoriesCache instanceof Collection
            && $this->assignableCategoriesCacheServiceId === (int) $this->selectedServiceId) {
            return $this->assignableCategoriesCache;
        }

        $service = $this->selectedService;

        if (! $service) {
            return collect();
        }

        $categoryMetrics = $this->selectedServiceCategoryMetrics;

        $this->assignableCategoriesCacheServiceId = (int) $service->id;

        return $this->assignableCategoriesCache = $service->categories
            ->filter(fn ($category) => (float) ($categoryMetrics[$category->id]['allocated'] ?? 0) > 0)
            ->sortBy('sort_id')
            ->values();
    }

    public function getSelectedServiceTotalsProperty(): array
    {
        if (is_array($this->selectedServiceTotalsCache)
            && $this->selectedServiceTotalsCacheServiceId === (int) $this->selectedServiceId) {
            return $this->selectedServiceTotalsCache;
        }

        $service = $this->selectedService;

        if (! $service) {
            $this->selectedServiceTotalsCacheServiceId = null;

            return $this->selectedServiceTotalsCache = [
                'allocated' => 0.0,
                'delivered' => 0.0,
                'remaining' => 0.0,
            ];
        }

        $this->selectedServiceTotalsCacheServiceId = (int) $service->id;

        return $this->selectedServiceTotalsCache = $this->buildSelectedServiceTotals($service);
    }

    public function getSelectedServiceCategoryMetricsProperty(): array
    {
        if (is_array($this->selectedServiceCategoryMetricsCache)
            && $this->selectedServiceCategoryMetricsCacheServiceId === (int) $this->selectedServiceId) {
            return $this->selectedServiceCategoryMetricsCache;
        }

        $service = $this->selectedService;

        if (! $service) {
            $this->selectedServiceCategoryMetricsCacheServiceId = null;

            return $this->selectedServiceCategoryMetricsCache = [];
        }

        $this->selectedServiceCategoryMetricsCacheServiceId = (int) $service->id;

        return $this->selectedServiceCategoryMetricsCache = $this->buildSelectedServiceCategoryMetrics($service);
    }

    public function getUnitOptionsProperty(): array
    {
        return $this->unitOptionsCache ??= Service::unitOptions();
    }

    public function getSelectedServiceTypeLabelProperty(): string
    {
        return $this->quotaState['service_type'] !== ''
            ? $this->quotaState['service_type']
            : $this->serviceTypeLabel($this->selectedService?->service_type);
    }

    public function render()
    {
        $selectedService = $this->selectedService;
        $categoryMetrics = $this->buildSelectedServiceCategoryMetrics($selectedService);
        $assignableCategories = $selectedService
            ? $selectedService->categories
                ->filter(fn ($category) => (float) ($categoryMetrics[$category->id]['allocated'] ?? 0) > 0)
                ->sortBy('sort_id')
                ->values()
            : collect();

        return view('livewire.social-workers.dashboard', [
            'assignedServices' => $this->assignedServices,
            'selectedService' => $selectedService,
            'assignableCategories' => $assignableCategories,
            'selectedServiceTotals' => $this->buildSelectedServiceTotals($selectedService),
            'categoryMetrics' => $categoryMetrics,
            'unitOptions' => $this->unitOptions,
        ]);
    }

    protected function showValidationErrorModal(array $errors, ?string $firstErrorKey = null): void
    {
        $messages = collect($errors)
            ->filter(fn ($message) => filled($message))
            ->values()
            ->all();

        if ($messages === []) {
            return;
        }

        $this->openNotificationModal([
            'type' => 'error',
            'title' => 'خطا در اعتبارسنجی فرم',
            'message' => 'لطفاً موارد زیر را بررسی کنید:
• '.implode('
• ', $messages),
            'buttons' => [[
                'label' => 'متوجه شدم',
                'action' => 'close',
                'variant' => 'danger',
            ]],
        ]);

        $this->dispatch('social-worker-delivery-validation-failed', field: $firstErrorKey);
    }

    protected function rules(): array
    {
        return [
            'selectedServiceId' => ['required', 'integer', Rule::in($this->assignedServices->pluck('id')->all())],
            'recipientEntries' => ['required', 'array', 'min:1'],
            'recipientEntries.*.national_id' => [
                'required',
                'digits:10',
            ],
            'recipientEntries.*.full_name' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    [$rowIndex] = sscanf($attribute, 'recipientEntries.%d.full_name');
                    $rowIndex = (int) $rowIndex;

                    if (! ($this->recipientEntries[$rowIndex]['is_unregistered'] ?? false)) {
                        return;
                    }

                    if (trim((string) $value) === '') {
                        $fail('نام و نام خانوادگی برای فرد ثبت‌نشده الزامی است.');
                    }
                },
            ],
            'recipientEntries.*.mobile' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'recipientEntries.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'recipientEntries.*.service_category_id' => [
                'nullable',
                'integer',
                Rule::in($this->assignableCategories->pluck('id')->all()),
            ],
            'recipientEntries.*.category_quantities' => ['nullable', 'array'],
            'recipientEntries.*.category_quantities.*' => ['nullable', 'numeric', 'min:0.01'],
            'deliveredAt' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ تحویل باید به فرمت شمسی معتبر مانند 1405/03/16 وارد شود.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedServiceId' => 'خدمت',
            'recipientEntries.*.national_id' => 'کد ملی گیرنده',
            'recipientEntries.*.full_name' => 'نام و نام خانوادگی',
            'recipientEntries.*.mobile' => 'موبایل',
            'recipientEntries.*.quantity' => 'مقدار',
            'recipientEntries.*.category_quantities.*' => 'مقدار دسته‌بندی',
            'deliveredAt' => 'تاریخ تحویل',
            'notes' => 'یادداشت',
        ];
    }

    protected function normalizedDeliveryEntries(array $recipientEntries): array
    {
        $assignableCategoryIds = $this->assignableCategories
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $assignableCategoryLookup = array_flip($assignableCategoryIds);
        $deliveryEntries = [];
        $missingRows = [];

        foreach ($recipientEntries as $index => $entry) {
            $rowDeliveries = [];

            foreach (($entry['category_quantities'] ?? []) as $categoryId => $quantity) {
                $categoryId = (int) $categoryId;
                $quantity = (float) $quantity;

                if ($categoryId <= 0 || $quantity <= 0) {
                    continue;
                }

                if (! isset($assignableCategoryLookup[$categoryId])) {
                    throw ValidationException::withMessages([
                        'recipientEntries' => 'دسته‌بندی انتخاب‌شده برای این خدمت معتبر نیست.',
                    ]);
                }

                $rowDeliveries[$categoryId] = ($rowDeliveries[$categoryId] ?? 0) + $quantity;
            }

            if ($rowDeliveries === [] && filled($entry['service_category_id'] ?? null) && filled($entry['quantity'] ?? null)) {
                $categoryId = (int) $entry['service_category_id'];
                $quantity = (float) $entry['quantity'];

                if ($categoryId > 0 && $quantity > 0) {
                    $rowDeliveries[$categoryId] = $quantity;
                }
            }

            if ($rowDeliveries === []) {
                $missingRows[] = $index + 1;

                continue;
            }

            foreach ($rowDeliveries as $categoryId => $quantity) {
                $deliveryEntries[] = array_merge($entry, [
                    '_recipient_index' => $index,
                    'service_category_id' => (int) $categoryId,
                    'quantity' => (float) $quantity,
                ]);
            }
        }

        if ($missingRows !== []) {
            $rows = implode('، ', $missingRows);

            throw ValidationException::withMessages([
                'recipientEntries' => "برای ردیف‌های {$rows} حداقل یک مقدار تحویل در دسته‌بندی‌ها وارد کنید.",
            ]);
        }

        return $deliveryEntries;
    }

    protected function validateNoDuplicateRecipientCategoryRows(array $recipientEntries): void
    {
        $seen = [];
        $duplicateRows = [];

        foreach ($recipientEntries as $index => $entry) {
            $nationalId = $this->normalizedNationalId((string) ($entry['national_id'] ?? ''));
            $categoryId = (int) ($entry['service_category_id'] ?? 0);

            if ($nationalId === '' || $categoryId <= 0) {
                continue;
            }

            $key = $nationalId.':'.$categoryId;

            if (isset($seen[$key])) {
                $duplicateRows[] = $index + 1;

                continue;
            }

            $seen[$key] = $index;
        }

        if ($duplicateRows === []) {
            return;
        }

        $rows = implode('، ', array_unique($duplicateRows));

        throw ValidationException::withMessages([
            'recipientEntries' => "گیرنده تکراری در همان دسته‌بندی خدمت ثبت شده است. لطفاً ردیف‌های {$rows} را ادغام یا حذف کنید.",
        ]);
    }

    protected function normalizedNationalId(string $nationalId): string
    {
        return preg_replace('/\D+/', '', trim($nationalId)) ?? '';
    }

    protected function currentSocialWorkerId(): int
    {
        return (int) auth()->user()->social_worker_id;
    }

    protected function blankEntry(?int $categoryId = null): array
    {
        return [
            'national_id' => '',
            'full_name' => '',
            'mobile' => '',
            'quantity' => '',
            'service_category_id' => $categoryId,
            'category_quantities' => $categoryId ? [$categoryId => ''] : [],
            'is_unregistered' => false,
            'not_found_notice' => '',
            'resolved_name' => '',
            'resolved_meta' => '',
            'covered_dependents_count' => null,
            'family_members_count' => null,
            'person_id' => null,
            'guardian_id' => null,
            'qr_token' => '',
        ];
    }

    protected function defaultDeliveredAt(): string
    {
        return Jalalian::fromDateTime(now())->format('Y/m/d');
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $date = $this->normalizeJalaliDate($date);
        $parts = explode('/', $date);

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
    }

    protected function normalizeJalaliDate(string $date): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $date = str_replace($persianDigits, range('0', '9'), $date);
        $date = str_replace($arabicDigits, range('0', '9'), $date);
        $date = str_replace(['-', '.', '‌', '‏', ' '], ['/', '/', ' ', ' ', ' '], $date);
        $date = trim(preg_replace('/\s+/', ' ', $date) ?? $date);

        return explode(' ', $date)[0] ?? '';
    }

    protected function remainingAllocationForCurrentWorker(): float
    {
        return (float) $this->selectedServiceTotals['remaining'];
    }

    protected function syncQuotaState(?Service $service = null): void
    {
        $service ??= $this->selectedService;

        $this->quotaState = [
            'service_type' => $this->serviceTypeLabel($service?->service_type),
        ];
    }

    protected function buildSelectedServiceTotals(?Service $service): array
    {
        if (! $service) {
            return [
                'allocated' => 0.0,
                'delivered' => 0.0,
                'remaining' => 0.0,
            ];
        }

        $allocated = $this->allocatedQuantityFromLoadedService($service, $this->currentSocialWorkerId());
        $delivered = (float) ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->sum('delivered_quantity');

        return [
            'allocated' => $allocated,
            'delivered' => $delivered,
            'remaining' => max(0, $allocated - $delivered),
        ];
    }

    protected function buildSelectedServiceCategoryMetrics(?Service $service): array
    {
        if (! $service) {
            return [];
        }

        $categories = $service->categories;
        $categoryIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deliveryRows = $categoryIds === []
            ? collect()
            : ServiceDelivery::query()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN social_worker_id = ? THEN delivered_quantity ELSE 0 END), 0) as worker_delivered_quantity',
                    [$this->currentSocialWorkerId()]
                )
                ->where('service_id', $service->id)
                ->whereIn('service_category_id', $categoryIds)
                ->groupBy('service_category_id')
                ->get()
                ->keyBy('service_category_id');

        $metrics = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category->id;
            $allocated = $service->workerAllocations
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->where('service_category_id', $categoryId)
                ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
            $row = $deliveryRows->get($categoryId);
            $delivered = (float) ($row?->delivered_quantity ?? 0);
            $workerDelivered = (float) ($row?->worker_delivered_quantity ?? 0);

            $metrics[$categoryId] = [
                'allocated' => (float) $allocated,
                'delivered' => $delivered,
                'worker_delivered' => $workerDelivered,
                'remaining_allocation' => max(0, (float) $allocated - $workerDelivered),
                'remaining_stock' => max(0, (float) $category->quantity - $delivered),
            ];
        }

        return $metrics;
    }

    protected function refreshSelectedServiceQuotaStats(): ?Service
    {
        $this->flushServiceMetricCaches();

        $service = $this->selectedService;
        $this->syncQuotaState($service);

        return $service;
    }

    protected function defaultServiceCategoryId(?Service $service = null): ?int
    {
        $service ??= $this->selectedService;

        if (! $service) {
            return null;
        }

        $categoryMetrics = $this->selectedServiceCategoryMetrics;

        return $this->assignableCategories
            ->first(fn ($category) => (float) ($categoryMetrics[$category->id]['remaining_stock'] ?? 0) > 0)?->id
            ?: $this->assignableCategories->first()?->id;
    }

    protected function serviceTypeLabel(?string $serviceType): string
    {
        return match ($serviceType) {
            'family' => 'خانوادگی',
            'individual' => 'شخصی',
            default => '',
        };
    }

    protected function clearResolvedEntry(int $index, bool $preserveNationalId = true): void
    {
        $qrToken = (string) ($this->recipientEntries[$index]['qr_token'] ?? '');

        $this->recipientEntries[$index]['national_id'] = $preserveNationalId
            ? (string) ($this->recipientEntries[$index]['national_id'] ?? '')
            : '';
        $this->recipientEntries[$index]['full_name'] = '';
        $this->recipientEntries[$index]['mobile'] = '';
        $this->recipientEntries[$index]['is_unregistered'] = false;
        $this->recipientEntries[$index]['not_found_notice'] = '';
        $this->recipientEntries[$index]['resolved_name'] = '';
        $this->recipientEntries[$index]['resolved_meta'] = '';
        $this->recipientEntries[$index]['covered_dependents_count'] = null;
        $this->recipientEntries[$index]['family_members_count'] = null;
        $this->recipientEntries[$index]['person_id'] = null;
        $this->recipientEntries[$index]['guardian_id'] = null;
        $this->recipientEntries[$index]['qr_token'] = $qrToken;
    }

    protected function fillGuardianEntry(int $index, Guardian $guardian, string $meta = ''): void
    {
        $fullName = $guardian->full_name !== '' ? $guardian->full_name : '-';

        $this->recipientEntries[$index]['national_id'] = (string) ($guardian->national_code ?? '');
        $this->recipientEntries[$index]['full_name'] = $fullName;
        $this->recipientEntries[$index]['mobile'] = (string) ($guardian->guardian_phone_number ?? '');
        $this->recipientEntries[$index]['is_unregistered'] = false;
        $this->recipientEntries[$index]['not_found_notice'] = '';
        $this->recipientEntries[$index]['guardian_id'] = $guardian->id;
        $this->recipientEntries[$index]['person_id'] = null;
        $this->recipientEntries[$index]['resolved_name'] = $fullName;
        $this->recipientEntries[$index]['resolved_meta'] = $meta;
        $this->recipientEntries[$index]['covered_dependents_count'] = (int) ($guardian->children_count ?? $guardian->people_count ?? 0);
        $this->recipientEntries[$index]['family_members_count'] = (int) ($guardian->children_in_house ?? 0);
    }

    protected function fillPersonEntry(int $index, Person $person, string $meta = ''): void
    {
        $fullName = trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: '-';
        $guardian = $person->guardian;

        $this->recipientEntries[$index]['national_id'] = (string) ($person->national_id ?? '');
        $this->recipientEntries[$index]['full_name'] = $fullName;
        $this->recipientEntries[$index]['mobile'] = '';
        $this->recipientEntries[$index]['is_unregistered'] = false;
        $this->recipientEntries[$index]['not_found_notice'] = '';
        $this->recipientEntries[$index]['person_id'] = $person->id;
        $this->recipientEntries[$index]['guardian_id'] = null;
        $this->recipientEntries[$index]['resolved_name'] = $fullName;
        $this->recipientEntries[$index]['resolved_meta'] = $meta;
        $this->recipientEntries[$index]['covered_dependents_count'] = (int) ($guardian?->children_count ?? 0);
        $this->recipientEntries[$index]['family_members_count'] = (int) ($guardian?->children_in_house ?? 0);
    }

    protected function recipientQrScanResponse(bool $ok, string $message, string $name = ''): array
    {
        return [
            'ok' => $ok,
            'status' => $ok ? 'paused' : 'scan_error',
            'message' => $message,
            'result' => [
                'ok' => $ok,
                'code' => $ok ? 'resolved' : 'invalid_qr',
                'message' => $message,
                'name' => $name,
            ],
        ];
    }

    public function getRecipientSuggestionsProperty(): array
    {
        $suggestions = [];
        $service = $this->selectedService;

        foreach ($this->recipientEntries as $index => $entry) {
            if ($this->activeRecipientSearchIndex !== $index || ! $service) {
                $suggestions[$index] = collect();

                continue;
            }

            $query = trim((string) ($entry['national_id'] ?? ''));

            if (mb_strlen($query) < 2) {
                $suggestions[$index] = collect();

                continue;
            }

            $suggestions[$index] = $service->service_type === 'family'
                ? $this->guardianSuggestions($query)
                : $this->personSuggestions($query);
        }

        return $suggestions;
    }

    protected function guardianSuggestions(string $query)
    {
        return Guardian::query()
            ->withCount('people')
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->where(function (Builder $guardianQuery) use ($query): void {
                $guardianQuery->where('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%')
                    ->orWhere('national_code', 'like', $query.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query.'%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$query.'%']);
            })
            ->orderByRaw(
                "CASE
                    WHEN first_name LIKE ? THEN 1
                    WHEN last_name LIKE ? THEN 2
                    WHEN national_code LIKE ? THEN 3
                    WHEN CONCAT_WS(' ', first_name, last_name) LIKE ? THEN 4
                    ELSE 5
                END",
                [$query.'%', $query.'%', $query.'%', $query.'%']
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(6)
            ->get(['id', 'first_name', 'last_name', 'national_code', 'children_count', 'children_in_house']);
    }

    protected function personSuggestions(string $query)
    {
        return Person::query()
            ->with(['guardian:id,children_count,children_in_house'])
            ->whereHas('guardian', fn (Builder $guardianQuery) => $guardianQuery->where('social_worker_id', $this->currentSocialWorkerId()))
            ->where(function (Builder $personQuery) use ($query): void {
                $personQuery->where('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%')
                    ->orWhere('national_id', 'like', $query.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query.'%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$query.'%']);
            })
            ->orderByRaw(
                "CASE
                    WHEN first_name LIKE ? THEN 1
                    WHEN last_name LIKE ? THEN 2
                    WHEN national_id LIKE ? THEN 3
                    WHEN CONCAT_WS(' ', first_name, last_name) LIKE ? THEN 4
                    ELSE 5
                END",
                [$query.'%', $query.'%', $query.'%', $query.'%']
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(6)
            ->get(['id', 'first_name', 'last_name', 'national_id']);
    }

    protected function markUnregisteredRecipient(int $index, string $nationalId): void
    {
        $this->clearResolvedEntry($index, preserveNationalId: true);
        $this->recipientEntries[$index]['national_id'] = $nationalId;
        $this->recipientEntries[$index]['is_unregistered'] = true;
        $this->recipientEntries[$index]['not_found_notice'] = 'این شخص در سامانه ثبت نشده است';
    }

    protected function resolveServiceDeliveryQrIdentity(?string $token): ?QrIdentity
    {
        if (! $token) {
            return null;
        }

        $identity = $this->resolveTokenHashQrIdentity($token)
            ?: $this->resolvePublicQrCode($token);

        if (! $identity) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity;
    }

    protected function resolveTokenHashQrIdentity(string $token): ?QrIdentity
    {
        return QrIdentity::query()
            ->select(['id', 'subject_type', 'subject_id', 'status', 'last_scanned_at'])
            ->where('token_hash', app(QrIdentityService::class)->hashToken($token))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();
    }

    protected function resolvePublicQrCode(string $token): ?QrIdentity
    {
        return QrIdentity::query()
            ->select(['id', 'subject_type', 'subject_id', 'status', 'last_scanned_at'])
            ->where('public_code', strtoupper(trim($token)))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();
    }

    protected function extractQrToken(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\/qr\/r\/([^\/?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }

    protected function flushServiceMetricCaches(): void
    {
        $this->assignedServicesCache = null;
        $this->selectedServiceCache = null;
        $this->flushSelectedServiceDerivedCaches();
    }

    protected function flushSelectedServiceDerivedCaches(): void
    {
        $this->assignableCategoriesCache = null;
        $this->selectedServiceTotalsCache = null;
        $this->selectedServiceCategoryMetricsCache = null;
        $this->assignableCategoriesCacheServiceId = null;
        $this->selectedServiceTotalsCacheServiceId = null;
        $this->selectedServiceCategoryMetricsCacheServiceId = null;
    }

    protected function allocatedQuantityFromLoadedService(Service $service, int $socialWorkerId): float
    {
        return (float) $service->workerAllocations
            ->where('social_worker_id', $socialWorkerId)
            ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
    }

    protected function remainingAllocationForService(Service $service, int $socialWorkerId): float
    {
        $allocated = $this->allocatedQuantityFromLoadedService($service, $socialWorkerId);
        $delivered = ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('social_worker_id', $socialWorkerId)
            ->sum('delivered_quantity');

        return max(0, $allocated - (float) $delivered);
    }
}
