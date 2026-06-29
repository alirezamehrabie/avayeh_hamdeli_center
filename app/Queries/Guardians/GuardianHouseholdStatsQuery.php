<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;
use Illuminate\Support\Collection;

class GuardianHouseholdStatsQuery
{
    public function byHouseholdSize(): Collection
    {
        return Guardian::query()
            ->select(['id', 'national_code', 'first_name', 'last_name', 'children_in_house'])
            ->orderBy('children_in_house')
            ->orderBy('national_code')
            ->get()
            ->groupBy(fn (Guardian $guardian) => (int) ($guardian->children_in_house ?? 0))
            ->map(function (Collection $guardians, int|string $householdSize): array {
                return [
                    'household_size' => (int) $householdSize,
                    'households_count' => $guardians->count(),
                    'national_codes' => $guardians->pluck('national_code')->filter()->values()->all(),
                ];
            })
            ->values();
    }

    public function byCoverageCount(): Collection
    {
        return Guardian::query()
            ->select(['id', 'national_code', 'first_name', 'last_name', 'children_count'])
            ->orderBy('children_count')
            ->orderBy('national_code')
            ->get()
            ->groupBy(fn (Guardian $guardian) => (int) ($guardian->children_count ?? 0))
            ->map(function (Collection $guardians, int|string $coverageCount): array {
                return [
                    'coverage_count' => (int) $coverageCount,
                    'households_count' => $guardians->count(),
                    'national_codes' => $guardians->pluck('national_code')->filter()->values()->all(),
                ];
            })
            ->values();
    }
}
