<?php

namespace App\Livewire\Services;

use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceUnit;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceManagement extends Component
{
    use InteractsWithNotificationModal;

    public ?int $selectedServiceNameId = null;
    public string $serviceNameSearch = '';

    public ?int $editingServiceNameId = null;
    public string $serviceName = '';

    public ?int $editingCategoryId = null;
    public string $categoryName = '';

    public ?int $editingUnitId = null;
    public string $unitLabel = '';
    public string $unitKey = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->selectedServiceNameId = ServiceName::query()->ordered()->value('id');
    }

    public function saveServiceName(): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255', Rule::unique('service_names', 'name')->ignore($this->editingServiceNameId)],
        ], [], [
            'serviceName' => 'نام خدمت',
        ]);

        if ($this->editingServiceNameId) {
            $this->persistServiceName($validated['serviceName']);
        }
    }

    #[On('confirm-service-name-save')]
    public function confirmServiceNameSave(string $serviceNameInput): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255', Rule::unique('service_names', 'name')->ignore($this->editingServiceNameId)],
        ], [], [
            'serviceName' => 'نام خدمت',
        ]);

        $this->persistServiceName($serviceNameInput);
    }

    protected function persistServiceName(string $serviceNameInput): void
    {
        $serviceName = ServiceName::query()->updateOrCreate(
            ['id' => $this->editingServiceNameId],
            [
                'name' => trim($serviceNameInput),
                'sort_id' => $this->editingServiceNameId
                    ? ServiceName::query()->whereKey($this->editingServiceNameId)->value('sort_id')
                    : $this->getNextSortId(ServiceName::query()),
                'created_by' => $this->editingServiceNameId
                    ? ServiceName::query()->whereKey($this->editingServiceNameId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->selectedServiceNameId = $serviceName->id;
        $this->resetServiceNameForm();
        $this->pendingServiceName = '';
        $this->closeNotificationModal();
        session()->flash('management-success', 'نام خدمت با موفقیت ذخیره شد.');
    }

    public function editServiceName(int $serviceNameId): void
    {
        $serviceName = ServiceName::query()->findOrFail($serviceNameId);

        $this->editingServiceNameId = $serviceName->id;
        $this->serviceName = $serviceName->name;
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
                    ->where(fn ($query) => $query
                        ->where('service_name_id', $this->selectedServiceNameId)
                        ->whereNull('deleted_at'))
                    ->ignore($this->editingCategoryId),
            ],
        ], [], [
            'selectedServiceNameId' => 'نام خدمت',
            'categoryName' => 'دسته‌بندی خدمت',
        ]);

        ServiceCategory::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'service_name_id' => (int) $validated['selectedServiceNameId'],
                'name' => trim($validated['categoryName']),
                'sort_id' => $this->editingCategoryId
                    ? ServiceCategory::query()->whereKey($this->editingCategoryId)->value('sort_id')
                    : $this->getNextSortId(ServiceCategory::query()),
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
    }

    public function saveUnit(): void
    {
        $validated = $this->validate([
            'unitLabel' => ['required', 'string', 'max:255'],
            'unitKey' => ['nullable', 'string', 'max:255', Rule::unique('service_units', 'key')->ignore($this->editingUnitId)],
        ], [], [
            'unitLabel' => 'واحد خدمت',
            'unitKey' => 'کلید واحد',
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
                'sort_id' => $this->editingUnitId
                    ? ServiceUnit::query()->whereKey($this->editingUnitId)->value('sort_id')
                    : $this->getNextSortId(ServiceUnit::query()),
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
    }

    public function resetServiceNameForm(): void
    {
        $this->reset(['editingServiceNameId', 'serviceName']);
        $this->resetValidation(['serviceName']);
    }

    public function resetCategoryForm(): void
    {
        $this->reset(['editingCategoryId', 'categoryName']);
        $this->resetValidation(['categoryName']);
    }

    public function resetUnitForm(): void
    {
        $this->reset(['editingUnitId', 'unitLabel', 'unitKey']);
        $this->resetValidation(['unitLabel', 'unitKey']);
    }

    protected function getNextSortId($query): int
    {
        return ((int) $query->max('sort_id')) + 1;
    }

    public function render()
    {
        $serviceNamesQuery = ServiceName::query()
            ->when($this->serviceNameSearch !== '', fn ($query) => $query->where('name', 'like', '%' . trim($this->serviceNameSearch) . '%'))
            ->ordered();

        return view('livewire.services.service-management', [
            'serviceNames' => $serviceNamesQuery->withCount('categories')->get(),
            'serviceCategories' => ServiceCategory::query()
                ->with('serviceName')
                ->when($this->selectedServiceNameId, fn ($query) => $query->where('service_name_id', $this->selectedServiceNameId))
                ->ordered()
                ->get(),
            'serviceUnits' => ServiceUnit::query()->ordered()->get(),
        ]);
    }
}
