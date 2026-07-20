<?php

namespace App\Services\Deliveries;

use App\Models\Service;
use App\Models\SocialWorker;
use Illuminate\Support\Collection;

/**
 * Builds the review and confirmation payloads consumed by the service-batch
 * Blade view. It has no database or Livewire dependencies.
 */
final class ServiceBatchReviewBuilder
{
    /**
     * @param  array<int, array<string, mixed>>  $workerGroups
     * @param  array<string, string>  $unitOptions
     * @return array<string, mixed>
     */
    public function buildMiscEdit(
        array $workerGroups,
        array $unitOptions,
        string $serviceName,
        string $serviceType,
        string $date,
        string $description,
        string $fallbackWorkerName,
        string $title,
    ): array {
        $groups = collect($workerGroups)
            ->map(function (array $group) use ($unitOptions, $fallbackWorkerName): ?array {
                $rows = collect($group['categories'] ?? [])
                    ->map(fn (array $category): array => $this->miscRow($category, $unitOptions))
                    ->filter(fn (array $row): bool => $row['name'] !== '' && $row['quantity'] > 0)
                    ->values()
                    ->all();

                if ($rows === []) {
                    return null;
                }

                return [
                    'worker_name' => trim((string) ($group['worker_display'] ?? ''))
                        ?: (trim((string) ($group['worker_query'] ?? '')) ?: $fallbackWorkerName),
                    'worker_code' => (string) ($group['worker_code'] ?? ''),
                    'rows' => $rows,
                    'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $allRows = collect($groups)->flatMap(fn (array $group): array => $group['rows'])->values()->all();

        return [
            'mode' => 'misc',
            'is_edit' => true,
            'title' => $title,
            'service_name' => $serviceName,
            'service_code' => '',
            'service_type' => $serviceType,
            'worker_name' => count($groups) === 1
                ? ($groups[0]['worker_name'] ?? '-')
                : (count($groups).' '.$fallbackWorkerName),
            'worker_code' => count($groups) === 1 ? ($groups[0]['worker_code'] ?? '') : '',
            'date_label' => $date,
            'description' => trim($description),
            'total_quantity_label' => number_format(collect($allRows)->sum('quantity'), 2),
            'rows' => $allRows,
            'groups' => $groups,
            'large_quantity_rows_count' => collect($allRows)
                ->filter(fn (array $row): bool => (bool) ($row['is_large_quantity'] ?? false))
                ->count(),
            'has_empty_description_warning' => trim($description) === '',
        ];
    }

    /**
     * @param  array<int, array{quantity: float, allocated: float, assignable: float}>  $metrics
     * @param  array<int, array<string, mixed>>  $workerGroups
     * @param  array<string, string>  $unitOptions
     * @return array<string, mixed>
     */
    public function buildPredefined(
        ?Service $service,
        Collection $categories,
        array $metrics,
        array $workerGroups,
        array $unitOptions,
        callable $formatQuantity,
        string $fallbackServiceName,
        string $fallbackWorkerName,
        string $dateLabel,
        string $title,
    ): array {
        $categoriesById = $categories->keyBy('id');
        $claimedByCategory = [];
        $groups = [];

        foreach (array_values($workerGroups) as $group) {
            $rows = [];

            foreach ($group['allocations'] ?? [] as $categoryId => $rawQuantity) {
                $categoryId = (int) $categoryId;
                $quantity = max(0, (float) $rawQuantity);
                $category = $categoriesById->get($categoryId);

                if ($quantity <= 0 || ! $category) {
                    continue;
                }

                $baseAssignable = (float) ($metrics[$categoryId]['assignable'] ?? 0);
                $alreadyClaimed = (float) ($claimedByCategory[$categoryId] ?? 0);
                $remainingAfter = max(0, $baseAssignable - $alreadyClaimed - $quantity);
                $claimedByCategory[$categoryId] = $alreadyClaimed + $quantity;

                $rows[] = [
                    'name' => $category->name,
                    'unit' => $category->unit,
                    'unit_label' => $unitOptions[$category->unit] ?? $category->unit,
                    'quantity' => $quantity,
                    'quantity_label' => $formatQuantity($quantity, (string) $category->unit),
                    'remaining_label' => $formatQuantity($remainingAfter, (string) $category->unit),
                    'consumes_all_remaining' => $baseAssignable > 0 && $remainingAfter <= 0.00001,
                ];
            }

            if ($rows === []) {
                continue;
            }

            $groups[] = [
                'worker_name' => trim((string) ($group['worker_display'] ?? ''))
                    ?: (trim((string) ($group['worker_query'] ?? '')) ?: $fallbackWorkerName),
                'worker_code' => (string) ($group['worker_code'] ?? ''),
                'rows' => $rows,
                'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
            ];
        }

        $allRows = collect($groups)->flatMap(fn (array $group): array => $group['rows'])->values()->all();
        $workerCount = count($groups);

        return [
            'mode' => 'predefined',
            'title' => $title,
            'service_name' => $service?->name ?: ($service?->serviceName?->name ?? $fallbackServiceName),
            'service_code' => (string) ($service?->code ?? ''),
            'worker_name' => $workerCount === 1
                ? ($groups[0]['worker_name'] ?? '-')
                : ($workerCount.' '.$fallbackWorkerName),
            'worker_code' => $workerCount === 1 ? ($groups[0]['worker_code'] ?? '') : '',
            'date_label' => $dateLabel,
            'total_quantity_label' => number_format(collect($allRows)->sum('quantity'), 2),
            'rows' => $allRows,
            'groups' => $workerCount > 1 ? $groups : [],
            'depleting_rows_count' => collect($allRows)
                ->filter(fn (array $row): bool => (bool) ($row['consumes_all_remaining'] ?? false))
                ->count(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<string, string>  $unitOptions
     * @return array<string, mixed>
     */
    public function buildMisc(
        array $categories,
        array $unitOptions,
        ?SocialWorker $worker,
        string $workerQuery,
        string $serviceName,
        string $serviceType,
        string $date,
        string $description,
        string $title,
    ): array {
        $rows = collect($categories)
            ->map(fn (array $category): array => $this->miscRow($category, $unitOptions))
            ->filter(fn (array $row): bool => $row['name'] !== '' && $row['quantity'] > 0)
            ->values()
            ->all();

        return [
            'mode' => 'misc',
            'title' => $title,
            'service_name' => $serviceName,
            'service_code' => '',
            'service_type' => $serviceType,
            'worker_name' => $worker?->full_name ?? $workerQuery,
            'worker_code' => $worker?->worker_code ? (string) $worker->worker_code : '',
            'date_label' => $date,
            'description' => trim($description),
            'total_quantity_label' => number_format(collect($rows)->sum('quantity'), 2),
            'rows' => $rows,
            'large_quantity_rows_count' => collect($rows)
                ->filter(fn (array $row): bool => (bool) ($row['is_large_quantity'] ?? false))
                ->count(),
            'has_empty_description_warning' => trim($description) === '',
        ];
    }

    /**
     * @param  array<string, mixed>  $category
     * @param  array<string, string>  $unitOptions
     * @return array<string, mixed>
     */
    protected function miscRow(array $category, array $unitOptions): array
    {
        $quantity = (float) ($category['quantity'] ?? 0);
        $unit = (string) ($category['unit'] ?? '');

        return [
            'name' => trim((string) ($category['name'] ?? '')),
            'unit' => $unit,
            'unit_label' => $unitOptions[$unit] ?? $unit,
            'quantity' => $quantity,
            'quantity_label' => number_format($quantity, 2),
            'remaining_label' => '0.00',
            'is_large_quantity' => $quantity >= 1000,
        ];
    }
}
