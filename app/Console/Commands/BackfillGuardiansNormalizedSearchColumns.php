<?php

namespace App\Console\Commands;

use App\Services\Search\BackfillNormalizedSearchColumns;
use Illuminate\Console\Command;

class BackfillGuardiansNormalizedSearchColumns extends Command
{
    protected $signature = 'guardians:backfill-normalized-search
        {--chunk=500 : Number of households to process per batch}
        {--force-all : Recompute normalized search columns for all guardians}';

    protected $description = 'Backfill normalized and compact search columns for guardians using batched bulk updates.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $forceAll = (bool) $this->option('force-all');

        $updatedCount = app(BackfillNormalizedSearchColumns::class)->run(
            table: 'guardians',
            chunkSize: $chunkSize,
            forceAll: $forceAll,
        );

        $message = $forceAll
            ? "Normalized search columns backfilled for {$updatedCount} guardians."
            : "Normalized search columns backfilled for {$updatedCount} guardians missing normalized search columns.";

        $this->info($message);

        return self::SUCCESS;
    }
}
