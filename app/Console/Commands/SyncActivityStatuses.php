<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class SyncActivityStatuses extends Command
{
    protected $signature = 'activities:sync-statuses';

    protected $description = 'Move scheduled activities to ongoing when their start time is reached.';

    public function handle(): int
    {
        $updatedCount = Activity::query()
            ->where('status', 'scheduled')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->update(['status' => 'ongoing']);

        $this->info("Updated {$updatedCount} scheduled activities.");

        return self::SUCCESS;
    }
}
