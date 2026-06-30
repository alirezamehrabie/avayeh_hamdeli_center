<?php

namespace App\Queries\People;

use App\Models\Person;

class SelectedPersonDetailsQuery
{
    public function find(?int $personId): ?Person
    {
        if (! $personId) {
            return null;
        }

        return Person::with([
            'guardian.occupation',
            'guardian.jobType',
            'guardian.residence',
            'guardian.socialWorker',
            'education.educationLevel',
            'education.educationDegreeLevel',
            'supportCoverage.organization',
            'disabilityType',
            'familyStatus.guardianRelationType',
            'skills',
            'harmTypes',
            'needsLevel.levelType',
        ])->find($personId);
    }
}
