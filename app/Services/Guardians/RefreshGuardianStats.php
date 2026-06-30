<?php

namespace App\Services\Guardians;

use App\Models\Guardian;

class RefreshGuardianStats
{
    public function handle(): void
    {
        Guardian::refreshAllChildrenInHouse();
    }
}
