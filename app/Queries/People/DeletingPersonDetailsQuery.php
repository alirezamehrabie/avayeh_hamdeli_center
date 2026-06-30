<?php

namespace App\Queries\People;

use App\Models\Person;

class DeletingPersonDetailsQuery
{
    public function find(?int $personId): ?Person
    {
        if (! $personId) {
            return null;
        }

        return Person::query()->find($personId);
    }
}
