<?php

namespace App\Livewire\Services;

use App\Models\District;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class ManageServices extends Component
{
    public ?int $serviceId = null;
    public ?int $editingServiceId = null;
    public ?int $selectedServiceNameId = null;
    public ?int $selectedServiceCategoryId = null;
    public string $serviceType = 'individual';
    public string $description = '';
    public string $totalQuantity = '';
    public string $serviceUnit = 'package';
    public string $valuePerUnit = '';
    public ?int $serviceDistrictId = null;
    public string $distributionStartDate = '';
    public string $distributionEndDate = '';
    public string $priority = '';
    public string $status = 'draft';
    public string $statusNotes = '';

    public function mount(?int $serviceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->bootDefaultSelections();

        if ($serviceId) {
            $this->editService($serviceId);
        }
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        $name = $this->resolveServiceName();
        $category = $this->resolveServiceCategory();
        $totalValue = $this->calculateTotalServiceValue();

        DB::transaction(function () use ($validated, $name, $category, $totalValue) {
            $payload = [
                'service_name_id' => $name->id,
                'service_category_id' => $category->id,
                'service_type' => $validated['serviceType'],
                'description' => $validated['description'] ?: null,
                'total_quantity' => $validated['totalQuantity'],
                'service_unit' => $validated['serviceUnit'],
                'value_per_unit' => (int) $validated['valuePerUnit'],
                'total_service_value' => $totalValue,
                'district_id' => $validated['serviceDistrictId'] ?: null,
                'distribution_start_date' => $validated['distributionStartDate'],
                'distribution_end_date' => $validated['distributionEndDate'],
                'priority' => $validated['priority'] ?: null,
                'status' => $validated['status'],
                'status_notes' => $validated['statusNotes'] ?: null,
                'quantity_delivered' => $this->editingServiceId
                    ? Service::query()->findOrFail($this->editingServiceId)->quantity_delivered
                    : 0,
                'created_by' => $this->editingServiceId
                    ? Service::query()->findOrFail($this->editingServiceId)->created_by
                    : auth()->id(),
            ];

            if ($this->editingServiceId) {
                $service = Service::query()->findOrFail($this->editingServiceId);
                $service->update($payload);
            } else {
                $payload['service_code'] = Service::generateNextCode();
                $service = Service::query()->create($payload);
            }
        });

        session()->flash('success', $this->editingServiceId
            ? 'خدمت با موفقیت به‌روزرسانی شد.'
            : 'خدمت جدید با موفقیت ثبت شد.');

        $this->resetForm();
        $this->bootDefaultSelections();
    }

    public function editService(int $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->selectedServiceNameId = $service->service_name_id;
        $this->selectedServiceCategoryId = $service->service_category_id;
        $this->serviceType = $service->service_type;
        $this->description = (string) $service->description;
        $this->totalQuantity = $this->formatDecimal($service->total_quantity);
        $this->serviceUnit = $service->service_unit;
        $this->valuePerUnit = (string) $service->value_per_unit;
        $this->serviceDistrictId = $service->district_id;
        $this->distributionStartDate = optional($service->distribution_start_date)->format('Y-m-d') ?? '';
        $this->distributionEndDate = optional($service->distribution_end_date)->format('Y-m-d') ?? '';
        $this->priority = (string) $service->priority;
        $this->status = $service->status;
        $this->statusNotes = (string) $service->status_notes;
    }

    public function startNewService(): void
    {
        $this->resetForm();
        $this->bootDefaultSelections();
        $this->resetValidation();
    }

    public function updatedSelectedServiceNameId($value): void
    {
        if (! $value) {
            $this->selectedServiceCategoryId = null;

            return;
        }

        $categoryIds = $this->availableServiceCategories()->pluck('id')->map(fn ($id) => (int) $id);

        if (! $categoryIds->contains((int) $this->selectedServiceCategoryId)) {
            $this->selectedServiceCategoryId = $categoryIds->first();
        }
    }

    public function getPreviewServiceCodeProperty(): string
    {
        if ($this->editingServiceId) {
            return (string) Service::query()->whereKey($this->editingServiceId)->value('service_code');
        }

        return Service::generateNextCode();
    }

    public function getTotalServiceValueProperty(): int
    {
        return $this->calculateTotalServiceValue();
    }

    public function getRemainingQuantityProperty(): float
    {
        return max(0, $this->normalizeDecimal($this->totalQuantity) - $this->currentDeliveredQuantity());
    }

    public function getDeliveredQuantityProperty(): float
    {
        return $this->currentDeliveredQuantity();
    }

    public function getProgressPercentageProperty(): float
    {
        $totalQuantity = $this->normalizeDecimal($this->totalQuantity);

        if ($totalQuantity <= 0) {
            return 0;
        }

        return min(100, round(($this->currentDeliveredQuantity() / $totalQuantity) * 100, 2));
    }

    public function render()
    {
        return view('livewire.services.manage-services', [
            'serviceNames' => ServiceName::query()->ordered()->get(),
            'serviceCategories' => $this->availableServiceCategories(),
            'districts' => District::query()->orderBy('sort_order')->orderBy('name')->get(),
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'statusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'selectedServiceNameId' => ['required', 'integer', 'exists:service_names,id'],
            'selectedServiceCategoryId' => [
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where(function ($query) {
                    $query->where('service_name_id', $this->selectedServiceNameId);
                }),
            ],
            'serviceType' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'description' => ['nullable', 'string', 'max:5000'],
            'totalQuantity' => ['required', 'numeric', 'min:0.01'],
            'serviceUnit' => ['required', Rule::in(Service::unitKeys())],
            'valuePerUnit' => ['required', 'integer', 'min:0'],
            'serviceDistrictId' => ['nullable', 'integer', 'exists:districts,id'],
            'distributionStartDate' => ['required', 'date'],
            'distributionEndDate' => ['required', 'date', 'after_or_equal:distributionStartDate'],
            'priority' => ['nullable', Rule::in(array_keys(Service::PRIORITY_OPTIONS))],
            'status' => ['required', Rule::in(array_keys(Service::STATUS_OPTIONS))],
            'statusNotes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedServiceNameId' => 'نام خدمت',
            'selectedServiceCategoryId' => 'دسته‌بندی خدمت',
            'serviceType' => 'نوع خدمت',
            'description' => 'توضیحات خدمت',
            'totalQuantity' => 'تعداد کل',
            'serviceUnit' => 'واحد خدمت',
            'valuePerUnit' => 'ارزش هر واحد',
            'serviceDistrictId' => 'منطقه خدمت',
            'distributionStartDate' => 'تاریخ شروع توزیع',
            'distributionEndDate' => 'تاریخ پایان توزیع',
            'priority' => 'اولویت',
            'status' => 'وضعیت خدمت',
            'statusNotes' => 'یادداشت وضعیت',
        ];
    }

    protected function resolveServiceName(): ServiceName
    {
        return ServiceName::query()->findOrFail($this->selectedServiceNameId);
    }

    protected function resolveServiceCategory(): ServiceCategory
    {
        return ServiceCategory::query()
            ->where('service_name_id', $this->selectedServiceNameId)
            ->findOrFail($this->selectedServiceCategoryId);
    }

    protected function calculateTotalServiceValue(): int
    {
        return (int) round($this->normalizeDecimal($this->totalQuantity) * (int) $this->valuePerUnit);
    }

    protected function normalizeDecimal(string|int|float|null $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = $this->normalizeDecimal($value);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingServiceId',
            'selectedServiceNameId',
            'selectedServiceCategoryId',
            'serviceType',
            'description',
            'totalQuantity',
            'serviceUnit',
            'valuePerUnit',
            'serviceDistrictId',
            'distributionStartDate',
            'distributionEndDate',
            'priority',
            'status',
            'statusNotes',
        ]);

        $this->serviceType = 'individual';
        $this->serviceUnit = array_key_first(Service::unitOptions()) ?? 'package';
        $this->status = 'draft';
    }

    protected function bootDefaultSelections(): void
    {
        $this->selectedServiceNameId = $this->selectedServiceNameId ?: ServiceName::query()->ordered()->value('id');
        $this->selectedServiceCategoryId = $this->selectedServiceCategoryId ?: $this->availableServiceCategories()->value('id');
    }

    protected function availableServiceCategories(): Collection
    {
        if (! $this->selectedServiceNameId) {
            return new Collection();
        }

        return ServiceCategory::query()
            ->ordered()
            ->where('service_name_id', $this->selectedServiceNameId)
            ->get();
    }

    protected function currentDeliveredQuantity(): float
    {
        if (! $this->editingServiceId) {
            return 0;
        }

        return (float) (Service::query()->whereKey($this->editingServiceId)->value('quantity_delivered') ?? 0);
    }
}
