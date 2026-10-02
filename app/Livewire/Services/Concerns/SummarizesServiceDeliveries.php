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
                ->map(function (Collection $allocations, int|string $workerId) use ($deliveries, $categories, $unitOptions): ?array {
                    $worker = $allocations->first()?->socialWorker;
                    $workerDeliveries = $deliveries->where('social_worker_id', (int) $workerId);
                    $totalAllocated = (float) $allocations->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                    $totalDelivered = (float) $workerDeliveries->sum(fn ($delivery) => (float) $delivery->delivered_quantity);

                    if ($totalAllocated <= 0 && $totalDelivered <= 0) {
                        return null;
                    }

                    $progress = $totalAllocated > 0 ? min(100, round(($totalDelivered / $totalAllocated) * 100, 1)) : 0;

                    $categoryIds = $allocations
                        ->where('allocated_quantity', '>', 0)
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
                ->filter()
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
                $isGuardian = (bool) (! $first->person && $first->guardian);
                $type = $first->person
                    ? 'مددجو'
                    : ($first->guardian ? 'سرپرست خانوار' : 'ثبت دستی');

                $people = [];

                if ($first->person) {
                    $person = $first->person;
                    $name = trim($person->first_name.' '.$person->last_name);
                    $people[] = [
                        'id' => (int) $person->id,
                        'name' => $name !== '' ? $name : ($first->recipient_name ?: '-'),
                        'national_id' => (string) ($person->national_id ?: '-'),
                        'person_code' => (string) ($person->person_code ?: '-'),
                    ];
                } elseif ($first->guardian) {
                    $guardianPeople = $first->guardian->people ?? collect();
                    $people = $guardianPeople->map(function ($person): array {
                        $name = trim($person->first_name.' '.$person->last_name);

                        return [
                            'id' => (int) $person->id,
                            'name' => $name !== '' ? $name : ($person->person_code ?: '-'),
                            'national_id' => (string) ($person->national_id ?: '-'),
                            'person_code' => (string) ($person->person_code ?: '-'),
                        ];
                    })->values()->all();
                }

                if (empty($people) && $first->person_id) {
                    $people[] = [
                        'id' => (int) $first->person_id,
                        'name' => $first->recipient_name ?: '-',
                        'national_id' => $first->recipient_national_id ?: '-',
                        'person_code' => '-',
                    ];
                }

                if (empty($people) && $first->recipient_national_id && $first->recipient_national_id !== '-') {
                    $matchedPerson = \App\Models\Person::query()
                        ->where('national_id', $first->recipient_national_id)
                        ->first(['id', 'first_name', 'last_name', 'national_id', 'person_code']);

                    if ($matchedPerson) {
                        $name = trim($matchedPerson->first_name.' '.$matchedPerson->last_name);
                        $people[] = [
                            'id' => (int) $matchedPerson->id,
                            'name' => $name !== '' ? $name : ($matchedPerson->person_code ?: '-'),
                            'national_id' => (string) ($matchedPerson->national_id ?: '-'),
                            'person_code' => (string) ($matchedPerson->person_code ?: '-'),
                        ];
                    }
                }

                $primaryPersonId = ! empty($people) ? $people[0]['id'] : null;

                return [
                    'person_id' => $primaryPersonId,
                    'is_guardian' => $isGuardian,
                    'people' => $people,
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
