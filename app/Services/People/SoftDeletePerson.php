<?php

namespace App\Services\People;

use App\Models\Person;

class SoftDeletePerson
{
    public function handleById(int $personId, string $reason): bool
    {
        return $this->handle(Person::query()->findOrFail($personId), $reason);
    }

    public function handle(Person $person, string $reason): bool
    {
        $person->forceFill([
            'deletion_reason' => $reason,
        ])->saveQuietly();

        return (bool) $person->delete();
    }
}
