<?php

namespace App\Services\Guardians;

use App\Models\Guardian;
use Illuminate\Support\Facades\DB;

class SoftDeleteGuardianFamily
{
    public function handleById(int $guardianId, string $reason): Guardian
    {
        $guardian = Guardian::query()->findOrFail($guardianId);

        $this->handle($guardian, $reason);

        return $guardian;
    }

    public function handle(Guardian $guardian, string $reason): bool
    {
        return (bool) DB::transaction(function () use ($guardian, $reason) {
            foreach ($guardian->people()->get() as $person) {
                $person->forceFill([
                    'deletion_reason' => $reason,
                ])->saveQuietly();

                $person->delete();
            }

            $guardian->forceFill([
                'deletion_reason' => $reason,
            ])->saveQuietly();

            return $guardian->delete();
        });
    }
}
