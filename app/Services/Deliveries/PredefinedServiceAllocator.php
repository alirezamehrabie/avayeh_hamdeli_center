<?php

namespace App\Services\Deliveries;

use App\Models\Service;
use App\Models\ServiceWorkerAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persists predefined-service worker allocations for the distribution operator
 * batch workflow.
 *
 * Business rules (shared with the batch creator UI, enforced here so they hold
 * regardless of caller):
 *  - each submitted worker group must carry at least one positive allocation row,
 *  - category stock is validated inside a transaction with row locks so several
 *    workers sharing the same pool can never over-draw it,
 *  - an existing allocation may only be overwritten by the operator who created
 *    it — another operator's allocation is protected.
 *
 * The thrown {@see ValidationException} keys mirror the Livewire
 * `predefinedWorkerGroups.*` property paths so the component surfaces field
 * errors without any translation layer.
 */
class PredefinedServiceAllocator
{
    /**
     * @param  array<int, array<string, mixed>>  $predefinedWorkerGroups  validated worker groups from the batch form
     * @return int the number of worker groups persisted
     *
     * @throws ValidationException
     */
    public function allocate(Service $service, array $predefinedWorkerGroups, int $assignedByUserId): int
    {
        $groups = $this->flattenGroups($predefinedWorkerGroups);

        $this->guardEveryGroupHasPositiveRow($groups);

        $categoryIds = $groups
            ->flatMap(fn (array $group) => $group['rows']->pluck('category_id'))
            ->unique()
            ->values();

        DB::transaction(function () use ($service, $groups, $categoryIds, $assignedByUserId): void {
            $categories = $service->categories()->lockForUpdate()->get()->keyBy('id');
            $lockedAllocations = ServiceWorkerAllocation::query()
                ->where('service_id', $service->id)
                ->whereIn('service_category_id', $categoryIds->all())
                ->lockForUpdate()
                ->get();
            // Base stock already consumed per category by allocations that belong
            // to workers NOT part of this submit; the submitted workers share the
            // remaining stock, so their own current rows are excluded here.
            $submittedWorkerIds = $groups->pluck('worker_id')->map(fn ($id): int => (int) $id)->all();
            $baseAllocatedByCategory = $lockedAllocations
                ->whereNotIn('social_worker_id', $submittedWorkerIds)
                ->groupBy('service_category_id')
                ->map(fn (Collection $allocations): float => (float) $allocations->sum(fn (ServiceWorkerAllocation $allocation) => (float) $allocation->allocated_quantity));
            $existingByWorkerAndCategory = $lockedAllocations
                ->keyBy(fn (ServiceWorkerAllocation $allocation): string => $allocation->service_category_id.':'.$allocation->social_worker_id);

            // Running total of what the submitted workers claim per category, so
            // the shared pool can never be over-drawn across several workers.
            $claimedByCategory = [];

            foreach ($groups as $group) {
                $workerId = $group['worker_id'];

                foreach ($group['rows'] as $row) {
                    $category = $categories->get($row['category_id']);

                    if (! $category) {
                        throw ValidationException::withMessages([
                            "predefinedWorkerGroups.{$group['index']}.allocations" => 'دسته‌بندی انتخاب‌شده متعلق به خدمت انتخاب‌شده نیست.',
                        ]);
                    }

                    $allocation = $existingByWorkerAndCategory->get($category->id.':'.$workerId);

                    if ($allocation && (int) $allocation->assigned_by_user_id !== $assignedByUserId) {
                        throw ValidationException::withMessages([
                            "predefinedWorkerGroups.{$group['index']}.social_worker_id" => 'این مددکار قبلاً توسط اپراتور دیگری برای این دسته‌بندی تخصیص یافته است.',
                        ]);
                    }

                    $alreadyClaimed = (float) ($claimedByCategory[$category->id] ?? 0);
                    $remainingAssignable = max(
                        0,
                        (float) $category->quantity
                            - (float) ($baseAllocatedByCategory[$category->id] ?? 0)
                            - $alreadyClaimed
                    );

                    if ($row['quantity'] > $remainingAssignable) {
                        throw ValidationException::withMessages([
                            "predefinedWorkerGroups.{$group['index']}.allocations.{$category->id}" => 'مقدار تخصیص نمی‌تواند از موجودی دسته‌بندی بیشتر باشد.',
                        ]);
                    }

                    $claimedByCategory[$category->id] = $alreadyClaimed + $row['quantity'];

                    if (! $allocation) {
                        $allocation = new ServiceWorkerAllocation;
                        $allocation->fill([
                            'service_id' => $service->id,
                            'service_category_id' => $category->id,
                            'social_worker_id' => $workerId,
                        ]);
                    }

                    $allocation->fill([
                        'allocated_quantity' => $row['quantity'],
                        'assigned_by_user_id' => $assignedByUserId,
                    ])->save();
                }
            }
        });

        $service->refreshDeliveryProgress();

        return $groups->count();
    }

    /**
     * Flatten the worker groups into (worker, category, quantity) rows, dropping
     * any zero/blank allocation so no worker is persisted with an empty row.
     *
     * @param  array<int, array<string, mixed>>  $predefinedWorkerGroups
     * @return Collection<int, array{index: int, worker_id: int, rows: Collection<int, array{category_id: int, quantity: float}>}>
     */
    protected function flattenGroups(array $predefinedWorkerGroups): Collection
    {
        return collect($predefinedWorkerGroups)
            ->map(function (array $group, int $groupIndex): array {
                $workerId = (int) ($group['social_worker_id'] ?? 0);
                $rows = collect($group['allocations'] ?? [])
                    ->map(fn ($quantity, $categoryId): array => [
                        'category_id' => (int) $categoryId,
                        'quantity' => (float) $quantity,
                    ])
                    ->filter(fn (array $row): bool => $row['quantity'] > 0)
                    ->values();

                return [
                    'index' => $groupIndex,
                    'worker_id' => $workerId,
                    'rows' => $rows,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{index: int, worker_id: int, rows: Collection<int, mixed>}>  $groups
     *
     * @throws ValidationException
     */
    protected function guardEveryGroupHasPositiveRow(Collection $groups): void
    {
        foreach ($groups as $group) {
            if ($group['rows']->isEmpty()) {
                throw ValidationException::withMessages([
                    "predefinedWorkerGroups.{$group['index']}.allocations" => 'حداقل برای یک دسته‌بندی مقدار تخصیص وارد کنید.',
                ]);
            }
        }

        if ($groups->every(fn (array $group): bool => $group['rows']->isEmpty())) {
            throw ValidationException::withMessages([
                'predefinedWorkerGroups' => 'حداقل برای یک دسته‌بندی مقدار تخصیص وارد کنید.',
            ]);
        }
    }
}
