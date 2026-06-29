<?php

namespace App\Services\People;

use App\Models\Person;

class SoftDeletePerson
{
    public function handle(Person $person, string $reason): bool
    {
        $person->forceFill([
            'deletion_reason' => $reason,
        ])->saveQuietly();

        return (bool) $person->delete();
    }
}
