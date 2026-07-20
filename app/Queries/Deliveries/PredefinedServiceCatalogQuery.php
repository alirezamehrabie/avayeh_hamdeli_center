<?php

namespace App\Queries\Deliveries;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceWorkerAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-side queries for services that can receive distribution-operator
 * allocations.
 *
 * This class deliberately does not persist allocations. Persistence and its
 * locking rules remain in PredefinedServiceAllocator and MiscServiceEditor.
 */
final class PredefinedServiceCatalogQuery
{
    /**
     * @return Collection<int, Service>
     */
    public function search(string $search, ?int $selectedServiceId = null): Collection
    {
        $services = $this->eligibleServiceQuery()
            ->when(trim($search) !== '', function (Builder $query) use ($search): void {
                $term = trim($search);

                $query->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery->where('code', 'like', '%'.$term.'%')
                        ->orWhere('name', 'like', '%'.$term.'%')
                        ->orWhereHas('serviceName', fn (Builder $serviceNameQuery) => $serviceNameQuery
                            ->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->latest()
            ->limit(25)
            ->get();

        if ($selectedServiceId > 0 && ! $services->contains('id', $selectedServiceId)) {
            $selectedService = $this->findSelectableOrFail($selectedServiceId);
            $services->prepend($selectedService);
        }

        return $services->unique('id')->values();
    }

    public function findSelectableOrFail(int $serviceId): Service
    {
        return $this->selectableServiceQuery()->findOrFail($serviceId);
    }

    /**
     * @return array<int, float>
     */
    public function allocatedQuantitiesByCategory(Service $service): array
    {
        return ServiceWorkerAllocation::query()
            ->select('service_category_id')
            ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
            ->where('service_id', $service->id)
            ->groupBy('service_category_id')
            ->pluck('allocated_quantity', 'service_category_id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->all();
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return array<int, array<string, mixed>>
     */
    public function options(Collection $services, ?int $selectedServiceId = null): array
    {
        if ($services->isEmpty()) {
            return [];
        }

        $categoryIds = $services
            ->flatMap(fn (Service $service) => $service->categories->pluck('id'))
            ->map(fn ($categoryId): int => (int) $categoryId)
            ->filter()
            ->values();

        $allocatedByCategory = $categoryIds->isEmpty()
            ? collect()
            : ServiceWorkerAllocation::query()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(allocated_quantity), 0) as allocated_quantity')
                ->whereIn('service_category_id', $categoryIds->all())
                ->groupBy('service_category_id')
                ->pluck('allocated_quantity', 'service_category_id');

        return $services
            ->map(function (Service $service) use ($allocatedByCategory): array {
                $categories = $service->categories->values();
                $remainingAssignable = $categories->sum(function (ServiceCategory $category) use ($allocatedByCategory): float {
                    $allocated = (float) ($allocatedByCategory[$category->id] ?? 0);

                    return max(0, (float) $category->quantity - $allocated);
                });

                return [
                    'id' => (int) $service->id,
                    'code' => (string) $service->code,
                    'name' => $service->name ?: ($service->serviceName?->name ?? 'بدون عنوان'),
                    'status' => Service::STATUS_OPTIONS[$service->status] ?? $service->status,
                    'remaining' => $remainingAssignable,
                    'remainingLabel' => number_format($remainingAssignable, 2),
                    'categoriesCount' => $categories->count(),
                    'categorySummary' => $categories->pluck('name')->filter()->take(4)->implode('، '),
                ];
            })
            ->filter(fn (array $service): bool => $service['remaining'] > 0
                || (int) $service['id'] === (int) $selectedServiceId)
            ->values()
            ->all();
    }

    protected function eligibleServiceQuery(): Builder
    {
        return $this->selectableServiceQuery()
            ->whereRaw('(select coalesce(sum(quantity), 0) from service_categories where service_categories.service_id = services.id and service_categories.deleted_at is null) > (select coalesce(sum(allocated_quantity), 0) from service_social_worker where service_social_worker.service_id = services.id)');
    }

    protected function selectableServiceQuery(): Builder
    {
        return Service::query()
            ->with([
                'serviceName',
                'categories' => fn ($query) => $query->orderBy('sort_id')->orderBy('id'),
            ])
            ->whereIn('status', ['approved', 'in_distribution'])
            ->where(function (Builder $query): void {
                $query->whereNull('created_by')
                    ->orWhereDoesntHave('creator', fn (Builder $creatorQuery) => $creatorQuery
                        ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR));
            });
    }
}
