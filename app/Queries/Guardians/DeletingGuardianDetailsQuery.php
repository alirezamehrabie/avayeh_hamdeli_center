<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;

class DeletingGuardianDetailsQuery
{
    public function findOrFail(int $guardianId): Guardian
    {
        return Guardian::query()
            ->withCount('people')
            ->findOrFail($guardianId);
    }

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
