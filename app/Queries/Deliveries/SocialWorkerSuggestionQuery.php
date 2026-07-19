<?php

namespace App\Queries\Deliveries;

use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Builds the social-worker autocomplete suggestions for the distribution
 * operator allocation flows.
 *
 * Extracted from ServiceBatchCreator, where the same worker projection,
 * open-allocation count and prefix search were duplicated (once for the search
 * result set and once to re-hydrate a preselected worker missing from it).
 *
 * The query only counts allocations tied to services that are still handing out
 * stock (approved / in distribution), so the "open allocations" and cumulative
 * allocated-quantity figures reflect live workload only.
 */
class SocialWorkerSuggestionQuery
{
    /**
     * Service statuses whose allocations still count toward a worker's live
     * workload.
     *
     * @var array<int, string>
     */
    public const ACTIVE_SERVICE_STATUSES = ['approved', 'in_distribution'];

    /**
     * Search active workers by name / code / mobile prefix, capped at $limit.
     *
     * @return EloquentCollection<int, SocialWorker>
     */
    public function search(string $term, int $limit = 10): EloquentCollection
    {
        return $this->baseQuery()
            ->where(function (Builder $workerQuery) use ($term): void {
                $workerQuery->where('first_name', 'like', $term.'%')
                    ->orWhere('last_name', 'like', $term.'%')
                    ->orWhere('worker_code', 'like', $term.'%')
                    ->orWhere('mobile', 'like', $term.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$term.'%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$term.'%']);
            })
            ->orderBy('worker_code')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch a single worker with the same projection as {@see search()} so a
     * preselected worker can be surfaced even when it falls outside the search
     * result set.
     */
    public function find(int $workerId): ?SocialWorker
    {
        return $this->baseQuery()->find($workerId);
    }

    /**
     * Sum each worker's currently open allocated quantity, keyed by worker id.
     *
     * @param  array<int, int>  $workerIds
     * @return Collection<int, float>
     */
    public function openAllocatedQuantities(array $workerIds): Collection
    {
        if ($workerIds === []) {
            return collect();
        }

        return ServiceWorkerAllocation::query()
            ->select('social_worker_id')
            ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
            ->whereIn('social_worker_id', $workerIds)
            ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                ->whereIn('status', self::ACTIVE_SERVICE_STATUSES))
            ->groupBy('social_worker_id')
            ->pluck('allocated_quantity', 'social_worker_id');
    }

    /**
     * Sum a single worker's currently open allocated quantity.
     */
    public function openAllocatedQuantityFor(int $workerId): float
    {
        return (float) ServiceWorkerAllocation::query()
            ->where('social_worker_id', $workerId)
            ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                ->whereIn('status', self::ACTIVE_SERVICE_STATUSES))
            ->sum('allocated_quantity');
    }

    /**
     * The shared worker projection: identity columns, district, coverage
     * counters and a live "open allocations" count.
     */
    protected function baseQuery(): Builder
    {
        return SocialWorker::query()
            ->with('district:id,name')
            ->withCount([
                'workerAllocations as open_allocations_count' => fn (Builder $allocationQuery) => $allocationQuery
                    ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery
                        ->whereIn('status', self::ACTIVE_SERVICE_STATUSES)),
            ])
            ->select([
                'id',
                'first_name',
                'last_name',
                'worker_code',
                'mobile',
                'district_id',
                'covered_people_count',
                'covered_households_count',
                'covered_children_count',
            ]);
    }
}
