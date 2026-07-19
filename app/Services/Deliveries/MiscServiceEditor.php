<?php

namespace App\Services\Deliveries;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceWorkerAllocation;
use Illuminate\Support\Collection;

/**
 * Persists the category/allocation changes for an operator-defined "misc"
 * service edit.
 *
 * This is the write half of ServiceBatchCreator::saveMiscServiceEdit(). The
 * component still owns the surrounding transaction, row locking, optimistic
 * version check, and — crucially — the category-resolution/cache helpers, which
 * are shared with the live edit UI cycle and therefore must stay on the
 * component. What moves here is the pure persistence: it receives already
 * resolved payloads and writes them.
 *
 * Rules preserved verbatim from the component:
 *  - each payload category is upserted (existing row reused by id, soft-deleted
 *    rows restored, otherwise a new row created) with a fresh sort order,
 *  - each worker allocation is upserted, reusing the existing (category, worker)
 *    row when present so pivot ids and audit metadata survive,
 *  - allocations for workers no longer submitted against a retained category are
 *    pruned, then whole categories/allocations dropped from the submit are
 *    deleted,
 *  - the service header (type, description, total, distribution window) is
 *    updated and its financial/delivery aggregates refreshed.
 */
class MiscServiceEditor
{
    /**
     * @param  Service  $service  the locked, in-transaction service being edited
     * @param  Collection<int, ServiceCategory>  $existingCategories  withTrashed categories keyed by id
     * @param  Collection<string, ServiceWorkerAllocation>  $existingAllocationsByWorkerAndCategory  keyed "categoryId:workerId"
     * @param  array<string, array{existing_id: int|null, name: string, unit: string, quantity: float, sort_id: int}>  $categoryPayloads
     * @param  array<string, array<int, float>>  $categoryWorkerAllocations  payload key => [workerId => quantity]
     * @param  string  $distributionDate  Gregorian date string (Y-m-d) for both start and end columns
     * @return string|null the service's refreshed version stamp (updated_at ISO string)
     */
    public function apply(
        Service $service,
        Collection $existingCategories,
        Collection $existingAllocationsByWorkerAndCategory,
        array $categoryPayloads,
        array $categoryWorkerAllocations,
        int $serviceNameId,
        string $serviceType,
        ?string $description,
        string $distributionDate,
        int $editedBy,
    ): ?string {
        $totalQuantity = 0.0;
        $retainedCategoryIds = [];
        $retainedWorkerIdsByCategory = [];

        foreach ($categoryPayloads as $categoryKey => $payload) {
            $existingCategoryId = (int) ($payload['existing_id'] ?? 0);
            $category = $existingCategoryId > 0 ? $existingCategories->get($existingCategoryId) : null;

            if (! $category) {
                $category = new ServiceCategory;
            }

            $category->fill([
                'service_name_id' => $serviceNameId,
                'name' => $payload['name'],
                'quantity' => (float) $payload['quantity'],
                'unit' => $payload['unit'],
                'value' => 0,
                'sort_id' => $payload['sort_id'],
                'created_by' => $editedBy,
            ]);

            $service->categories()->save($category);

            if (method_exists($category, 'trashed') && $category->trashed()) {
                $category->restore();
            }

            $retainedCategoryIds[] = (int) $category->id;
            $retainedWorkerIdsByCategory[(int) $category->id] = array_map(
                'intval',
                array_keys($categoryWorkerAllocations[$categoryKey] ?? [])
            );
            $totalQuantity += (float) $payload['quantity'];

            foreach (($categoryWorkerAllocations[$categoryKey] ?? []) as $workerId => $allocatedQuantity) {
                $allocationKey = $category->id.':'.(int) $workerId;
                $existingAllocation = $existingAllocationsByWorkerAndCategory->get($allocationKey);

                if ($existingAllocation) {
                    $existingAllocation->fill([
                        'allocated_quantity' => (float) $allocatedQuantity,
                    ])->save();

                    continue;
                }

                $allocation = new ServiceWorkerAllocation;
                $allocation->fill([
                    'service_id' => $service->id,
                    'service_category_id' => $category->id,
                    'social_worker_id' => (int) $workerId,
                    'allocated_quantity' => (float) $allocatedQuantity,
                    'assigned_by_user_id' => $editedBy,
                ])->save();

                $existingAllocationsByWorkerAndCategory->put($allocationKey, $allocation);
            }
        }

        foreach ($retainedWorkerIdsByCategory as $categoryId => $workerIds) {
            $service->workerAllocations()
                ->where('service_category_id', $categoryId)
                ->when(
                    $workerIds !== [],
                    fn ($query) => $query->whereNotIn('social_worker_id', $workerIds)
                )
                ->delete();
        }

        $service->workerAllocations()
            ->when($retainedCategoryIds !== [], fn ($query) => $query->whereNotIn('service_category_id', $retainedCategoryIds))
            ->delete();

        $service->categories()
            ->when($retainedCategoryIds !== [], fn ($query) => $query->whereNotIn('id', $retainedCategoryIds))
            ->delete();

        $service->fill([
            'service_type' => $serviceType,
            'description' => $description,
            'total_quantity' => $totalQuantity,
            'distribution_start_date' => $distributionDate,
            'distribution_end_date' => $distributionDate,
        ])->save();

        $service->refreshFinancialTotals();
        $service->refreshDeliveryProgress();

        return $service->fresh()->updated_at?->toISOString();
    }
}
