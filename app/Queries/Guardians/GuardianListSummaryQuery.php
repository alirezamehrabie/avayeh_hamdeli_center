<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;

class GuardianListSummaryQuery
{
    /**
     * @return array{totalGuardians: int, totalCenterMembers: int}
     */
    public function get(): array
    {
        return [
            'totalGuardians' => Guardian::count(),
            'totalCenterMembers' => (int) Guardian::query()->sum('children_in_house'),
        ];
    }
}
