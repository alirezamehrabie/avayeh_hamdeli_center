<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ServiceList extends Component
{
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

    public function formatReadableNumber(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return number_format((int) $number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
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
