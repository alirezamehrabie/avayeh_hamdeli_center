<?php

namespace App\Queries\People;

use App\Models\Person;

class TrackingPersonDetailsQuery
{
    public function find(?int $personId): ?Person
    {
        if (! $personId) {
            return null;
        }

        return Person::with(['creator:id,name', 'updater:id,name'])->find($personId);
    }
}
