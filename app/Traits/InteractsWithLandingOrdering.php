<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait InteractsWithLandingOrdering
{
    /**
     * Swap the given row with its previous sibling inside $scope (sort_id ASC,
     * nulls last). Done through two direct updates inside one transaction with
     * a row lock on the neighbor, so concurrent reorderings can't interleave.
     */
    protected function moveLandingRowUp(Builder $scope, Model $row): void
    {
        DB::transaction(function () use ($scope, $row): void {
            $previous = $this->lockLandingPrevious($scope, $row);

            if ($previous) {
                $this->swapLandingSortValues($row, $previous);
            }
        });
    }

    protected function moveLandingRowDown(Builder $scope, Model $row): void
    {
        DB::transaction(function () use ($scope, $row): void {
            $next = $this->lockLandingNext($scope, $row);

            if ($next) {
                $this->swapLandingSortValues($row, $next);
            }
        });
    }

    protected function swapLandingSortValues(Model $row, Model $other): void
    {
        $rowSort = $row->sort_id;
        $otherSort = $other->sort_id;

        $row->newQuery()->whereKey($row->getKey())->update(['sort_id' => $otherSort]);
        $other->newQuery()->whereKey($other->getKey())->update(['sort_id' => $rowSort]);
    }

    private function lockLandingPrevious(Builder $scope, Model $row): ?Model
    {
        $currentSort = $row->sort_id ?? PHP_INT_MAX;

        return $scope
            ->whereKeyNot($row->getKey())
            ->where(fn ($query) => $query
                ->where('sort_id', '<', $currentSort)
                ->orWhereRaw('(sort_id IS NULL AND id < ?)', [$row->getKey()]))
            ->orderByDesc('sort_id')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    private function lockLandingNext(Builder $scope, Model $row): ?Model
    {
        $currentSort = $row->sort_id ?? PHP_INT_MAX;

        return $scope
            ->whereKeyNot($row->getKey())
            ->where(fn ($query) => $query
                ->where('sort_id', '>', $currentSort)
                ->orWhereRaw('(sort_id IS NULL AND id > ?)', [$row->getKey()]))
            ->orderBy('sort_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }
}
