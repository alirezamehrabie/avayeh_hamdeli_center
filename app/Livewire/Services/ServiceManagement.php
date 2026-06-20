<?php

namespace App\Livewire\Services;

use App\Models\ServiceCategoryTemplate;
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

    public function selectServiceName(int $serviceNameId): void
    {
        $serviceName = ServiceName::query()->findOrFail($serviceNameId);

        $this->selectedServiceNameId = $serviceName->id;
        $this->resetCategoryForm();
    }

    public function openArchiveServiceNameConfirmation(int $serviceNameId): void
    {
        $serviceName = ServiceName::query()
            ->withCount([
                'services',
                'categories',
                'categoryTemplates',
            ])
            ->findOrFail($serviceNameId);

        $usageMessage = $this->serviceNameUsageMessage(
            (int) $serviceName->services_count,
            (int) $serviceName->categories_count,
            (int) $serviceName->category_templates_count
        );

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'حذف نام خدمت',
            'message' => "آیا از حذف «{$serviceName->name}» از فهرست نام خدمات مطمئن هستید؟\n\n{$usageMessage}\n\nاین عملیات فقط نام خدمت را از انتخاب‌های جدید پنهان می‌کند و سوابق قبلی حفظ می‌شوند.",
            'buttons' => [
                [
                    'label' => 'حذف از فهرست',
                    'action' => 'event',
                    'event' => 'confirm-service-name-archive',
                    'payload' => ['serviceNameId' => $serviceName->id],
                    'variant' => 'danger',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-service-name-archive')]
    public function archiveServiceName(int $serviceNameId): void
    {
        $serviceName = ServiceName::query()->findOrFail($serviceNameId);
        $archivedName = $serviceName->name;

        $serviceName->delete();

        if ((int) $this->selectedServiceNameId === $serviceNameId) {
            $this->selectedServiceNameId = ServiceName::query()->ordered()->value('id');
            $this->resetCategoryForm();
        }

        if ((int) $this->editingServiceNameId === $serviceNameId) {
            $this->resetServiceNameForm();
        }

        $this->closeNotificationModal();
        session()->flash('management-success', "نام خدمت «{$archivedName}» از فهرست انتخاب‌های جدید حذف شد. سوابق قبلی حفظ شده‌اند.");
    }

    public function restoreServiceName(int $serviceNameId): void
    {
        $serviceName = ServiceName::onlyTrashed()->findOrFail($serviceNameId);

        if (ServiceName::query()->where('name', $serviceName->name)->exists()) {
            $this->openNotificationModal([
                'type' => 'error',
                'title' => 'بازیابی امکان‌پذیر نیست',
                'message' => 'یک نام خدمت فعال با همین عنوان وجود دارد. ابتدا نام فعال را تغییر دهید یا حذف کنید.',
                'buttons' => [[
                    'label' => 'متوجه شدم',
                    'action' => 'close',
                    'variant' => 'danger',
                ]],
            ]);

            return;
        }

        $serviceName->restore();
        $this->selectedServiceNameId = $serviceName->id;
        $this->resetServiceNameForm();
        $this->resetCategoryForm();

        session()->flash('management-success', "نام خدمت «{$serviceName->name}» بازیابی شد.");
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'selectedServiceNameId' => ['required', 'integer', 'exists:service_names,id'],
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_category_templates', 'name')
                    ->where(fn ($query) => $query
                        ->where('service_name_id', $this->selectedServiceNameId)
                        ->whereNull('deleted_at'))
                    ->ignore($this->editingCategoryId),
            ],
        ], [], [
            'selectedServiceNameId' => 'نام خدمت',
            'categoryName' => 'دسته‌بندی خدمت',
        ]);

        ServiceCategoryTemplate::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'service_name_id' => (int) $validated['selectedServiceNameId'],
                'name' => trim($validated['categoryName']),
                'sort_id' => $this->editingCategoryId
                    ? ServiceCategoryTemplate::query()->whereKey($this->editingCategoryId)->value('sort_id')
                    : $this->getNextSortId(ServiceCategoryTemplate::query()),
                'created_by' => $this->editingCategoryId
                    ? ServiceCategoryTemplate::query()->whereKey($this->editingCategoryId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->resetCategoryForm();
        session()->flash('management-success', 'دسته‌بندی خدمت با موفقیت ذخیره شد.');
    }

    public function editCategory(int $categoryId): void
    {
        $category = ServiceCategoryTemplate::query()->findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->selectedServiceNameId = $category->service_name_id;
        $this->categoryName = $category->name;
    }

    public function openDeleteCategoryConfirmation(int $categoryId): void
    {
        $category = ServiceCategoryTemplate::query()
            ->with('serviceName')
            ->findOrFail($categoryId);

        $usedServicesCount = ServiceCategory::query()
            ->where('service_name_id', $category->service_name_id)
            ->where('name', $category->name)
            ->count();

        $usageMessage = $usedServicesCount > 0
            ? "\n\nاین دسته در {$usedServicesCount} خدمت ثبت‌شده استفاده شده است. حذف فقط از فهرست انتخاب‌های جدید انجام می‌شود و اطلاعات خدمات قبلی حفظ می‌گردد."
            : '';

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'حذف دسته‌بندی',
            'message' => "آیا از حذف دسته‌بندی «{$category->name}» مطمئن هستید؟{$usageMessage}",
            'buttons' => [
                [
                    'label' => 'حذف دسته‌بندی',
                    'action' => 'event',
                    'event' => 'confirm-category-delete',
                    'payload' => ['categoryId' => $category->id],
                    'variant' => 'danger',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-category-delete')]
    public function deleteCategory(int $categoryId): void
    {
        $category = ServiceCategoryTemplate::query()->findOrFail($categoryId);

        $activeCategoryCount = ServiceCategoryTemplate::query()
            ->where('service_name_id', $category->service_name_id)
            ->count();

        if ($activeCategoryCount <= 1) {
            $this->openNotificationModal([
                'type' => 'error',
                'title' => 'حذف امکان‌پذیر نیست',
                'message' => 'برای هر نام خدمت باید حداقل یک دسته‌بندی فعال باقی بماند.',
                'buttons' => [[
                    'label' => 'متوجه شدم',
                    'action' => 'close',
                    'variant' => 'danger',
                ]],
            ]);

            return;
        }

        $deletedCategoryName = $category->name;
        $category->delete();

        if ((int) $this->editingCategoryId === (int) $categoryId) {
            $this->resetCategoryForm();
        }

        $this->closeNotificationModal();
        session()->flash('management-success', "دسته‌بندی «{$deletedCategoryName}» حذف شد. اطلاعات خدمات قبلی حفظ شده است.");
    }

    public function restoreCategory(int $categoryId): void
    {
        $category = ServiceCategoryTemplate::onlyTrashed()
            ->with(['serviceName' => fn ($query) => $query->withTrashed()])
            ->findOrFail($categoryId);

        if ($category->serviceName?->trashed()) {
            $this->openNotificationModal([
                'type' => 'error',
                'title' => 'بازیابی امکان‌پذیر نیست',
                'message' => 'برای بازیابی این دسته‌بندی، ابتدا نام خدمت وابسته را از آرشیو بازیابی کنید.',
                'buttons' => [[
                    'label' => 'متوجه شدم',
                    'action' => 'close',
                    'variant' => 'danger',
                ]],
            ]);

            return;
        }

        $duplicateExists = ServiceCategoryTemplate::query()
            ->where('service_name_id', $category->service_name_id)
            ->where('name', $category->name)
            ->exists();

        if ($duplicateExists) {
            $this->openNotificationModal([
                'type' => 'error',
                'title' => 'بازیابی امکان‌پذیر نیست',
                'message' => 'یک دسته‌بندی فعال با همین نام برای این خدمت وجود دارد.',
                'buttons' => [[
                    'label' => 'متوجه شدم',
                    'action' => 'close',
                    'variant' => 'danger',
                ]],
            ]);

            return;
        }

        $category->restore();
        $this->selectedServiceNameId = $category->service_name_id;
        $this->resetCategoryForm();

        session()->flash('management-success', "دسته‌بندی «{$category->name}» بازیابی شد.");
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

    protected function serviceNameUsageMessage(int $servicesCount, int $categoriesCount, int $templatesCount): string
    {
        if ($servicesCount === 0 && $categoriesCount === 0 && $templatesCount === 0) {
            return 'این نام خدمت هنوز در هیچ خدمت یا دسته‌ای استفاده نشده است.';
        }

        return "این نام خدمت در {$servicesCount} خدمت ثبت‌شده، {$categoriesCount} دسته خدمت و {$templatesCount} دسته‌بندی پیشنهادی استفاده شده است.";
    }

    public function render()
    {
        $serviceNamesQuery = ServiceName::query()
            ->when($this->serviceNameSearch !== '', fn ($query) => $query->where('name', 'like', '%' . trim($this->serviceNameSearch) . '%'))
            ->ordered();

        return view('livewire.services.service-management', [
            'allServiceNames' => ServiceName::query()
                ->withCount(['services', 'categories', 'categoryTemplates'])
                ->ordered()
                ->get(),
            'serviceNames' => $serviceNamesQuery
                ->withCount(['services', 'categories', 'categoryTemplates'])
                ->get(),
            'serviceCategories' => ServiceCategoryTemplate::query()
                ->with('serviceName')
                ->when(
                    $this->selectedServiceNameId,
                    fn ($query) => $query->where('service_name_id', $this->selectedServiceNameId),
                    fn ($query) => $query->whereRaw('0 = 1')
                )
                ->ordered()
                ->get(),
            'serviceUnits' => ServiceUnit::query()->ordered()->get(),
            'archivedServiceNames' => ServiceName::onlyTrashed()
                ->withCount([
                    'services',
                    'categories',
                    'categoryTemplates' => fn ($query) => $query->withTrashed(),
                ])
                ->orderByDesc('deleted_at')
                ->get(),
            'archivedServiceCategories' => ServiceCategoryTemplate::onlyTrashed()
                ->with(['serviceName' => fn ($query) => $query->withTrashed()])
                ->orderByDesc('deleted_at')
                ->get(),
        ]);
    }
}
