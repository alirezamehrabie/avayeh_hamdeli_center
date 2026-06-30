<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;

class DeletingGuardianDetailsQuery
{
    public function find(?int $guardianId): ?Guardian
    {
        if (! $guardianId) {
            return null;
        }

        return Guardian::query()
            ->withCount('people')
            ->find($guardianId);
    }
}
