<?php

namespace App\Services\Deliveries;

use App\Data\Deliveries\MiscServiceEditData;
use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceWorkerAllocation;
use App\Models\User;
use App\Queries\Deliveries\MiscServiceEditQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persists the category/allocation changes for an operator-defined "misc"
 * service edit.
 *
 * The edit() method owns the complete locked transaction and rechecks the
 * concurrency and usage constraints immediately before persistence. The lower
 * level apply() method remains available for focused persistence tests.
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
    public function __construct(private readonly MiscServiceEditQuery $query) {}

    /**
     * Execute a complete miscellaneous-service edit under one locked
     * transaction. The Livewire component may perform the same validations
     * earlier for immediate feedback, but this method is the authoritative
     * write boundary.
     */
    public function edit(MiscServiceEditData $data, User $user): ?string
    {
        return DB::transaction(function () use ($data, $user): ?string {
            $service = Service::query()
                ->createdByDistributionOperator()
                ->whereKey($data->serviceId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($user->can('edit-distribution-operator-service', $service), 403);
            $this->ensureVersionMatches($service, $data->expectedVersion);

            $existingCategories = $service->categories()
                ->withTrashed()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $existingAllocations = ServiceWorkerAllocation::query()
                ->where('service_id', $service->id)
                ->lockForUpdate()
                ->get();
            $deliveries = $service->deliveries()
                ->lockForUpdate()
                ->get();
            $assignments = GateEntryAssignment::query()
                ->where('service_id', $service->id)
                ->lockForUpdate()
                ->get();

            $service->setRelation('categories', $existingCategories
                ->filter(fn (ServiceCategory $category): bool => $category->deleted_at === null)
                ->values());

            $usedCategoryIds = $existingAllocations
                ->pluck('service_category_id')
                ->merge($deliveries->pluck('service_category_id'))
                ->merge($assignments->pluck('service_category_id'))
                ->map(fn ($categoryId): int => (int) $categoryId)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $categoryPool = $this->query->categoryPool(
                $service,
                $existingCategories->values(),
                $usedCategoryIds,
            );
            $usageByCategory = $this->query->usageByCategory(
                $service,
                $existingAllocations,
                $deliveries,
                $assignments,
            );

            $this->validateUsageConstraints(
                $data->workerGroups,
                $existingCategories,
                $categoryPool,
                $usageByCategory,
            );

            [$categoryPayloads, $categoryWorkerAllocations] = $this->buildPayloads(
                $data->workerGroups,
                $categoryPool,
            );

            return $this->apply(
                $service,
                $existingCategories,
                $existingAllocations->keyBy(
                    fn (ServiceWorkerAllocation $allocation): string => $allocation->service_category_id.':'.$allocation->social_worker_id
                ),
                $categoryPayloads,
                $categoryWorkerAllocations,
                (int) $service->service_name_id,
                $data->serviceType,
                $data->description,
                $data->distributionDate,
                (int) $user->id,
            );
        });
    }

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

    private function ensureVersionMatches(Service $service, ?string $expectedVersion): void
    {
        $currentVersion = $service->updated_at?->toISOString();

        if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
            throw ValidationException::withMessages([
                'miscWorkerGroups' => 'این خدمت توسط اپراتور دیگری تغییر کرده است. صفحه را تازه‌سازی کنید و دوباره تلاش کنید.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $workerGroups
     * @param  Collection<int, ServiceCategory>  $existingCategories
     * @param  array{by_name: array<string, ServiceCategory>}  $categoryPool
     * @param  array<int, array{social_worker_ids: array<int, int>, delivered_quantity: float, assigned_quantity: float}>  $usageByCategory
     */
    private function validateUsageConstraints(
        array $workerGroups,
        Collection $existingCategories,
        array $categoryPool,
        array $usageByCategory,
    ): void {
        $submittedByCategoryId = $this->submittedRowsByCategoryId($workerGroups, $categoryPool);
        $submittedCategoryIds = array_keys($submittedByCategoryId);
        $validCategoryIds = $existingCategories->keys()
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (array_diff($submittedCategoryIds, $validCategoryIds) !== []) {
            throw ValidationException::withMessages([
                'miscWorkerGroups' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
            ]);
        }

        foreach ($usageByCategory as $categoryId => $usage) {
            $category = $existingCategories->get($categoryId);

            if (! $category) {
                continue;
            }

            $submitted = $submittedByCategoryId[$categoryId] ?? null;

            if (! $submitted) {
                throw ValidationException::withMessages([
                    'miscWorkerGroups' => 'دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل حذف نیستند.',
                ]);
            }

            $usedWorkerIds = array_values(array_unique(array_map('intval', $usage['social_worker_ids'] ?? [])));
            $submittedWorkerIds = array_values(array_unique(array_map('intval', $submitted['social_worker_ids'] ?? [])));

            if ($usedWorkerIds !== [] && array_diff($usedWorkerIds, $submittedWorkerIds) !== []) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.social_worker_id' => 'مددکار دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل تغییر نیست.',
                ]);
            }

            if ((string) $submitted['unit'] !== (string) $category->unit) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.categories.'.$submitted['category_index'].'.unit' => 'واحد دسته‌بندی‌هایی که تحویل یا ورود برای آن‌ها ثبت شده است قابل تغییر نیست.',
                ]);
            }

            $minimumQuantity = max((float) $usage['delivered_quantity'], (float) $usage['assigned_quantity']);

            if ((float) $submitted['quantity'] + 0.00001 < $minimumQuantity) {
                throw ValidationException::withMessages([
                    $submitted['field_prefix'].'.categories.'.$submitted['category_index'].'.quantity' => 'مقدار این دسته‌بندی کمتر از مقدار استفاده‌شده در تحویل یا گیت ورود است.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $workerGroups
     * @param  array{by_name: array<string, ServiceCategory>}  $categoryPool
     * @return array<int, array<string, mixed>>
     */
    private function submittedRowsByCategoryId(array $workerGroups, array $categoryPool): array
    {
        $rows = [];

        foreach (array_values($workerGroups) as $groupIndex => $group) {
            $workerId = (int) ($group['social_worker_id'] ?? 0);

            foreach (array_values($group['categories'] ?? []) as $categoryIndex => $category) {
                $categoryId = $this->query->resolveCategoryId($category, $categoryPool);

                if ($categoryId <= 0) {
                    continue;
                }

                if (! isset($rows[$categoryId])) {
                    $rows[$categoryId] = [
                        'social_worker_id' => $workerId,
                        'social_worker_ids' => [],
                        'category_index' => $categoryIndex,
                        'field_prefix' => 'miscWorkerGroups.'.$groupIndex,
                        'quantity' => 0.0,
                        'unit' => (string) ($category['unit'] ?? ''),
                    ];
                }

                $rows[$categoryId]['social_worker_ids'][] = $workerId;
                $rows[$categoryId]['quantity'] += (float) ($category['quantity'] ?? 0);
            }
        }

        foreach ($rows as &$row) {
            $row['social_worker_ids'] = array_values(array_unique(array_map('intval', $row['social_worker_ids'] ?? [])));
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $workerGroups
     * @param  array{by_name: array<string, ServiceCategory>}  $categoryPool
     * @return array{
     *     0: array<string, array{existing_id: int|null, name: string, unit: string, quantity: float, sort_id: int}>,
     *     1: array<string, array<int, float>>
     * }
     */
    private function buildPayloads(array $workerGroups, array $categoryPool): array
    {
        $sortId = 1;
        $categoryPayloads = [];
        $categoryWorkerAllocations = [];

        foreach (array_values($workerGroups) as $group) {
            $workerId = (int) $group['social_worker_id'];

            foreach (array_values($group['categories']) as $categoryRow) {
                $categoryId = $this->query->resolveCategoryId($categoryRow, $categoryPool);
                $name = trim((string) $categoryRow['name']);
                $unit = (string) $categoryRow['unit'];
                $allocatedQuantity = (float) $categoryRow['quantity'];
                $categoryKey = $categoryId > 0
                    ? 'existing:'.$categoryId
                    : 'new:'.ServiceCategory::normalizeName($name);

                if (! isset($categoryPayloads[$categoryKey])) {
                    $categoryPayloads[$categoryKey] = [
                        'existing_id' => $categoryId > 0 ? $categoryId : null,
                        'name' => $name,
                        'unit' => $unit,
                        'quantity' => 0.0,
                        'sort_id' => $sortId++,
                    ];
                }

                $categoryPayloads[$categoryKey]['quantity'] += $allocatedQuantity;
                $categoryWorkerAllocations[$categoryKey][$workerId] =
                    ($categoryWorkerAllocations[$categoryKey][$workerId] ?? 0.0) + $allocatedQuantity;
            }
        }

        return [$categoryPayloads, $categoryWorkerAllocations];
    }
}
