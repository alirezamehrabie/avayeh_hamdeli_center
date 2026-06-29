<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;
use Illuminate\Support\Collection;

class ExpandedGuardianPeopleQuery
{
    public function get(?int $guardianId): Collection
    {
        if (! $guardianId) {
            return collect();
        }

        $guardian = Guardian::query()->find($guardianId);

        if (! $guardian) {
            return collect();
        }

        return $guardian->people()
            ->orderByDesc('created_at')
            ->get();
    }
}
