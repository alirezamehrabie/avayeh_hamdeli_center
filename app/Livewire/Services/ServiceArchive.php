<?php

namespace App\Livewire\Services;

use App\Models\Service;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Traits\InteractsWithNotificationModal;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceArchive extends Component
{
    use InteractsWithNotificationModal;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function openRestoreServiceNameConfirmation(int $serviceNameId): void
    {
        $serviceName = ServiceName::onlyTrashed()->findOrFail($serviceNameId);

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'بازیابی نام خدمت',
            'message' => "آیا از بازیابی نام خدمت «{$serviceName->name}» مطمئن هستید؟ این نام دوباره در فهرست انتخاب‌های جدید نمایش داده می‌شود.",
            'buttons' => [
                [
                    'label' => 'بازیابی نام خدمت',
                    'action' => 'event',
                    'event' => 'confirm-service-name-restore',
                    'payload' => ['serviceNameId' => $serviceName->id],
                    'variant' => 'primary',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-service-name-restore')]
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

        session()->flash('archive-success', "نام خدمت «{$serviceName->name}» بازیابی شد.");
    }

    public function openRestoreCategoryConfirmation(int $categoryId): void
    {
        $category = ServiceCategoryTemplate::onlyTrashed()
            ->with(['serviceName' => fn ($query) => $query->withTrashed()])
            ->findOrFail($categoryId);

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'بازیابی دسته‌بندی',
            'message' => "آیا از بازیابی دسته‌بندی «{$category->name}» مطمئن هستید؟ این دسته‌بندی دوباره برای «{$category->serviceName?->name}» قابل انتخاب می‌شود.",
            'buttons' => [
                [
                    'label' => 'بازیابی دسته‌بندی',
                    'action' => 'event',
                    'event' => 'confirm-service-category-restore',
                    'payload' => ['categoryId' => $category->id],
                    'variant' => 'primary',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-service-category-restore')]
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

        session()->flash('archive-success', "دسته‌بندی «{$category->name}» بازیابی شد.");
    }

    public function openRestoreServiceConfirmation(int $serviceId): void
    {
        $service = Service::onlyTrashed()
            ->with('serviceName')
            ->withCount([
                'categories' => fn ($query) => $query->withTrashed(),
                'workerAllocations',
                'deliveries' => fn ($query) => $query->withTrashed(),
            ])
            ->findOrFail($serviceId);

        $serviceLabel = $service->serviceName?->name ?: $service->name ?: $service->code;

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'بازیابی خدمت',
            'message' => "آیا از بازیابی خدمت «{$serviceLabel}» مطمئن هستید؟\n\nبا این کار خدمت به‌همراه {$service->categories_count} دسته آرشیوشده دوباره به فهرست فعال بازمی‌گردد.",
            'buttons' => [
                [
                    'label' => 'بازیابی خدمت',
                    'action' => 'event',
                    'event' => 'confirm-service-restore',
                    'payload' => ['serviceId' => $service->id],
                    'variant' => 'primary',
                ],
                [
                    'label' => 'انصراف',
                    'action' => 'close',
                    'variant' => 'secondary',
                ],
            ],
        ]);
    }

    #[On('confirm-service-restore')]
    public function restoreService(int $serviceId): void
    {
        $service = Service::onlyTrashed()
            ->with('serviceName')
            ->findOrFail($serviceId);

        $serviceLabel = $service->serviceName?->name ?: $service->name ?: $service->code;

        $service->restore();

        session()->flash('archive-success', "خدمت «{$serviceLabel}» به فهرست فعال بازگردانده شد.");
    }

    public function render()
    {
        return view('livewire.services.service-archive', [
            'archivedServices' => Service::onlyTrashed()
                ->with('serviceName')
                ->withCount([
                    'categories' => fn ($query) => $query->withTrashed(),
                    'workerAllocations',
                    'deliveries' => fn ($query) => $query->withTrashed(),
                ])
                ->orderByDesc('deleted_at')
                ->get(),
            'archivedServiceNames' => ServiceName::onlyTrashed()
                ->withCount([
                    'services' => fn ($query) => $query->withTrashed(),
                    'categories' => fn ($query) => $query->withTrashed(),
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
