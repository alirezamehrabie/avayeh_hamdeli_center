<?php

namespace App\Livewire\Activities;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\District;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Services\ActivityServiceDeliverySyncService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ActivityDefinition extends Component
{
    public ?int $activityId = null;

    public ?int $editingActivityId = null;

    public string $name = '';

    public string $activityType = 'ceremony';

    public string $description = '';

    public string $location = '';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public string $capacity = '';

    public string $status = 'ongoing';

    public string $statusNotes = '';

    public string $previewActivityCode = '';

    public string $activeTab = 'activity';

    public string $lastActivityServiceDefaultName = '';

    /**
     * @var array<int, array{
     *     id: ?int,
     *     selectedServiceNameId: ?int,
     *     serviceName: string,
     *     serviceType: string,
     *     description: string,
     *     serviceDistrictId: ?int,
     *     distributionStartDate: ?string,
     *     distributionEndDate: ?string,
     *     priority: string,
     *     status: string,
     *     statusNotes: string,
     *     categories: array<int, array{id: ?int, code: string, name: string, quantity: string, unit: string, value: string}>
     * }>
     */
    public array $activityServices = [];

    public function mount(?int $activityId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->activityId = $activityId;
        $this->bootDefaults();
        $this->lastActivityServiceDefaultName = $this->defaultActivityServiceName();
        $this->syncPreviewActivityCode();

        if ($activityId) {
            $this->editActivity($activityId);
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->normalizeInput();

        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $exception) {
            $this->dispatch('activity-save-failed');

            throw $exception;
        }

        $startsAt = blank($validated['startsAt']) ? null : $this->jalaliDateTimeToGregorian($validated['startsAt']);
        $endsAt = blank($validated['endsAt']) ? null : $this->jalaliDateTimeToGregorian($validated['endsAt']);

        $payload = [
            'name' => trim($validated['name']),
            'activity_type' => $validated['activityType'],
            'description' => blank($validated['description']) ? null : trim($validated['description']),
            'location' => blank($validated['location']) ? null : trim($validated['location']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => blank($validated['capacity']) ? null : (int) $validated['capacity'],
            'status' => $validated['status'],
            'status_notes' => blank($validated['statusNotes']) ? null : trim($validated['statusNotes']),
        ];

        if ($this->editingActivityId) {
            DB::transaction(function () use ($payload, $validated): void {
                $activity = Activity::query()->findOrFail($this->editingActivityId);
                $activity->fill($payload);
                $activity->save();

                $this->syncActivityServices($activity, $validated['activityServices']);
                app(ActivityServiceDeliverySyncService::class)->syncActivity($activity);
            });
        } else {
            $this->saveNewActivity($payload, $validated['activityServices']);
        }

        session()->flash('activity-success', __('activities.definition.messages.saved'));

        return redirect()->to('/admin/dashboard?section=activity-list');
    }

    public function editActivity(int $activityId): void
    {
        $activity = Activity::query()
            ->with(['services.categories'])
            ->findOrFail($activityId);

        $this->editingActivityId = $activity->id;
        $this->name = $activity->name;
        $this->activityType = (string) $activity->activity_type;
        $this->description = (string) $activity->description;
        $this->location = (string) $activity->location;
        $this->startsAt = $activity->starts_at ? Jalalian::fromDateTime($activity->starts_at)->format('Y/m/d H:i') : null;
        $this->endsAt = $activity->ends_at ? Jalalian::fromDateTime($activity->ends_at)->format('Y/m/d H:i') : null;
        $this->capacity = $activity->capacity ? (string) $activity->capacity : '';
        $this->status = (string) $activity->status;
        $this->statusNotes = (string) $activity->status_notes;
        $this->previewActivityCode = (string) $activity->code;
        $this->activityServices = $activity->services
            ->sortBy('id')
            ->map(fn (Service $service): array => $this->activityServiceFromModel($service))
            ->values()
            ->all();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['activity', 'services'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function addActivityService(): void
    {
        $this->activityServices[] = $this->emptyActivityService();
        $this->activeTab = 'services';
    }

    public function updatedName(): void
    {
        $previousDefault = $this->lastActivityServiceDefaultName;
        $nextDefault = $this->defaultActivityServiceName();

        foreach ($this->activityServices as $index => $service) {
            $serviceName = trim((string) ($service['serviceName'] ?? ''));

            if ($serviceName === '' || $serviceName === $previousDefault) {
                $this->activityServices[$index]['serviceName'] = $nextDefault;
                $this->activityServices[$index]['selectedServiceNameId'] = null;
            }
        }

        $this->lastActivityServiceDefaultName = $nextDefault;
    }

    public function removeActivityService(int $index): void
    {
        unset($this->activityServices[$index]);
        $this->activityServices = array_values($this->activityServices);
    }

    public function addActivityServiceCategory(int $serviceIndex): void
    {
        if (! array_key_exists($serviceIndex, $this->activityServices)) {
            return;
        }

        $this->activityServices[$serviceIndex]['categories'][] = $this->emptyActivityServiceCategory();
        $this->dispatch('activity-category-added', serviceIndex: $serviceIndex);
    }

    public function removeActivityServiceCategory(int $serviceIndex, int $categoryIndex): void
    {
        if (! array_key_exists($serviceIndex, $this->activityServices)) {
            return;
        }

        unset($this->activityServices[$serviceIndex]['categories'][$categoryIndex]);
        $this->activityServices[$serviceIndex]['categories'] = array_values($this->activityServices[$serviceIndex]['categories']);

        if ($this->activityServices[$serviceIndex]['categories'] === []) {
            $this->activityServices[$serviceIndex]['categories'][] = $this->emptyActivityServiceCategory();
        }
    }

    public function backToList(): void
    {
        $this->dispatch('open-dashboard-section', section: 'activity-list');
    }

    public function render()
    {
        return view('livewire.activities.activity-definition', [
            'typeOptions' => Activity::TYPE_OPTIONS,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'serviceNames' => ServiceName::query()->ordered()->get(['id', 'name']),
            'districts' => District::query()->orderBy('sort_order')->orderBy('name')->get(),
            'serviceTypeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'serviceStatusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'activityType' => ['required', Rule::in(array_keys(Activity::TYPE_OPTIONS))],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'startsAt' => ['nullable', 'string', $this->jalaliDateTimeRule('startsAt')],
            'endsAt' => ['nullable', 'string', $this->jalaliDateTimeRule('endsAt'), function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (blank($this->startsAt)) {
                    $fail(__('activities.definition.validation.messages.end_requires_start'));

                    return;
                }

                if (! $this->isValidJalaliDateTime((string) $value) || ! $this->isValidJalaliDateTime((string) $this->startsAt)) {
                    return;
                }

                if (! $this->jalaliDateTimeToGregorian((string) $value)
                    ->greaterThan($this->jalaliDateTimeToGregorian((string) $this->startsAt))) {
                    $fail(__('activities.definition.validation.messages.end_after_start'));
                }
            }],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_keys(Activity::STATUS_OPTIONS))],
            'statusNotes' => ['nullable', 'string', 'max:5000'],
            'activityServices' => ['array'],
            'activityServices.*.id' => ['nullable', 'integer', 'exists:services,id'],
            'activityServices.*.selectedServiceNameId' => ['nullable', 'integer', 'exists:service_names,id'],
            'activityServices.*.serviceName' => ['required', 'string', 'max:255'],
            'activityServices.*.serviceType' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'activityServices.*.description' => ['nullable', 'string', 'max:5000'],
            'activityServices.*.serviceDistrictId' => ['nullable', 'integer', 'exists:districts,id'],
            'activityServices.*.distributionStartDate' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ شروع توزیع خدمت معتبر نیست.');
                    }
                },
            ],
            'activityServices.*.distributionEndDate' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ پایان توزیع خدمت معتبر نیست.');
                    }
                },
            ],
            'activityServices.*.priority' => ['nullable', Rule::in(array_keys(Service::PRIORITY_OPTIONS))],
            'activityServices.*.status' => ['required', Rule::in(array_keys(Service::STATUS_OPTIONS))],
            'activityServices.*.statusNotes' => ['nullable', 'string', 'max:5000'],
            'activityServices.*.categories' => ['required', 'array', 'min:1'],
            'activityServices.*.categories.*.id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'activityServices.*.categories.*.name' => ['required', 'string', 'max:255'],
            'activityServices.*.categories.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'activityServices.*.categories.*.unit' => ['required', Rule::in(Service::unitKeys())],
            'activityServices.*.categories.*.value' => [
                'nullable',
                'regex:/^[\d,\s]+$/',
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => __('activities.definition.validation.attributes.name'),
            'activityType' => __('activities.definition.validation.attributes.activityType'),
            'description' => __('activities.definition.validation.attributes.description'),
            'location' => __('activities.definition.validation.attributes.location'),
            'startsAt' => __('activities.definition.validation.attributes.startsAt'),
            'endsAt' => __('activities.definition.validation.attributes.endsAt'),
            'capacity' => __('activities.definition.validation.attributes.capacity'),
            'statusNotes' => __('activities.definition.validation.attributes.statusNotes'),
            'activityServices.*.serviceName' => 'نام خدمت',
            'activityServices.*.serviceType' => 'نوع خدمت',
            'activityServices.*.distributionStartDate' => 'تاریخ شروع توزیع خدمت',
            'activityServices.*.distributionEndDate' => 'تاریخ پایان توزیع خدمت',
            'activityServices.*.categories' => 'دسته‌های خدمت',
            'activityServices.*.categories.*.name' => 'نام دسته خدمت',
            'activityServices.*.categories.*.quantity' => 'تعداد دسته خدمت',
            'activityServices.*.categories.*.unit' => 'واحد دسته خدمت',
            'activityServices.*.categories.*.value' => 'ارزش واحد دسته خدمت',
        ];
    }

    protected function bootDefaults(): void
    {
        $this->activityType = $this->activityType ?: 'ceremony';
        $this->status = $this->status ?: 'ongoing';
    }

    protected function syncPreviewActivityCode(): void
    {
        if ($this->editingActivityId) {
            return;
        }

        $this->previewActivityCode = Activity::generateNextCode();
    }

    protected function normalizeInput(): void
    {
        $this->name = trim($this->name);
        $this->activityType = trim($this->activityType) ?: 'ceremony';
        $this->description = trim($this->description);
        $this->location = trim($this->location);
        $this->startsAt = $this->normalizeJalaliDateTimeInput($this->startsAt);
        $this->endsAt = $this->normalizeJalaliDateTimeInput($this->endsAt);
        $this->capacity = trim($this->capacity);
        $this->status = trim($this->status) ?: 'ongoing';
        $this->statusNotes = trim($this->statusNotes);
        $this->normalizeActivityServicesInput();
    }

    protected function jalaliDateTimeRule(string $attributeKey): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($attributeKey): void {
            if (blank($value)) {
                return;
            }

            if (! $this->isValidJalaliDateTime((string) $value)) {
                $fail(__('activities.definition.validation.messages.invalid_jalali_datetime', [
                    'attribute' => __('activities.definition.validation.attributes.'.$attributeKey),
                ]));
            }
        };
    }

    protected function isValidJalaliDateTime(string $value): bool
    {
        [$date, $time] = $this->splitJalaliDateTime($value);

        if ($date === null) {
            return false;
        }

        if (! $this->hasStrictJalaliDateTimeFormat($date, $time)) {
            return false;
        }

        $dateParts = explode('/', $date);

        if (count($dateParts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $dateParts);

        if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
            return false;
        }

        if ($time === null) {
            return true;
        }

        $timeParts = explode(':', $time);

        if (count($timeParts) !== 2) {
            return false;
        }

        [$hour, $minute] = array_map('intval', $timeParts);

        return $hour >= 0 && $hour <= 23
            && $minute >= 0 && $minute <= 59;
    }

    protected function hasStrictJalaliDateTimeFormat(string $date, ?string $time): bool
    {
        if (! preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) {
            return false;
        }

        return $time === null || (bool) preg_match('/^\d{2}:\d{2}$/', $time);
    }

    protected function splitJalaliDateTime(?string $value): array
    {
        $normalized = $this->normalizeJalaliDateTimeInput($value);

        if ($normalized === null) {
            return [null, null];
        }

        $parts = explode(' ', $normalized, 2);

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    protected function jalaliDateTimeToGregorian(string $value): Carbon
    {
        [$date, $time] = $this->splitJalaliDateTime($value);

        $normalized = $time === null ? (string) $date : trim($date.' '.$time);
        $format = $time === null ? 'Y/m/d' : 'Y/m/d H:i';
        $timezone = new \DateTimeZone((string) config('app.timezone'));
        $carbon = Jalalian::fromFormat($format, $normalized, $timezone)
            ->toCarbon()
            ->setTimezone($timezone);

        return Carbon::instance($carbon);
    }

    protected function normalizeJalaliDateTimeInput(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = CalendarUtils::convertNumbers((string) $value, true);
        $normalized = str_replace(["\u{200c}", "\u{200f}", "\u{00a0}"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    protected function saveNewActivity(array $payload, array $services): void
    {
        $attempts = 0;

        while (true) {
            try {
                DB::transaction(function () use ($payload, $services): void {
                    $activity = new Activity;
                    $activity->created_by = auth()->id();
                    $activity->fill($payload);
                    $activity->save();

                    $this->syncActivityServices($activity, $services);
                    app(ActivityServiceDeliverySyncService::class)->syncActivity($activity);
                });

                return;
            } catch (QueryException $exception) {
                if (! $this->isDuplicateActivityCodeException($exception) || $attempts >= 2) {
                    throw $exception;
                }

                $attempts++;
            }
        }
    }

    protected function isDuplicateActivityCodeException(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'activities.code')
            || str_contains($message, 'activities_code_unique')
            || str_contains($message, 'UNIQUE constraint failed: activities.code');
    }

    protected function normalizeActivityServicesInput(): void
    {
        $this->activityServices = collect($this->activityServices)
            ->map(function (array $service): array {
                $service['id'] = filled($service['id'] ?? null) ? (int) $service['id'] : null;
                $service['selectedServiceNameId'] = filled($service['selectedServiceNameId'] ?? null)
                    ? (int) $service['selectedServiceNameId']
                    : null;
                $service['serviceName'] = trim((string) ($service['serviceName'] ?? ''));
                $service['serviceType'] = trim((string) ($service['serviceType'] ?? 'individual')) ?: 'individual';
                $service['description'] = trim((string) ($service['description'] ?? ''));
                $service['serviceDistrictId'] = filled($service['serviceDistrictId'] ?? null) ? (int) $service['serviceDistrictId'] : null;
                $service['distributionStartDate'] = $this->normalizeJalaliDateInput($service['distributionStartDate'] ?? null)
                    ?? $this->defaultServiceStartDate();
                $service['distributionEndDate'] = $this->normalizeJalaliDateInput($service['distributionEndDate'] ?? null);
                $service['priority'] = trim((string) ($service['priority'] ?? 'normal')) ?: 'normal';
                $service['status'] = trim((string) ($service['status'] ?? 'in_distribution')) ?: 'in_distribution';
                $service['statusNotes'] = trim((string) ($service['statusNotes'] ?? ''));
                $service['categories'] = collect($service['categories'] ?? [$this->emptyActivityServiceCategory()])
                    ->map(fn (array $category): array => [
                        'id' => filled($category['id'] ?? null) ? (int) $category['id'] : null,
                        'code' => (string) ($category['code'] ?? ''),
                        'name' => trim((string) ($category['name'] ?? '')),
                        'quantity' => trim((string) ($category['quantity'] ?? '')),
                        'unit' => trim((string) ($category['unit'] ?? (array_key_first(Service::unitOptions()) ?: 'count'))),
                        'value' => trim((string) ($category['value'] ?? '')),
                    ])
                    ->values()
                    ->all();

                return $service;
            })
            ->values()
            ->all();
    }

    protected function syncActivityServices(Activity $activity, array $services): void
    {
        $serviceIds = collect($services)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($this->editingActivityId) {
            $activity->services()
                ->whereNotIn('id', $serviceIds)
                ->get()
                ->each(function (Service $service): void {
                    $service->categories()->delete();
                    $service->delete();
                });
        }

        foreach ($services as $serviceData) {
            if ($this->hasDuplicateCategoryNames($serviceData['categories'])) {
                throw ValidationException::withMessages([
                    'activityServices' => 'هر دسته خدمت برای یک خدمت فقط یک‌بار قابل ثبت است.',
                ]);
            }

            $service = ! empty($serviceData['id'])
                ? $activity->services()->findOrFail((int) $serviceData['id'])
                : new Service;
            $serviceName = $this->persistServiceNameRecord(
                trim($serviceData['serviceName']),
                filled($serviceData['selectedServiceNameId'] ?? null) ? (int) $serviceData['selectedServiceNameId'] : null
            );

            if (! $service->exists) {
                $service->code = Service::generateNextCode();
                $service->created_by = auth()->id();
            }

            $service->fill([
                'activity_id' => $activity->id,
                'service_name_id' => $serviceName->id,
                'name' => trim($serviceData['serviceName']),
                'service_type' => $serviceData['serviceType'],
                'supports_gate_delivery' => false,
                'supports_home_delivery' => false,
                'supports_activity_delivery' => true,
                'description' => blank($serviceData['description']) ? null : trim($serviceData['description']),
                'district_id' => $serviceData['serviceDistrictId'] ?: null,
                'distribution_start_date' => $this->jalaliDateToGregorian($serviceData['distributionStartDate']),
                'distribution_end_date' => blank($serviceData['distributionEndDate'])
                    ? null
                    : $this->jalaliDateToGregorian($serviceData['distributionEndDate']),
                'priority' => $serviceData['priority'] ?: null,
                'status' => $serviceData['status'],
                'status_notes' => blank($serviceData['statusNotes']) ? null : trim($serviceData['statusNotes']),
                'total_quantity' => collect($serviceData['categories'])->sum(fn (array $category) => (float) $category['quantity']),
                'total_service_value' => 0,
                'quantity_delivered' => $service->exists ? (float) $service->quantity_delivered : 0,
            ]);
            $service->save();

            $this->syncActivityServiceCategories($service, $serviceData['categories']);
            $service->refreshFinancialTotals();
        }
    }

    protected function syncActivityServiceCategories(Service $service, array $categories): void
    {
        $existingCategoryIds = collect($categories)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $service->categories()->withTrashed()->whereNotNull('deleted_at')->restore();
        $service->categories()->whereNotIn('id', $existingCategoryIds)->delete();

        foreach ($categories as $index => $categoryData) {
            $category = ! empty($categoryData['id'])
                ? $service->categories()->findOrFail((int) $categoryData['id'])
                : new ServiceCategory;

            if (blank($category->code)) {
                $category->code = ServiceCategory::generateNextCode((int) $service->id);
            }

            if (blank($category->sort_id)) {
                $category->sort_id = $index + 1;
            }

            $categoryName = trim($categoryData['name']);
            $category->fill([
                'service_name_id' => $service->service_name_id,
                'service_id' => $service->id,
                'name' => $categoryName,
                'quantity' => (float) $categoryData['quantity'],
                'unit' => trim($categoryData['unit']),
                'value' => $this->nullableDigitsToInt((string) ($categoryData['value'] ?? '')),
                'created_by' => $category->exists
                    ? (int) ($category->created_by ?: auth()->id())
                    : auth()->id(),
            ]);
            $category->save();

            $this->persistCategoryTemplateRecord($service->service_name_id, $categoryName, $index + 1);
        }
    }

    protected function emptyActivityService(): array
    {
        return [
            'id' => null,
            'selectedServiceNameId' => null,
            'serviceName' => $this->defaultActivityServiceName(),
            'serviceType' => 'individual',
            'description' => '',
            'serviceDistrictId' => null,
            'distributionStartDate' => $this->defaultServiceStartDate(),
            'distributionEndDate' => $this->defaultServiceEndDate(),
            'priority' => 'normal',
            'status' => 'in_distribution',
            'statusNotes' => '',
            'categories' => [$this->emptyActivityServiceCategory()],
        ];
    }

    protected function emptyActivityServiceCategory(): array
    {
        return [
            'id' => null,
            'code' => '',
            'name' => '',
            'quantity' => '',
            'unit' => array_key_first(Service::unitOptions()) ?: 'count',
            'value' => '',
        ];
    }

    protected function activityServiceFromModel(Service $service): array
    {
        return [
            'id' => $service->id,
            'selectedServiceNameId' => $service->service_name_id,
            'serviceName' => $service->name,
            'serviceType' => (string) $service->service_type,
            'description' => (string) $service->description,
            'serviceDistrictId' => $service->district_id,
            'distributionStartDate' => $service->distribution_start_date
                ? Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d')
                : $this->defaultServiceStartDate(),
            'distributionEndDate' => $service->distribution_end_date
                ? Jalalian::fromDateTime($service->distribution_end_date)->format('Y/m/d')
                : null,
            'priority' => (string) ($service->priority ?: 'normal'),
            'status' => (string) ($service->status ?: 'in_distribution'),
            'statusNotes' => (string) $service->status_notes,
            'categories' => $service->categories
                ->sortBy('id')
                ->map(fn (ServiceCategory $category): array => [
                    'id' => $category->id,
                    'code' => (string) $category->code,
                    'name' => (string) $category->name,
                    'quantity' => $this->formatDecimal($category->quantity),
                    'unit' => (string) $category->unit,
                    'value' => (string) $category->value,
                ])
                ->values()
                ->all() ?: [$this->emptyActivityServiceCategory()],
        ];
    }

    protected function defaultServiceStartDate(): string
    {
        if ($this->startsAt && $this->isValidJalaliDateTime($this->startsAt)) {
            [$date] = $this->splitJalaliDateTime($this->startsAt);

            return (string) $date;
        }

        return Jalalian::now()->format('Y/m/d');
    }

    protected function defaultActivityServiceName(): string
    {
        $activityName = trim($this->name);

        return $activityName !== ''
            ? 'خدمت '.$activityName
            : 'خدمت - '.$this->currentJalaliDateLabel();
    }

    protected function currentJalaliDateLabel(): string
    {
        $date = Jalalian::fromDateTime(now());
        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        return $date->getDay().' '.($months[$date->getMonth()] ?? '').' '.$date->getYear();
    }

    protected function defaultServiceEndDate(): ?string
    {
        if ($this->endsAt && $this->isValidJalaliDateTime($this->endsAt)) {
            [$date] = $this->splitJalaliDateTime($this->endsAt);

            return (string) $date;
        }

        return null;
    }

    protected function normalizeJalaliDateInput(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = CalendarUtils::convertNumbers((string) $value, true);
        $normalized = str_replace(["\u{200c}", "\u{200f}", "\u{00a0}"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        return $normalized !== '' ? $normalized : null;
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

    protected function jalaliDateToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }

    protected function persistServiceNameRecord(string $serviceNameInput, ?int $selectedServiceNameId = null): ServiceName
    {
        $name = trim($serviceNameInput);

        if ($selectedServiceNameId) {
            $serviceName = ServiceName::query()->find($selectedServiceNameId);

            if ($serviceName && $serviceName->name === $name) {
                return $serviceName;
            }
        }

        return ServiceName::query()->firstOrCreate(
            ['name' => $name],
            [
                'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
                'created_by' => auth()->id(),
            ]
        );
    }

    protected function persistCategoryTemplateRecord(int $serviceNameId, string $categoryName, int $fallbackSortId): void
    {
        $name = trim($categoryName);

        if ($name === '') {
            return;
        }

        $existingTemplate = ServiceCategoryTemplate::query()
            ->withTrashed()
            ->where('service_name_id', $serviceNameId)
            ->where('name', $name)
            ->first();

        if ($existingTemplate) {
            if ($existingTemplate->trashed()) {
                $existingTemplate->restore();
            }

            return;
        }

        ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceNameId,
            'name' => $name,
            'sort_id' => ServiceCategoryTemplate::nextSortId($serviceNameId) ?: $fallbackSortId,
            'created_by' => auth()->id(),
        ]);
    }

    protected function hasDuplicateCategoryNames(array $categories): bool
    {
        $names = collect($categories)
            ->map(fn (array $category) => mb_strtolower(trim((string) ($category['name'] ?? ''))))
            ->filter()
            ->values();

        return $names->unique()->count() !== $names->count();
    }

    protected function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    protected function nullableDigitsToInt(string $value): int
    {
        $digits = $this->digitsOnly($value);

        return $digits === '' ? 0 : (int) $digits;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : number_format($number, 2, '.', '');
    }
}
