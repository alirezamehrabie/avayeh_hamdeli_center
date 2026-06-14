<?php

namespace App\Livewire\Services;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\District;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ServiceDefinition extends Component
{
    public ?int $serviceId = null;

    public ?int $editingServiceId = null;

    public ?int $selectedServiceNameId = null;

    public string $serviceName = '';

    public string $serviceType = 'individual';

    public string $description = '';

    public ?int $serviceDistrictId = null;

    public ?string $distributionStartDate = null;

    public ?string $distributionEndDate = null;

    public string $priority = 'normal';

    public string $status = 'in_distribution';

    public string $statusNotes = '';

    /**
     * @var array<int, array{id: ?int, code: string, name: string, quantity: string, unit: string, value: string}>
     */
    public array $categories = [];

    public function mount(?int $serviceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->serviceId = $serviceId;
        $this->bootDefaults();

        if ($serviceId) {
            $this->editService($serviceId);
        }
    }

    public function addCategory(): void
    {
        $this->categories[] = $this->emptyCategory();
    }

    public function removeCategory(int $index): void
    {
        unset($this->categories[$index]);
        $this->categories = array_values($this->categories);

        if ($this->categories === []) {
            $this->categories[] = $this->emptyCategory();
        }
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $validated['categories'] = collect($validated['categories'])
            ->map(function (array $category): array {
                $category['value'] = $this->digitsOnly((string) ($category['value'] ?? ''));

                return $category;
            })
            ->all();

        if ($this->hasDuplicateCategoryNames($validated['categories'])) {
            $this->addError('categories', 'هر دسته خدمت برای یک خدمت فقط یک‌بار قابل انتخاب است.');

            return;
        }

        DB::transaction(function () use ($validated): void {
            $service = $this->editingServiceId
                ? Service::query()->findOrFail($this->editingServiceId)
                : new Service;
            $serviceName = $this->persistServiceNameRecord(trim($validated['serviceName']));

            if (! $this->editingServiceId) {
                $service->code = Service::generateNextCode();
                $service->created_by = auth()->id();
            }

            $service->fill([
                'service_name_id' => $serviceName->id,
                'name' => trim($validated['serviceName']),
                'service_type' => $validated['serviceType'],
                'description' => $validated['description'] ?: null,
                'district_id' => $validated['serviceDistrictId'] ?: null,
                'distribution_start_date' => $this->jalaliToGregorian($validated['distributionStartDate']),
                'distribution_end_date' => blank($validated['distributionEndDate'])
                    ? null
                    : $this->jalaliToGregorian($validated['distributionEndDate']),
                'priority' => $validated['priority'] ?: null,
                'status' => $validated['status'],
                'status_notes' => $validated['statusNotes'] ?: null,
                'total_quantity' => collect($validated['categories'])->sum(fn (array $category) => (float) $category['quantity']),
                'total_service_value' => 0,
                'quantity_delivered' => $this->editingServiceId ? (float) $service->quantity_delivered : 0,
            ]);
            $service->save();

            $service->categories()->withTrashed()->whereNotNull('deleted_at')->restore();

            $existingCategoryIds = collect($validated['categories'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($this->editingServiceId) {
                $service->categories()->whereNotIn('id', $existingCategoryIds)->delete();
            }

            foreach ($validated['categories'] as $index => $categoryData) {
                $payload = [
                    'service_name_id' => $service->service_name_id,
                    'service_id' => $service->id,
                    'name' => trim($categoryData['name']),
                    'quantity' => (float) $categoryData['quantity'],
                    'unit' => trim($categoryData['unit']),
                    'value' => (int) $categoryData['value'],
                    'created_by' => $this->editingServiceId
                        ? (int) (ServiceCategory::query()->whereKey($categoryData['id'] ?? null)->value('created_by') ?? auth()->id())
                        : auth()->id(),
                ];

                $category = ! empty($categoryData['id'])
                    ? ServiceCategory::query()->where('service_id', $service->id)->findOrFail((int) $categoryData['id'])
                    : new ServiceCategory;

                if (blank($category->code)) {
                    $category->code = $this->editingServiceId
                        ? $this->generateCategoryCode($service, $index, $existingCategoryIds->count())
                        : $this->generateCategoryCode($service, $index, 0);
                }

                if (blank($category->sort_id)) {
                    $category->sort_id = $this->editingServiceId
                        ? $this->nextCategorySortId($service->id)
                        : ($index + 1);
                }

                $category->fill($payload);
                $category->save();
            }

            $service->refreshFinancialTotals();
        });

        session()->flash('success', $this->editingServiceId
            ? 'خدمت با موفقیت به‌روزرسانی شد.'
            : 'خدمت جدید با موفقیت ثبت شد.');

        $this->resetForm();
        $this->bootDefaults();
    }

    public function editService(int $serviceId): void
    {
        $service = Service::query()
            ->with('categories')
            ->findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->selectedServiceNameId = $service->service_name_id;
        $this->serviceName = $service->name;
        $this->serviceType = $service->service_type;
        $this->description = (string) $service->description;
        $this->serviceDistrictId = $service->district_id;
        $this->distributionStartDate = $service->distribution_start_date
            ? Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d')
            : null;
        $this->distributionEndDate = $service->distribution_end_date
            ? Jalalian::fromDateTime($service->distribution_end_date)->format('Y/m/d')
            : null;
        $this->priority = (string) $service->priority;
        $this->status = (string) $service->status;
        $this->statusNotes = (string) $service->status_notes;
        $this->categories = $service->categories
            ->sortBy('id')
            ->map(fn (ServiceCategory $category) => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'quantity' => $this->formatDecimal($category->quantity),
                'unit' => (string) $category->unit,
                'value' => (string) $category->value,
            ])
            ->values()
            ->all();
    }

    public function startNewService(): void
    {
        $this->resetForm();
        $this->bootDefaults();
        $this->resetValidation();
    }

    public function getPreviewServiceCodeProperty(): string
    {
        return $this->editingServiceId
            ? (string) Service::query()->whereKey($this->editingServiceId)->value('code')
            : Service::generateNextCode();
    }

    public function getTotalServiceValueProperty(): int
    {
        return $this->calculateTotalServiceValue();
    }

    public function getTotalQuantityProperty(): float
    {
        return collect($this->categories)->sum(fn (array $category) => (float) ($category['quantity'] ?? 0));
    }

    public function render()
    {
        return view('livewire.services.service-definition', [
            'districts' => District::query()->orderBy('sort_order')->orderBy('name')->get(),
            'serviceNames' => ServiceName::query()->ordered()->get(['id', 'name']),
            'categoryTemplates' => ServiceCategoryTemplate::query()
                ->ordered()
                ->get(['id', 'service_name_id', 'name']),
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'statusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'serviceName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->ignore($this->editingServiceId),
            ],
            'selectedServiceNameId' => ['nullable', 'integer', 'exists:service_names,id'],
            'serviceType' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'description' => ['nullable', 'string', 'max:5000'],
            'serviceDistrictId' => ['nullable', 'integer', 'exists:districts,id'],
            'distributionStartDate' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ شروع توزیع شمسی معتبر نیست.');
                    }
                },
            ],
            'distributionEndDate' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ پایان توزیع شمسی معتبر نیست.');

                        return;
                    }

                    if (
                        filled($this->distributionStartDate)
                        && $this->isValidJalaliDate((string) $this->distributionStartDate)
                        && Jalalian::fromFormat('Y/m/d', trim((string) $value))
                            ->lessThan(Jalalian::fromFormat('Y/m/d', trim((string) $this->distributionStartDate)))
                    ) {
                        $fail('تاریخ پایان توزیع باید برابر یا بعد از تاریخ شروع توزیع باشد.');
                    }
                },
            ],
            'priority' => ['nullable', Rule::in(array_keys(Service::PRIORITY_OPTIONS))],
            'status' => ['required', Rule::in(array_keys(Service::STATUS_OPTIONS))],
            'statusNotes' => ['nullable', 'string', 'max:5000'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'categories.*.unit' => ['required', Rule::in(Service::unitKeys())],
            'categories.*.value' => [
                'required',
                'regex:/^[\d,\s]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->digitsOnly((string) $value) === '') {
                        $fail('ارزش واحد معتبر نیست.');
                    }
                },
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'serviceName' => 'نام خدمت',
            'serviceType' => 'نوع خدمت',
            'description' => 'توضیحات خدمت',
            'serviceDistrictId' => 'منطقه خدمت',
            'distributionStartDate' => 'تاریخ شروع توزیع شمسی',
            'distributionEndDate' => 'تاریخ پایان توزیع شمسی',
            'priority' => 'اولویت',
            'status' => 'وضعیت خدمت',
            'statusNotes' => 'یادداشت وضعیت',
            'categories' => 'دسته‌ها',
            'categories.*.name' => 'نام دسته',
            'categories.*.quantity' => 'تعداد',
            'categories.*.unit' => 'واحد',
            'categories.*.value' => 'ارزش واحد',
        ];
    }

    protected function bootDefaults(): void
    {
        if ($this->categories === []) {
            $this->categories[] = $this->emptyCategory();
        }

        if (blank($this->distributionStartDate)) {
            $this->distributionStartDate = Jalalian::now()->format('Y/m/d');
        }

        $this->serviceType = $this->serviceType ?: 'individual';
        $this->status = $this->status ?: 'draft';
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingServiceId',
            'selectedServiceNameId',
            'serviceName',
            'serviceType',
            'description',
            'serviceDistrictId',
            'distributionStartDate',
            'distributionEndDate',
            'priority',
            'status',
            'statusNotes',
            'categories',
        ]);

        $this->serviceType = 'individual';
        $this->status = 'draft';
        $this->categories = [$this->emptyCategory()];
    }

    protected function emptyCategory(): array
    {
        return [
            'id' => null,
            'code' => '',
            'name' => '',
            'quantity' => '',
            'unit' => array_key_first(Service::unitOptions()) ?: 'package',
            'value' => '',
        ];
    }

    protected function calculateTotalServiceValue(): int
    {
        return (int) collect($this->categories)->sum(function (array $category): float {
            return (float) ($category['quantity'] ?? 0) * (float) $this->digitsOnly((string) ($category['value'] ?? 0));
        });
    }

    protected function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    protected function hasDuplicateCategoryNames(array $categories): bool
    {
        $names = collect($categories)
            ->map(fn (array $category) => mb_strtolower(trim((string) ($category['name'] ?? ''))))
            ->filter()
            ->values();

        return $names->unique()->count() !== $names->count();
    }

    protected function persistServiceNameRecord(string $serviceNameInput): ServiceName
    {
        $name = trim($serviceNameInput);

        if ($this->selectedServiceNameId) {
            $serviceName = ServiceName::query()->find($this->selectedServiceNameId);

            if ($serviceName && $serviceName->name === $name) {
                return $serviceName;
            }
        }

        return ServiceName::query()->firstOrCreate(
            ['name' => $name],
            [
                'sort_id' => $this->nextServiceNameSortId(),
                'created_by' => auth()->id(),
            ]
        );
    }

    protected function generateCategoryCode(Service $service, int $index, int $existingCount): string
    {
        $suffix = $existingCount + $index + 1;

        return $service->code.'-CAT'.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
    }

    protected function nextServiceNameSortId(): int
    {
        return ((int) ServiceName::query()->max('sort_id')) + 1;
    }

    protected function nextCategorySortId(int $serviceId): int
    {
        return ((int) ServiceCategory::query()
            ->where('service_id', $serviceId)
            ->max('sort_id')) + 1;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : number_format($number, 2, '.', '');
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
}
