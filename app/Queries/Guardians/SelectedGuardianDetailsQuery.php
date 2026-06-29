<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;

class SelectedGuardianDetailsQuery
{
    public function find(?int $guardianId): ?Guardian
    {
        if (! $guardianId) {
            return null;
        }

        return Guardian::with([
            'socialWorker',
            'occupation',
            'insuranceType',
            'vehicleType',
            'residence.residenceStatus',
            'residence.district',
            'people.familyStatus.guardianRelationType',
            'people.harmTypes:id,title',
        ])
            ->withCount('people')
            ->find($guardianId);
    }
}
