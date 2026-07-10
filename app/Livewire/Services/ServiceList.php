<?php

namespace App\Livewire\Services;

use App\Livewire\Services\Concerns\SummarizesServiceDeliveries;
use App\Models\Service;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceList extends Component
{
    use InteractsWithNotificationModal;
    use SummarizesServiceDeliveries;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function editService(int $serviceId): void
    {
        $this->dispatch('open-dashboard-section', section: 'service-definition', id: $serviceId);
    }

    public function createService(): void
    {
        $this->dispatch('open-dashboard-section', section: 'service-definition');
    }

    public function openDeleteServiceConfirmation(int $serviceId): void
    {
        $service = Service::query()
            ->with('serviceName')
            ->withCount([
                'categories',
                'workerAllocations',
                'deliveries' => fn ($query) => $query->withTrashed(),
            ])
            ->findOrFail($serviceId);

        $serviceLabel = $service->serviceName?->name ?: $service->name ?: $service->code;
        $impactSummary = "این خدمت {$service->categories_count} دسته فعال، {$service->worker_allocations_count} تخصیص مددکار و {$service->deliveries_count} سابقه تحویل دارد.";

        $this->openNotificationModal([
            'type' => 'warning',
            'title' => 'حذف خدمت',
            'message' => "آیا از حذف خدمت «{$serviceLabel}» مطمئن هستید؟\n\n{$impactSummary}\n\nاین عملیات خدمت و دسته‌های وابسته را فقط به آرشیو منتقل می‌کند و سوابق گزارش‌گیری حفظ می‌شوند.",
            'buttons' => [
                [
                    'label' => 'حذف و انتقال به آرشیو',
                    'action' => 'event',
                    'event' => 'confirm-service-delete',
                    'payload' => ['serviceId' => $service->id],
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

    #[On('confirm-service-delete')]
    public function deleteService(int $serviceId): void
    {
        $service = Service::query()
            ->with('serviceName')
            ->findOrFail($serviceId);

        $serviceLabel = $service->serviceName?->name ?: $service->name ?: $service->code;

        $service->delete();

        $this->closeNotificationModal();
        session()->flash('service-list-success', "خدمت «{$serviceLabel}» به آرشیو منتقل شد و از فهرست فعال حذف گردید.");
    }

    public function formatQuantityForUnit(string|int|float|null $value, string $unit): string
    {
        $number = (float) ($value ?? 0);

        return number_format($number, $this->isDecimalQuantityUnit($unit) ? 2 : 0, '.', '');
    }

    public function isDecimalQuantityUnit(string $unit): bool
    {
        return in_array($unit, ['kilogram', 'gram', 'kg', 'g'], true);
    }

    public function render()
    {
        $search = trim($this->search);
        $statusFilter = $this->statusFilter;

        return view('livewire.services.service-list', [
            'services' => Service::query()
                ->with([
                    'serviceName',
                    'serviceCategory',
                    'categories' => fn ($query) => $query->ordered(),
                    'district',
                    'creator',
                    'socialWorkers',
                    'workerAllocations.socialWorker',
                    'deliveries.person.guardian',
                    'deliveries.guardian',
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nestedQuery) use ($search) {
                        $nestedQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhereHas('serviceName', fn ($serviceNameQuery) => $serviceNameQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                                $creatorQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', "%{$search}%");
                            });
                    });
                })
                ->when($statusFilter !== 'all', fn ($query) => $query->where('status', $statusFilter))
                ->latest()
                ->get(),
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
            'statusOptions' => Service::STATUS_OPTIONS,
            'priorityOptions' => Service::PRIORITY_OPTIONS,
        ]);
    }
}
