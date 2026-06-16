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
use App\Services\QrIdentityService;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);
        $this->recipientEntries = [$this->blankEntry()];
        $this->deliveredAt = $this->defaultDeliveredAt();
    }

    public function updatedSelectedServiceId(): void
    {
        $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId())];
        $this->activeRecipientSearchIndex = null;
        $this->syncQuotaState();
        $this->serviceSelectionWarning = '';
        $this->closeNotificationModal();
        $this->resetValidation();
    }

    public function requireServiceSelection(): void
    {
        if ($this->selectedService) {
            $this->serviceSelectionWarning = '';

            return;
        }

        $this->serviceSelectionWarning = 'لطفاً یک خدمت انتخاب کنید';
    }

    public function addRecipientField(): void
    {
        $this->recipientEntries[] = $this->blankEntry($this->defaultServiceCategoryId());
    }

    public function removeRecipientField(int $index): void
    {
        unset($this->recipientEntries[$index]);
        $this->recipientEntries = array_values($this->recipientEntries);

        if ($this->recipientEntries === []) {
            $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId())];
        }
    }

    public function updatedRecipientEntries($value, string $key): void
    {
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

        if (! $this->selectedService) {
            return;
        }

        if (! preg_match('/^\d{10}$/', $query)) {
            return;
        }

        if ($this->selectedService->service_type === 'family') {
            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->where('national_code', $query)
                ->first();

            if ($guardian) {
                $this->fillGuardianEntry($index, $guardian, $guardian->people_count . ' نفر تحت تکفل');
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
        abort_unless($this->selectedService, 404);

        $rawToken = trim((string) ($this->recipientEntries[$index]['qr_token'] ?? ''));

        if ($rawToken === '') {
            $this->addError('recipientEntries.' . $index . '.qr_token', 'وارد کردن توکن یا نشانی QR الزامی است.');

            return;
        }

        $qrIdentityService = app(QrIdentityService::class);
        $identity = $qrIdentityService->resolveToken($this->extractQrToken($rawToken), 'service-delivery');

        if (! $identity) {
            $this->clearResolvedEntry($index, preserveNationalId: false);
            $this->addError('recipientEntries.' . $index . '.qr_token', 'QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.');

            return;
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            if ($this->selectedService->service_type !== 'family') {
                $this->addError('recipientEntries.' . $index . '.qr_token', 'QR سرپرست فقط برای خدمات خانوادگی قابل استفاده است.');

                return;
            }

            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->find($identity->subject_id);

            if (! $guardian) {
                $this->addError('recipientEntries.' . $index . '.qr_token', 'این سرپرست به حساب شما تخصیص داده نشده است.');

                return;
            }

            $this->fillGuardianEntry($index, $guardian, $guardian->people_count . ' نفر تحت تکفل');

            return;
        }

        $person = Person::query()
            ->with('guardian:id,social_worker_id,children_count,children_in_house,national_code,first_name,last_name,guardian_phone_number')
            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
            ->find($identity->subject_id);

        if (! $person) {
            $this->addError('recipientEntries.' . $index . '.qr_token', 'این مددجو به حساب شما تخصیص داده نشده است.');

            return;
        }

        if ($this->selectedService->service_type === 'family') {
            $guardian = $person->guardian;

            if (! $guardian) {
                $this->addError('recipientEntries.' . $index . '.qr_token', 'این مددجو برای ثبت خدمت خانوادگی سرپرست مرتبط ندارد.');

                return;
            }

            $guardian->loadCount('people');
            $this->fillGuardianEntry($index, $guardian, $guardian->people_count . ' نفر تحت تکفل');

            return;
        }

        $this->fillPersonEntry($index, $person, 'مددجو');
    }

    public function setActiveRecipientSearch(int $index): void
    {
        $query = trim((string) ($this->recipientEntries[$index]['national_id'] ?? ''));
        $this->activeRecipientSearchIndex = mb_strlen($query) >= 2 ? $index : null;
    }

    public function selectRecipientSuggestion(int $index, string $type, int $id): void
    {
        abort_unless($this->selectedService, 404);

        if ($this->selectedService->service_type === 'family') {
            abort_unless($type === 'guardian', 404);

            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->findOrFail($id);

            $this->fillGuardianEntry($index, $guardian, $guardian->people_count . ' نفر تحت تکفل');
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
            $this->showValidationErrorModal($exception->validator->errors()->all());

            throw $exception;
        }

        $service = $this->selectedService;
        abort_unless($service, 404);

        $entryQuantitiesByCategory = collect($validated['recipientEntries'])
            ->groupBy(fn (array $entry) => (int) $entry['service_category_id'])
            ->map(fn ($entries) => $entries->sum(fn (array $entry) => (float) $entry['quantity']));

        foreach ($entryQuantitiesByCategory as $categoryId => $quantity) {
            if ((float) $quantity <= $service->remainingAllocationForWorkerCategory($this->currentSocialWorkerId(), (int) $categoryId)) {
                continue;
            }

            $message = 'جمع مقادیر ثبت‌شده از سهمیه تخصیص‌یافته شما برای این آیتم بیشتر است.';
            $this->addError('recipientEntries', $message);
            $this->showValidationErrorModal([$message]);

            return;
        }

        try {
            DB::transaction(function () use ($service, $validated): void {
                $categoryQuantities = collect($validated['recipientEntries'])
                    ->groupBy(fn (array $entry) => (int) $entry['service_category_id'])
                    ->map(fn ($entries) => $entries->sum(fn (array $entry) => (float) $entry['quantity']));

                $lockedCategories = ServiceCategory::query()
                    ->where('service_id', $service->id)
                    ->whereIn('id', $categoryQuantities->keys()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($categoryQuantities as $categoryId => $quantity) {
                    $category = $lockedCategories->get((int) $categoryId);

                    if (! $category || $service->remainingStockForCategory((int) $categoryId) < (float) $quantity) {
                        throw ValidationException::withMessages([
                            'recipientEntries' => 'مقدار تحویل برای یکی از دسته‌بندی‌ها از موجودی همان دسته‌بندی بیشتر است.',
                        ]);
                    }
                }

                foreach ($validated['recipientEntries'] as $entry) {
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
                            $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: $fullName;
                        }
                    }

                    ServiceDelivery::query()->create([
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
            $this->showValidationErrorModal($exception->validator->errors()->all());

            throw $exception;
        }

        $freshService = Service::query()
            ->with(['serviceName', 'categories', 'workerAllocations'])
            ->find($service->id);

        $this->openNotificationModal([
            'type' => 'success',
            'template' => 'service-delivery-success',
            'title' => 'ثبت موفق تحویل',
            'message' => 'تحویل خدمت با موفقیت ثبت شد.',
            'buttons' => [[
                'label' => 'متوجه شدم',
                'action' => 'close',
                'variant' => 'success',
            ]],
            'meta' => [
                'service_name' => $freshService?->serviceName?->name ?? '',
                'code' => $freshService?->code ?? '',
                'remaining_quota' => number_format(
                    $freshService?->remainingAllocationForWorker($this->currentSocialWorkerId()) ?? 0,
                    2
                ),
            ],
        ]);
        $this->recipientEntries = [$this->blankEntry($this->defaultServiceCategoryId())];
        $this->notes = '';
        $this->deliveredAt = $this->defaultDeliveredAt();
    }

    public function getAssignedServicesProperty()
    {
        return Service::query()
            ->with(['serviceName', 'categories', 'workerAllocations'])
            ->whereHas('socialWorkers', function (Builder $query) {
                $query->where('social_workers.id', $this->currentSocialWorkerId())
                    ->where('service_social_worker.allocated_quantity', '>', 0);
            })
            ->whereIn('status', ['approved', 'in_distribution'])
            ->latest()
            ->get();
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with(['serviceName', 'categories', 'workerAllocations'])
            ->whereHas('socialWorkers', function (Builder $query) {
                $query->where('social_workers.id', $this->currentSocialWorkerId())
                    ->where('service_social_worker.allocated_quantity', '>', 0);
            })
            ->whereIn('status', ['approved', 'in_distribution'])
            ->find($this->selectedServiceId);
    }

    public function getCurrentAllocationProperty(): float
    {
        $service = $this->selectedService;

        return $service ? $service->allocatedQuantityForWorker($this->currentSocialWorkerId()) : 0;
    }

    public function getCurrentDeliveredProperty(): float
    {
        $service = $this->selectedService;

        return $service ? $service->deliveredQuantityForWorker($this->currentSocialWorkerId()) : 0;
    }

    public function getCurrentRemainingAllocationProperty(): float
    {
        return $this->remainingAllocationForCurrentWorker();
    }

    public function getAssignableCategoriesProperty()
    {
        $service = $this->selectedService;

        if (! $service) {
            return collect();
        }

        return $service->categories
            ->filter(fn ($category) => $service->allocatedQuantityForWorkerCategory($this->currentSocialWorkerId(), (int) $category->id) > 0)
            ->sortBy('sort_id')
            ->values();
    }

    public function getSelectedServiceTypeLabelProperty(): string
    {
        return $this->quotaState['service_type'] !== ''
            ? $this->quotaState['service_type']
            : $this->serviceTypeLabel($this->selectedService?->service_type);
    }

    public function render()
    {
        return view('livewire.social-workers.dashboard');
    }

    protected function showValidationErrorModal(array $errors): void
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
            'message' => "لطفاً موارد زیر را بررسی کنید:
• " . implode("
• ", $messages),
            'buttons' => [[
                'label' => 'متوجه شدم',
                'action' => 'close',
                'variant' => 'danger',
            ]],
        ]);
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
            'recipientEntries.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'recipientEntries.*.service_category_id' => [
                'required',
                'integer',
                Rule::in($this->assignableCategories->pluck('id')->all()),
            ],
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
            'deliveredAt' => 'تاریخ تحویل',
            'notes' => 'یادداشت',
        ];
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
        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }

    protected function remainingAllocationForCurrentWorker(): float
    {
        $service = $this->selectedService;

        return $service ? $service->remainingAllocationForWorker($this->currentSocialWorkerId()) : 0;
    }

    protected function syncQuotaState(): void
    {
        $this->quotaState = [
            'service_type' => $this->serviceTypeLabel($this->selectedService?->service_type),
        ];
    }

    protected function defaultServiceCategoryId(): ?int
    {
        $service = $this->selectedService;

        if (! $service) {
            return null;
        }

        return $this->assignableCategories
            ->first(fn ($category) => $service->remainingStockForCategory((int) $category->id) > 0)?->id
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
        $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: '-';
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

    public function getRecipientSuggestionsProperty(): array
    {
        $suggestions = [];

        foreach ($this->recipientEntries as $index => $entry) {
            if ($this->activeRecipientSearchIndex !== $index || ! $this->selectedService) {
                $suggestions[$index] = collect();
                continue;
            }

            $query = trim((string) ($entry['national_id'] ?? ''));

            if (mb_strlen($query) < 2) {
                $suggestions[$index] = collect();
                continue;
            }

            $suggestions[$index] = $this->selectedService->service_type === 'family'
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
                $guardianQuery->where('first_name', 'like', $query . '%')
                    ->orWhere('last_name', 'like', $query . '%')
                    ->orWhere('national_code', 'like', $query . '%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query . '%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%' . $query . '%']);
            })
            ->orderByRaw(
                "CASE
                    WHEN first_name LIKE ? THEN 1
                    WHEN last_name LIKE ? THEN 2
                    WHEN national_code LIKE ? THEN 3
                    WHEN CONCAT_WS(' ', first_name, last_name) LIKE ? THEN 4
                    ELSE 5
                END",
                [$query . '%', $query . '%', $query . '%', $query . '%']
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
                $personQuery->where('first_name', 'like', $query . '%')
                    ->orWhere('last_name', 'like', $query . '%')
                    ->orWhere('national_id', 'like', $query . '%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query . '%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%' . $query . '%']);
            })
            ->orderByRaw(
                "CASE
                    WHEN first_name LIKE ? THEN 1
                    WHEN last_name LIKE ? THEN 2
                    WHEN national_id LIKE ? THEN 3
                    WHEN CONCAT_WS(' ', first_name, last_name) LIKE ? THEN 4
                    ELSE 5
                END",
                [$query . '%', $query . '%', $query . '%', $query . '%']
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

    protected function extractQrToken(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH);
            $segments = array_values(array_filter(explode('/', (string) $path)));

            return (string) end($segments);
        }

        return $value;
    }
}
