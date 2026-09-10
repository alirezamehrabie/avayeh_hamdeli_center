<?php

namespace App\Console\Commands;

use App\Services\Search\BackfillNormalizedSearchColumns;
use Illuminate\Console\Command;

class BackfillPeopleNormalizedSearchColumns extends Command
{
    protected $signature = 'people:backfill-normalized-search
        {--chunk=500 : Number of people to process per batch}
        {--force-all : Recompute normalized search columns for all people}';

    protected $description = 'Backfill normalized and compact search columns for people using batched bulk updates.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $forceAll = (bool) $this->option('force-all');

        $updatedCount = app(BackfillNormalizedSearchColumns::class)->run(
            table: 'people',
            chunkSize: $chunkSize,
            forceAll: $forceAll,
        );

        $message = $forceAll
            ? "Normalized search columns backfilled for {$updatedCount} people."
            : "Normalized search columns backfilled for {$updatedCount} people missing normalized search columns.";

        $this->info($message);

        return self::SUCCESS;
    }
}
