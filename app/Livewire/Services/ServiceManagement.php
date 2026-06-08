<?php

namespace App\Livewire\Services;

use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceUnit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ServiceManagement extends Component
{
    public ?int $selectedServiceNameId = null;

    public ?int $editingServiceNameId = null;
    public string $serviceName = '';
    public string $serviceNameSortId = '';

    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public string $categorySortId = '';

    public ?int $editingUnitId = null;
    public string $unitLabel = '';
    public string $unitKey = '';
    public string $unitSortId = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->selectedServiceNameId = ServiceName::query()->ordered()->value('id');
    }

    public function saveServiceName(): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255', Rule::unique('service_names', 'name')->ignore($this->editingServiceNameId)],
            'serviceNameSortId' => ['nullable', 'integer', 'min:1'],
        ], [], [
            'serviceName' => 'نام خدمت',
            'serviceNameSortId' => 'ترتیب نمایش خدمت',
        ]);

        $serviceName = ServiceName::query()->updateOrCreate(
            ['id' => $this->editingServiceNameId],
            [
                'name' => trim($validated['serviceName']),
                'sort_id' => $validated['serviceNameSortId'] === '' ? 999 : (int) $validated['serviceNameSortId'],
                'created_by' => $this->editingServiceNameId
                    ? ServiceName::query()->whereKey($this->editingServiceNameId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->selectedServiceNameId = $serviceName->id;
        $this->resetServiceNameForm();
        session()->flash('management-success', 'نام خدمت با موفقیت ذخیره شد.');
    }

    public function editServiceName(int $serviceNameId): void
    {
        $serviceName = ServiceName::query()->findOrFail($serviceNameId);

        $this->editingServiceNameId = $serviceName->id;
        $this->serviceName = $serviceName->name;
        $this->serviceNameSortId = (string) $serviceName->sort_id;
        $this->selectedServiceNameId = $serviceName->id;
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'selectedServiceNameId' => ['required', 'integer', 'exists:service_names,id'],
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_categories', 'name')
                    ->where(fn ($query) => $query->where('service_name_id', $this->selectedServiceNameId))
                    ->ignore($this->editingCategoryId),
            ],
            'categorySortId' => ['nullable', 'integer', 'min:1'],
        ], [], [
            'selectedServiceNameId' => 'نام خدمت',
            'categoryName' => 'دسته‌بندی خدمت',
            'categorySortId' => 'ترتیب نمایش دسته‌بندی',
        ]);

        ServiceCategory::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'service_name_id' => (int) $validated['selectedServiceNameId'],
                'name' => trim($validated['categoryName']),
                'sort_id' => $validated['categorySortId'] === '' ? 999 : (int) $validated['categorySortId'],
                'created_by' => $this->editingCategoryId
                    ? ServiceCategory::query()->whereKey($this->editingCategoryId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->resetCategoryForm();
        session()->flash('management-success', 'دسته‌بندی خدمت با موفقیت ذخیره شد.');
    }

    public function editCategory(int $categoryId): void
    {
        $category = ServiceCategory::query()->findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->selectedServiceNameId = $category->service_name_id;
        $this->categoryName = $category->name;
        $this->categorySortId = (string) $category->sort_id;
    }

    public function saveUnit(): void
    {
        $validated = $this->validate([
            'unitLabel' => ['required', 'string', 'max:255'],
            'unitKey' => ['nullable', 'string', 'max:255', Rule::unique('service_units', 'key')->ignore($this->editingUnitId)],
            'unitSortId' => ['nullable', 'integer', 'min:1'],
        ], [], [
            'unitLabel' => 'واحد خدمت',
            'unitKey' => 'کلید واحد',
            'unitSortId' => 'ترتیب نمایش واحد',
        ]);

        $unitKey = trim($validated['unitKey']) !== ''
            ? Str::slug(trim($validated['unitKey']), '_')
            : Str::slug(trim($validated['unitLabel']), '_');

        if ($unitKey === '') {
            $unitKey = 'unit_' . now()->timestamp;
        }

        ServiceUnit::query()->updateOrCreate(
            ['id' => $this->editingUnitId],
            [
                'key' => $unitKey,
                'label' => trim($validated['unitLabel']),
                'sort_id' => $validated['unitSortId'] === '' ? 999 : (int) $validated['unitSortId'],
                'created_by' => $this->editingUnitId
                    ? ServiceUnit::query()->whereKey($this->editingUnitId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->resetUnitForm();
        session()->flash('management-success', 'واحد خدمت با موفقیت ذخیره شد.');
    }

    public function editUnit(int $unitId): void
    {
        $unit = ServiceUnit::query()->findOrFail($unitId);

        $this->editingUnitId = $unit->id;
        $this->unitLabel = $unit->label;
        $this->unitKey = $unit->key;
        $this->unitSortId = (string) $unit->sort_id;
    }

    public function resetServiceNameForm(): void
    {
        $this->reset(['editingServiceNameId', 'serviceName', 'serviceNameSortId']);
        $this->resetValidation(['serviceName', 'serviceNameSortId']);
    }

    public function resetCategoryForm(): void
    {
        $this->reset(['editingCategoryId', 'categoryName', 'categorySortId']);
        $this->resetValidation(['categoryName', 'categorySortId']);
    }

    public function resetUnitForm(): void
    {
        $this->reset(['editingUnitId', 'unitLabel', 'unitKey', 'unitSortId']);
        $this->resetValidation(['unitLabel', 'unitKey', 'unitSortId']);
    }

    public function render()
    {
        return view('livewire.services.service-management', [
            'serviceNames' => ServiceName::query()->ordered()->withCount('categories')->get(),
            'serviceCategories' => ServiceCategory::query()
                ->with('serviceName')
                ->when($this->selectedServiceNameId, fn ($query) => $query->where('service_name_id', $this->selectedServiceNameId))
                ->ordered()
                ->get(),
            'serviceUnits' => ServiceUnit::query()->ordered()->get(),
        ]);
    }
}
