<?php

namespace App\Queries\Deliveries;

use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read-side operations and category identity rules for the miscellaneous
 * service edit workflow.
 */
final class MiscServiceEditQuery
{
    public function findEditableOrFail(int $serviceId, User $user): Service
    {
        $service = Service::query()
            ->with(['socialWorkers', 'categories'])
            ->createdByDistributionOperator()
            ->findOrFail($serviceId);

        abort_unless($user->can('edit-distribution-operator-service', $service), 403);

        return $service;
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    public function allCategories(Service $service): Collection
    {
        return $service->categories()
            ->withTrashed()
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, ServiceCategory>|null  $categories
     * @param  array<int, int>|null  $usedCategoryIds
     * @return array{by_name: array<string, ServiceCategory>}
     */
    public function categoryPool(
        Service $service,
        ?Collection $categories = null,
        ?array $usedCategoryIds = null,
    ): array {
        $categories ??= $this->allCategories($service);
        $usedCategoryIds ??= ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->pluck('service_category_id')
            ->merge($service->deliveries()->pluck('service_category_id'))
            ->merge(GateEntryAssignment::query()
                ->where('service_id', $service->id)
                ->pluck('service_category_id'))
            ->map(fn ($categoryId): int => (int) $categoryId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usedCategoryIdLookup = array_fill_keys($usedCategoryIds, true);
        $byName = [];

        foreach ($categories as $category) {
            $normalizedName = ServiceCategory::normalizeName((string) $category->name);

            if ($normalizedName === '') {
                continue;
            }

            $existing = $byName[$normalizedName] ?? null;

            if (! $existing instanceof ServiceCategory
                || $this->shouldPreferCategoryCandidate($category, $existing, $usedCategoryIdLookup)) {
                $byName[$normalizedName] = $category;
            }
        }

        return ['by_name' => $byName];
    }

    public function resolveCategoryId(array $category, array $categoryPool): int
    {
        $categoryId = (int) ($category['id'] ?? 0);

        if ($categoryId > 0) {
            return $categoryId;
        }

        $normalizedName = ServiceCategory::normalizeName((string) ($category['name'] ?? ''));

        if ($normalizedName === '') {
            return 0;
        }

        $resolvedCategory = $categoryPool['by_name'][$normalizedName] ?? null;

        return $resolvedCategory instanceof ServiceCategory ? (int) $resolvedCategory->id : 0;
    }

    public function sharedCategoryKey(array $category, array $categoryPool): ?string
    {
        $categoryId = $this->resolveCategoryId($category, $categoryPool);

        if ($categoryId > 0) {
            return 'id:'.$categoryId;
        }

        $normalizedName = ServiceCategory::normalizeName((string) ($category['name'] ?? ''));

        return $normalizedName === '' ? null : 'name:'.$normalizedName;
    }

    /**
     * @param  Collection<int, ServiceWorkerAllocation>|null  $allocations
     * @param  Collection<int, mixed>|null  $deliveries
     * @param  Collection<int, GateEntryAssignment>|null  $assignments
     * @return array<int, array{social_worker_ids: array<int, int>, delivered_quantity: float, assigned_quantity: float}>
     */
    public function usageByCategory(
        Service $service,
        ?Collection $allocations = null,
        ?Collection $deliveries = null,
        ?Collection $assignments = null,
    ): array {
        $allocatedWorkerIds = ($allocations ?? ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->get(['service_category_id', 'social_worker_id']))
            ->groupBy('service_category_id')
            ->map(fn (Collection $rows): array => $rows
                ->pluck('social_worker_id')
                ->map(fn ($workerId): int => (int) $workerId)
                ->unique()
                ->values()
                ->all());

        $deliveredByCategory = $deliveries === null
            ? $service->deliveries()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->groupBy('service_category_id')
                ->pluck('delivered_quantity', 'service_category_id')
                ->map(fn ($quantity): float => (float) $quantity)
            : $deliveries
                ->groupBy('service_category_id')
                ->map(fn (Collection $rows): float => (float) $rows->sum('delivered_quantity'));
        $assignedByCategory = $assignments === null
            ? GateEntryAssignment::query()
                ->where('service_id', $service->id)
                ->select('service_category_id')
                ->selectRaw('COUNT(*) as assigned_quantity')
                ->groupBy('service_category_id')
                ->pluck('assigned_quantity', 'service_category_id')
                ->map(fn ($quantity): float => (float) $quantity)
            : $assignments
                ->groupBy('service_category_id')
                ->map(fn (Collection $rows): float => (float) $rows->count());

        return $deliveredByCategory
            ->keys()
            ->merge($assignedByCategory->keys())
            ->map(fn ($categoryId): int => (int) $categoryId)
            ->unique()
            ->mapWithKeys(fn (int $categoryId): array => [
                $categoryId => [
                    'social_worker_ids' => $allocatedWorkerIds[$categoryId] ?? [],
                    'delivered_quantity' => (float) ($deliveredByCategory[$categoryId] ?? 0),
                    'assigned_quantity' => (float) ($assignedByCategory[$categoryId] ?? 0),
                ],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, ServiceCategory>|null  $categories
     * @param  array<int, array<string, mixed>>|null  $usageByCategory
     * @return array{ids: array<int, int>, names: array<string, bool>}
     */
    public function usedCategoryUnitLocks(
        Service $service,
        ?Collection $categories = null,
        ?array $usageByCategory = null,
    ): array {
        $categories ??= $this->allCategories($service);
        $usageByCategory ??= $this->usageByCategory($service);
        $usedCategoryIds = array_map('intval', array_keys($usageByCategory));

        if ($usedCategoryIds === []) {
            return ['ids' => [], 'names' => []];
        }

        $names = $categories
            ->whereIn('id', $usedCategoryIds)
            ->pluck('name')
            ->reduce(function (array $carry, $name): array {
                $normalized = ServiceCategory::normalizeName((string) $name);

                if ($normalized !== '') {
                    $carry[$normalized] = true;
                }

                return $carry;
            }, []);

        return [
            'ids' => array_values($usedCategoryIds),
            'names' => $names,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function workerGroups(Service $service): array
    {
        $categories = $service->categories()->ordered()->get();
        $allocationsByCategory = $service->workerAllocations()
            ->with('socialWorker.district:id,name')
            ->get()
            ->groupBy('service_category_id');

        $groups = [];
        $groupIndexByWorker = [];

        foreach ($categories as $category) {
            foreach ($allocationsByCategory->get($category->id, collect()) as $allocation) {
                $worker = $allocation->socialWorker;

                if (! $worker) {
                    continue;
                }

                $workerId = (int) $worker->id;

                if (! array_key_exists($workerId, $groupIndexByWorker)) {
                    $groupIndexByWorker[$workerId] = count($groups);
                    $groups[] = [
                        'uid' => 'group-'.$workerId,
                        'social_worker_id' => $workerId,
                        'worker_query' => trim($worker->full_name.' - کد '.$worker->worker_code),
                        'worker_search' => '',
                        'worker_code' => $worker->worker_code ? (string) $worker->worker_code : '-',
                        'worker_display' => $this->formatWorkerDisplay($worker),
                        'locked' => true,
                        'is_existing' => true,
                        'categories' => [],
                    ];
                }

                $groups[$groupIndexByWorker[$workerId]]['categories'][] = [
                    'id' => (int) $category->id,
                    'name' => $category->name,
                    'quantity' => $this->formatDecimal($allocation->allocated_quantity),
                    'unit' => $category->unit,
                ];
            }
        }

        return $groups;
    }

    private function shouldPreferCategoryCandidate(
        ServiceCategory $candidate,
        ServiceCategory $existing,
        array $usedCategoryIdLookup,
    ): bool {
        $candidateIsUsed = isset($usedCategoryIdLookup[(int) $candidate->id]);
        $existingIsUsed = isset($usedCategoryIdLookup[(int) $existing->id]);

        if ($candidateIsUsed !== $existingIsUsed) {
            return $candidateIsUsed;
        }

        $candidateIsActive = $candidate->deleted_at === null;
        $existingIsActive = $existing->deleted_at === null;

        if ($candidateIsActive !== $existingIsActive) {
            return $candidateIsActive;
        }

        return (int) $candidate->id > (int) $existing->id;
    }

    private function formatWorkerDisplay(SocialWorker $worker): string
    {
        $name = trim($worker->full_name) ?: 'مددکار بدون نام';
        $district = trim((string) ($worker->district?->name ?? ''));

        return $district !== '' ? $name.' - '.$district : $name;
    }

    private function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }
}
