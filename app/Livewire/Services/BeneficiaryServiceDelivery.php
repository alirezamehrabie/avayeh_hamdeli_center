<?php

namespace App\Livewire\Services;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Helpers\PersianText;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Services\Deliveries\DirectDeliveryRecorder;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * «تحویل مستقیم خدمت به مددجو / سرپرست»
 *
 * Direct final-delivery workflow for the Manager dashboard: the manager picks
 * any deliverable service, the recipient type follows the service type
 * (individual → Person, family → Guardian), and the delivery is recorded in
 * the canonical ServiceDelivery ledger without a social-worker allocation.
 */
class BeneficiaryServiceDelivery extends Component
{
    use InteractsWithNotificationModal;

    public const STEP_SERVICE = 1;

    public const STEP_RECIPIENT = 2;

    public const STEP_ITEMS = 3;

    public const STEP_REVIEW = 4;

    public int $step = self::STEP_SERVICE;

    // --- Step 1: service selection -------------------------------------
    public ?int $selectedServiceId = null;

    public string $serviceSearch = '';

    // --- Step 2: recipient ---------------------------------------------
    public string $recipientSearch = '';

    public ?int $selectedPersonId = null;

    public ?int $selectedGuardianId = null;

    public bool $manualEntry = false;

    public string $manualNationalId = '';

    public string $manualFullName = '';

    public string $manualMobile = '';

    /** Snapshot of the selected recipient shown on the review step. */
    public array $recipientSummary = [];

    // --- Step 3: items ---------------------------------------------------
    /** @var array<int, string> category id => quantity input */
    public array $categoryQuantities = [];

    public string $deliveredAt = '';

    public string $notes = '';

    // --- Success state ---------------------------------------------------
    public bool $submitting = false;

    public ?array $successState = null;

    protected ?Service $selectedServiceCache = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        $this->deliveredAt = Jalalian::fromDateTime(now())->format('Y/m/d');
    }

    // ------------------------------------------------------------------
    // Step 1: service selection
    // ------------------------------------------------------------------

    public function selectService(int $serviceId): void
    {
        $this->authorizeManager();

        $service = $this->deliverableServiceQuery()->whereKey($serviceId)->first();

        if (! $service) {
            $this->openNotificationModal([
                'type' => 'error',
                'title' => 'خدمت قابل تحویل نیست',
                'message' => 'خدمت انتخاب‌شده یافت نشد یا در وضعیت قابل تحویل قرار ندارد.',
            ]);

            return;
        }

        $this->selectedServiceId = (int) $service->id;
        $this->selectedServiceCache = null;
        $this->resetRecipientState();
        $this->resetItemsState();
        $this->successState = null;
        $this->step = self::STEP_RECIPIENT;
        $this->resetValidation();
    }

    public function changeService(): void
    {
        $this->authorizeManager();

        $this->selectedServiceId = null;
        $this->selectedServiceCache = null;
        $this->resetRecipientState();
        $this->resetItemsState();
        $this->successState = null;
        $this->step = self::STEP_SERVICE;
        $this->resetValidation();
    }

    // ------------------------------------------------------------------
    // Step 2: recipient
    // ------------------------------------------------------------------

    public function selectRecipient(int $id): void
    {
        $this->authorizeManager();

        $service = $this->selectedService;
        abort_unless($service, 404);

        if ($service->service_type === 'family') {
            $guardian = Guardian::query()->withCount('people')->findOrFail($id);

            $this->selectedGuardianId = (int) $guardian->id;
            $this->selectedPersonId = null;
            $this->recipientSummary = [
                'type' => 'guardian',
                'type_label' => 'سرپرست خانوار',
                'name' => $guardian->full_name !== '' ? $guardian->full_name : '-',
                'national_id' => (string) ($guardian->national_code ?: '-'),
                'code_label' => 'کد خانوار',
                'code' => (string) ($guardian->guardian_code ?: '-'),
                'mobile' => (string) ($guardian->guardian_phone_number ?: ''),
                'meta' => $guardian->people_count.' نفر تحت تکفل',
                'is_manual' => false,
            ];
        } else {
            $person = Person::query()->with('guardian:id,guardian_phone_number')->findOrFail($id);

            $this->selectedPersonId = (int) $person->id;
            $this->selectedGuardianId = null;
            $this->recipientSummary = [
                'type' => 'person',
                'type_label' => 'مددجو',
                'name' => trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: '-',
                'national_id' => (string) ($person->national_id ?: '-'),
                'code_label' => 'کد مددجو',
                'code' => (string) ($person->person_code ?: '-'),
                'mobile' => (string) (($person->phone_number ?: $person->guardian?->guardian_phone_number) ?: ''),
                'meta' => '',
                'is_manual' => false,
            ];
        }

        $this->manualEntry = false;
        $this->recipientSearch = '';
        $this->resetValidation();
        $this->goToItemsStep();
    }

    public function startManualEntry(): void
    {
        $this->authorizeManager();

        $this->manualEntry = true;
        $this->selectedPersonId = null;
        $this->selectedGuardianId = null;
        $this->recipientSummary = [];

        // Carry an entered national id over into the manual form.
        $digits = PersianText::digitsOnly($this->recipientSearch);

        if (preg_match('/^\d{10}$/', $digits)) {
            $this->manualNationalId = $digits;
        }

        $this->resetValidation();
    }

    public function cancelManualEntry(): void
    {
        $this->manualEntry = false;
        $this->manualNationalId = '';
        $this->manualFullName = '';
        $this->manualMobile = '';
        $this->resetValidation();
    }

    public function confirmManualEntry(): void
    {
        $this->authorizeManager();

        $service = $this->selectedService;
        abort_unless($service, 404);

        try {
            $recipient = app(DirectDeliveryRecorder::class)->resolveRecipient(
                $service,
                null,
                null,
                $this->manualNationalId,
                $this->manualFullName,
                $this->manualMobile,
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator?->errors() ?? $exception->errors());
            $this->showValidationErrorModal(collect($exception->errors())->flatten()->all());

            return;
        }

        // A registered match wins over a manual record — link it directly.
        $this->selectedPersonId = $recipient['person_id'];
        $this->selectedGuardianId = $recipient['guardian_id'];

        $isFamily = $service->service_type === 'family';

        $this->recipientSummary = [
            'type' => $recipient['is_manual'] ? 'manual' : ($isFamily ? 'guardian' : 'person'),
            'type_label' => $recipient['is_manual']
                ? ($isFamily ? 'سرپرست (ثبت دستی)' : 'مددجو (ثبت دستی)')
                : ($isFamily ? 'سرپرست خانوار' : 'مددجو'),
            'name' => $recipient['full_name'],
            'national_id' => $recipient['national_id'],
            'code_label' => $isFamily ? 'کد خانوار' : 'کد مددجو',
            'code' => '-',
            'mobile' => (string) ($recipient['mobile'] ?? ''),
            'meta' => $recipient['is_manual'] ? 'این شخص در سامانه ثبت نشده است' : 'رکورد ثبت‌شده با همین کد ملی پیدا و متصل شد',
            'is_manual' => $recipient['is_manual'],
        ];

        $this->resetValidation();
        $this->goToItemsStep();
    }

    public function changeRecipient(): void
    {
        $this->authorizeManager();

        $this->resetRecipientState();
        $this->resetItemsState();
        $this->successState = null;
        $this->step = self::STEP_RECIPIENT;
        $this->resetValidation();
    }

    // ------------------------------------------------------------------
    // Step 3 → 4: items and review
    // ------------------------------------------------------------------

    public function goToReview(): void
    {
        $this->authorizeManager();

        $service = $this->selectedService;
        abort_unless($service, 404);

        try {
            $this->validate($this->itemRules(), [], [
                'deliveredAt' => 'تاریخ تحویل',
                'notes' => 'یادداشت',
                'categoryQuantities.*' => 'مقدار دسته‌بندی',
            ]);
            $this->validateItemSelection();
        } catch (ValidationException $exception) {
            $this->showValidationErrorModal($exception->validator->errors()->all());

            throw $exception;
        }

        $this->step = self::STEP_REVIEW;
    }

    public function backToItems(): void
    {
        $this->step = self::STEP_ITEMS;
    }

    public function confirmDelivery(): void
    {
        $this->authorizeManager();

        if ($this->submitting) {
            return;
        }

        $service = $this->selectedService;
        abort_unless($service, 404);

        $this->submitting = true;

        try {
            $this->validate($this->itemRules(), [], [
                'deliveredAt' => 'تاریخ تحویل',
                'notes' => 'یادداشت',
                'categoryQuantities.*' => 'مقدار دسته‌بندی',
            ]);
            $this->validateItemSelection();

            $recorder = app(DirectDeliveryRecorder::class);

            // Recipient is always re-resolved server-side at submit time.
            $recipient = $recorder->resolveRecipient(
                $service,
                $this->selectedPersonId,
                $this->selectedGuardianId,
                $this->manualNationalId,
                $this->manualFullName,
                $this->manualMobile,
            );

            $result = $recorder->record(
                $service,
                $recipient,
                $this->normalizedQuantities(),
                $this->jalaliToGregorian($this->deliveredAt),
                trim($this->notes) ?: null,
                (int) auth()->id(),
            );
        } catch (ValidationException $exception) {
            $this->submitting = false;
            $this->showValidationErrorModal($exception->validator?->errors()->all() ?? collect($exception->errors())->flatten()->all());

            throw $exception;
        } catch (\Throwable $exception) {
            $this->submitting = false;
            report($exception);
            $this->openSystemErrorModal();

            return;
        }

        $this->successState = $this->buildSuccessState($service, $recipient, $result);
        $this->submitting = false;
        $this->step = self::STEP_REVIEW;
    }

    public function startNewDelivery(): void
    {
        $this->authorizeManager();

        $this->resetRecipientState();
        $this->resetItemsState();
        $this->successState = null;
        $this->step = $this->selectedServiceId ? self::STEP_RECIPIENT : self::STEP_SERVICE;
        $this->resetValidation();
    }

    // ------------------------------------------------------------------
    // Computed properties
    // ------------------------------------------------------------------

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        if ($this->selectedServiceCache instanceof Service
            && (int) $this->selectedServiceCache->id === (int) $this->selectedServiceId) {
            return $this->selectedServiceCache;
        }

        return $this->selectedServiceCache = $this->deliverableServiceQuery()
            ->with([
                'serviceName',
                'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id'),
            ])
            ->find($this->selectedServiceId);
    }

    public function getDeliverableServicesProperty(): Collection
    {
        if ($this->selectedServiceId) {
            return collect();
        }

        $search = PersianText::normalizeText($this->serviceSearch);

        return $this->deliverableServiceQuery()
            ->with(['serviceName', 'creator:id,first_name,last_name,access_level'])
            ->withCount('categories')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $digits = PersianText::digitsOnly($search);

                $query->where(function (Builder $searchQuery) use ($search, $digits): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('serviceName', fn (Builder $nameQuery) => $nameQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));

                    if ($digits !== '') {
                        $searchQuery->orWhere('code', 'like', "%{$digits}%");
                    } else {
                        $searchQuery->orWhere('code', 'like', "%{$search}%");
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(60)
            ->get();
    }

    public function getRecipientCandidatesProperty(): Collection
    {
        $service = $this->selectedService;
        $search = PersianText::normalizeText($this->recipientSearch);

        if (! $service || $this->manualEntry || mb_strlen($search) < 2) {
            return collect();
        }

        return $service->service_type === 'family'
            ? $this->guardianCandidates($search)
            : $this->personCandidates($search);
    }

    public function getCategoryMetricsProperty(): array
    {
        $service = $this->selectedService;

        if (! $service) {
            return [];
        }

        $categoryIds = $service->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deliveredByCategory = $categoryIds === []
            ? collect()
            : \App\Models\ServiceDelivery::query()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->where('service_id', $service->id)
                ->whereIn('service_category_id', $categoryIds)
                ->groupBy('service_category_id')
                ->pluck('delivered_quantity', 'service_category_id');

        $metrics = [];

        foreach ($service->categories as $category) {
            $delivered = (float) ($deliveredByCategory[$category->id] ?? 0);

            $metrics[(int) $category->id] = [
                'delivered' => $delivered,
                'remaining_stock' => max(0, (float) $category->quantity - $delivered),
            ];
        }

        return $metrics;
    }

    public function getUnitOptionsProperty(): array
    {
        return Service::unitOptions();
    }

    public function getServiceTypeLabelProperty(): string
    {
        return match ($this->selectedService?->service_type) {
            'family' => 'خانوادگی (سرپرست)',
            'individual' => 'شخصی (مددجو)',
            default => '',
        };
    }

    public function persianNumber(mixed $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            ',' => '٬',
        ]);
    }

    public function render()
    {
        return view('livewire.services.beneficiary-service-delivery', [
            'selectedService' => $this->selectedService,
            'deliverableServices' => $this->deliverableServices,
            'recipientCandidates' => $this->recipientCandidates,
            'categoryMetrics' => $this->categoryMetrics,
            'unitOptions' => $this->unitOptions,
        ]);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function authorizeManager(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    /**
     * All registered services in a deliverable status — regardless of the
     * creator (manager-defined and distribution-operator "misc" services alike)
     * and regardless of the supported delivery channels.
     */
    protected function deliverableServiceQuery(): Builder
    {
        return Service::query()->whereIn('status', ['approved', 'in_distribution']);
    }

    protected function goToItemsStep(): void
    {
        $this->resetItemsState();
        $this->step = self::STEP_ITEMS;
    }

    protected function resetRecipientState(): void
    {
        $this->recipientSearch = '';
        $this->selectedPersonId = null;
        $this->selectedGuardianId = null;
        $this->manualEntry = false;
        $this->manualNationalId = '';
        $this->manualFullName = '';
        $this->manualMobile = '';
        $this->recipientSummary = [];
    }

    protected function resetItemsState(): void
    {
        $this->categoryQuantities = [];
        $this->notes = '';
        $this->deliveredAt = Jalalian::fromDateTime(now())->format('Y/m/d');
    }

    protected function itemRules(): array
    {
        return [
            'categoryQuantities' => ['array'],
            'categoryQuantities.*' => ['nullable', 'numeric', 'min:0.01'],
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

    /**
     * Cross-field checks that plain rules cannot express: at least one
     * positive quantity, only categories of the selected service, and within
     * the remaining stock (a pre-check; the recorder re-validates under lock).
     */
    protected function validateItemSelection(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        if (! $this->hasRecipient()) {
            throw ValidationException::withMessages([
                'recipient' => 'ابتدا گیرنده خدمت را انتخاب یا به‌صورت دستی ثبت کنید.',
            ]);
        }

        $quantities = $this->normalizedQuantities();

        if ($quantities === []) {
            throw ValidationException::withMessages([
                'categoryQuantities' => 'حداقل یک مقدار تحویل برای دسته‌بندی‌های خدمت وارد کنید.',
            ]);
        }

        $categories = $service->categories->keyBy('id');
        $metrics = $this->categoryMetrics;

        foreach ($quantities as $categoryId => $quantity) {
            $category = $categories->get($categoryId);

            if (! $category) {
                throw ValidationException::withMessages([
                    'categoryQuantities' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
                ]);
            }

            if (! Service::unitUsesDecimalPrecision($category->unit) && fmod($quantity, 1.0) !== 0.0) {
                throw ValidationException::withMessages([
                    'categoryQuantities.'.$categoryId => "مقدار «{$category->name}» باید عدد صحیح باشد ({$this->unitLabelFor($category->unit)}).",
                ]);
            }

            $remaining = (float) ($metrics[$categoryId]['remaining_stock'] ?? 0);

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    'categoryQuantities.'.$categoryId => "مقدار تحویل «{$category->name}» از موجودی باقی‌مانده ({$this->formatQuantity($remaining, $category->unit)}) بیشتر است.",
                ]);
            }
        }
    }

    /** @return array<int, float> */
    protected function normalizedQuantities(): array
    {
        $quantities = [];

        foreach ($this->categoryQuantities as $categoryId => $quantity) {
            $value = (float) PersianText::normalizeDigits((string) $quantity);

            if ((int) $categoryId > 0 && $value > 0) {
                $quantities[(int) $categoryId] = $value;
            }
        }

        return $quantities;
    }

    protected function hasRecipient(): bool
    {
        return $this->selectedPersonId !== null
            || $this->selectedGuardianId !== null
            || ($this->manualEntry && $this->recipientSummary !== []);
    }

    protected function personCandidates(string $search): Collection
    {
        $digits = PersianText::digitsOnly($search);
        $escaped = addcslashes($search, '\\%_');

        return Person::query()
            ->with('guardian:id,guardian_phone_number')
            ->where(function (Builder $query) use ($escaped, $digits): void {
                if ($digits !== '') {
                    $query->where('national_id', 'like', "{$digits}%")
                        ->orWhere('person_code', 'like', "{$digits}%")
                        ->orWhere('phone_number', 'like', "%{$digits}%");
                } else {
                    $query->where('normalized_full_name', 'like', "%{$escaped}%")
                        ->orWhere('full_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "{$escaped}%")
                        ->orWhere('last_name', 'like', "{$escaped}%");
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Person $person): array => [
                'id' => (int) $person->id,
                'name' => trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: '-',
                'national_id' => (string) ($person->national_id ?: '-'),
                'code_label' => 'کد مددجو',
                'code' => (string) ($person->person_code ?: '-'),
                'mobile' => (string) (($person->phone_number ?: $person->guardian?->guardian_phone_number) ?: ''),
            ]);
    }

    protected function guardianCandidates(string $search): Collection
    {
        $digits = PersianText::digitsOnly($search);
        $escaped = addcslashes($search, '\\%_');

        return Guardian::query()
            ->withCount('people')
            ->where(function (Builder $query) use ($escaped, $digits): void {
                if ($digits !== '') {
                    $query->where('national_code', 'like', "{$digits}%")
                        ->orWhere('guardian_code', 'like', "{$digits}%")
                        ->orWhere('guardian_phone_number', 'like', "%{$digits}%");
                } else {
                    $query->where('first_name', 'like', "{$escaped}%")
                        ->orWhere('last_name', 'like', "{$escaped}%")
                        ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$escaped}%"]);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Guardian $guardian): array => [
                'id' => (int) $guardian->id,
                'name' => $guardian->full_name !== '' ? $guardian->full_name : '-',
                'national_id' => (string) ($guardian->national_code ?: '-'),
                'code_label' => 'کد خانوار',
                'code' => (string) ($guardian->guardian_code ?: '-'),
                'mobile' => (string) ($guardian->guardian_phone_number ?: ''),
                'meta' => $guardian->people_count.' نفر تحت تکفل',
            ]);
    }

    protected function buildSuccessState(Service $service, array $recipient, array $result): array
    {
        $categories = $service->categories->keyBy('id');
        $date = $this->deliveredAt;

        $items = $result['deliveries']
            ->map(function ($delivery) use ($categories, $date): array {
                $category = $categories->get((int) $delivery->service_category_id);
                $unitKey = $category?->unit;

                return [
                    'category' => $category?->name ?: '-',
                    'quantity' => Service::formatQuantityForUnit($delivery->delivered_quantity, $unitKey),
                    'unitLabel' => $this->unitLabelFor($unitKey),
                    'recordCount' => 1,
                    'date' => $date,
                ];
            })
            ->values()
            ->all();

        $unitTotals = $result['deliveries']
            ->groupBy(fn ($delivery) => $categories->get((int) $delivery->service_category_id)?->unit ?: '__none__')
            ->map(function ($unitDeliveries, $unitKey): array {
                $hasUnit = $unitKey !== '__none__';
                $total = $unitDeliveries->sum(fn ($delivery) => (float) $delivery->delivered_quantity);

                return [
                    'unitKey' => $hasUnit ? $unitKey : null,
                    'label' => $hasUnit ? $this->unitLabelFor($unitKey) : '-',
                    'total' => Service::formatQuantityForUnit($total, $hasUnit ? $unitKey : null),
                ];
            })
            ->values()
            ->all();

        $operator = auth()->user();

        return [
            'tracking_code' => $result['tracking_code'],
            'batch_id' => $result['batch_id'],
            'service_name' => $service->serviceName?->name ?: ($service->name ?: '-'),
            'service_code' => (string) $service->code,
            'recipient' => $this->recipientSummary + ['mobile' => (string) ($recipient['mobile'] ?? '')],
            'items' => $items,
            'unit_totals' => $unitTotals,
            'total_value' => (int) $result['deliveries']->sum('delivered_total_value'),
            'delivered_at' => $date,
            'operator_name' => trim(($operator->first_name ?? '').' '.($operator->last_name ?? '')) ?: ($operator->name ?? '-'),
            'receipt' => [
                'recipientName' => $this->recipientSummary['name'] ?? '-',
                'recipientType' => $this->recipientSummary['type_label'] ?? '-',
                'nationalId' => $this->recipientSummary['national_id'] ?? '-',
                'recipientCode' => ($this->recipientSummary['code'] ?? '-') !== '-' ? $this->recipientSummary['code'] : null,
                'recipientCodeLabel' => $this->recipientSummary['code_label'] ?? 'کد',
                'relation' => 'کد پیگیری: '.$result['tracking_code'],
                'mobile' => (string) ($recipient['mobile'] ?? '') ?: null,
                'serviceName' => $service->serviceName?->name ?: ($service->name ?: 'خدمت'),
                'serviceCode' => (string) $service->code,
                'date' => $date,
                'items' => $items,
                'unitTotals' => $unitTotals,
            ],
        ];
    }

    protected function showValidationErrorModal(array $errors): void
    {
        $messages = collect($errors)->filter(fn ($message) => filled($message))->values()->all();

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
    }

    protected function unitLabelFor(?string $unitKey): string
    {
        if (! $unitKey) {
            return '-';
        }

        return $this->unitOptions[$unitKey] ?? $unitKey;
    }

    protected function formatQuantity(float $value, ?string $unitKey): string
    {
        return Service::formatQuantityForUnit($value, $unitKey);
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
        $date = PersianText::normalizeDigits($date);
        $date = str_replace(['-', '.'], '/', $date);
        $date = trim(preg_replace('/\s+/', ' ', $date) ?? $date);

        return explode(' ', $date)[0] ?? '';
    }
}
