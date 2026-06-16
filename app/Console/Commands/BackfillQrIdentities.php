<?php

namespace App\Console\Commands;

use App\Models\Guardian;
use App\Models\Person;
use App\Services\QrIdentityService;
use Illuminate\Console\Command;

class BackfillQrIdentities extends Command
{
    protected $signature = 'qr-identities:backfill';

    protected $description = 'Create active QR identities for people and guardians that do not have one.';

    public function handle(QrIdentityService $qrIdentityService): int
    {
        $peopleCount = 0;
        $guardianCount = 0;

        Person::query()->chunkById(200, function ($people) use ($qrIdentityService, &$peopleCount): void {
            foreach ($people as $person) {
                $qrIdentityService->ensureActiveFor($person);
                $peopleCount++;
            }
        });

        Guardian::query()->chunkById(200, function ($guardians) use ($qrIdentityService, &$guardianCount): void {
            foreach ($guardians as $guardian) {
                $qrIdentityService->ensureActiveFor($guardian);
                $guardianCount++;
            }
        });

        $this->info("QR identities checked for {$peopleCount} people and {$guardianCount} guardians.");

        return self::SUCCESS;
    }
}
