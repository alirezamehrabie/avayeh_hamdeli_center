<?php

namespace App\Livewire\Services\Concerns;

use App\Models\Service;
use Illuminate\Support\Collection;

trait SummarizesServiceDeliveries
{
    public function formatReadableNumber(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return number_format((int) $number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    }

    public function socialWorkerSummary(Service $service, array $unitOptions): array
    {
        $deliveries = $service->deliveries;
        $categories = $service->categories->keyBy('id');

        return [
            'service' => $service->serviceName?->name ?: '-',
            'code' => $service->code,
            'workers' => $service->workerAllocations
                ->groupBy('social_worker_id')
                ->map(function (Collection $allocations, int|string $workerId) use ($deliveries, $categories, $unitOptions): array {
                    $worker = $allocations->first()?->socialWorker;
                    $workerDeliveries = $deliveries->where('social_worker_id', (int) $workerId);
                    $totalAllocated = (float) $allocations->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                    $totalDelivered = (float) $workerDeliveries->sum(fn ($delivery) => (float) $delivery->delivered_quantity);
                    $progress = $totalAllocated > 0 ? min(100, round(($totalDelivered / $totalAllocated) * 100, 1)) : 0;

                    $categoryIds = $allocations
                        ->pluck('service_category_id')
                        ->merge($workerDeliveries->pluck('service_category_id'))
                        ->filter()
                        ->unique()
                        ->values();

                    return [
                        'id' => (int) $workerId,
                        'name' => $worker?->full_name ?: '-',
                        'code' => $worker?->worker_code ? (string) $worker->worker_code : '-',
                        'mobile' => $worker?->mobile ?: '-',
                        'initials' => $this->workerInitials($worker?->first_name, $worker?->last_name),
                        'allocated' => $this->formatReadableNumber($totalAllocated),
                        'delivered' => $this->formatReadableNumber($totalDelivered),
                        'remaining' => $this->formatReadableNumber(max(0, $totalAllocated - $totalDelivered)),
                        'progress' => $progress,
                        'categories' => $categoryIds
                            ->map(function (int|string $categoryId) use ($allocations, $workerDeliveries, $categories, $unitOptions): array {
                                $category = $categories->get((int) $categoryId);
                                $allocated = (float) $allocations
                                    ->where('service_category_id', (int) $categoryId)
                                    ->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                                $delivered = (float) $workerDeliveries
                                    ->where('service_category_id', (int) $categoryId)
                                    ->sum(fn ($delivery) => (float) $delivery->delivered_quantity);
                                $progress = $allocated > 0 ? min(100, round(($delivered / $allocated) * 100, 1)) : 0;
                                $unit = $category?->unit ? ($unitOptions[$category->unit] ?? $category->unit) : '-';

                                return [
                                    'name' => $category?->name ?: '-',
                                    'unit' => $unit,
                                    'allocated' => $this->formatReadableNumber($allocated),
                                    'delivered' => $this->formatReadableNumber($delivered),
                                    'remaining' => $this->formatReadableNumber(max(0, $allocated - $delivered)),
                                    'progress' => $progress,
                                ];
                            })
                            ->values(),
                        'recipients' => $this->recipientSummary($workerDeliveries),
                    ];
                })
                ->sortBy('name')
                ->values(),
        ];
    }

    protected function recipientSummary(Collection $deliveries): Collection
    {
        return $deliveries
            ->groupBy(function ($delivery) {
                if ($delivery->person_id) {
                    return 'person-'.$delivery->person_id;
                }

                if ($delivery->guardian_id) {
                    return 'guardian-'.$delivery->guardian_id;
                }

                $nationalId = trim((string) ($delivery->national_id ?? ''));

                return $nationalId !== '' ? 'manual-'.$nationalId : 'manual-delivery-'.$delivery->id;
            })
            ->map(function (Collection $recipientDeliveries): array {
                $first = $recipientDeliveries->first();
                $type = $first->person
                    ? 'مددجو'
                    : ($first->guardian ? 'سرپرست خانوار' : 'ثبت دستی');

                return [
                    'name' => $first->recipient_name ?: '-',
                    'national_id' => $first->recipient_national_id ?: '-',
                    'type' => $type,
                    'deliveries_count' => $recipientDeliveries->count(),
                    'delivered' => $this->formatReadableNumber(
                        (float) $recipientDeliveries->sum(fn ($delivery) => (float) $delivery->delivered_quantity)
                    ),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    protected function workerInitials(?string $firstName, ?string $lastName): string
    {
        $initials = mb_substr(trim((string) $firstName), 0, 1).mb_substr(trim((string) $lastName), 0, 1);

        return $initials !== '' ? $initials : '؟';
    }
}
