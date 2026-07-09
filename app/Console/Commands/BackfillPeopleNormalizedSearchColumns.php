<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPeopleNormalizedSearchColumns extends Command
{
    protected $signature = 'people:backfill-normalized-search
        {--chunk=500 : Number of people to process per batch}
        {--force-all : Recompute normalized search columns for all people}';

    protected $description = 'Backfill normalized search columns for people using batched bulk updates.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $forceAll = (bool) $this->option('force-all');
        $updatedCount = 0;

        $query = DB::table('people')
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('id');

        if (! $forceAll) {
            $query->where(function ($builder): void {
                $builder
                    ->whereNull('normalized_first_name')
                    ->orWhereNull('normalized_last_name')
                    ->orWhereNull('normalized_full_name');
            });
        }

        $query->chunkById($chunkSize, function ($people) use (&$updatedCount): void {
            if ($people->isEmpty()) {
                return;
            }

            $bindings = [];
            $firstNameCase = 'CASE id';
            $lastNameCase = 'CASE id';
            $fullNameCase = 'CASE id';
            $ids = [];

            foreach ($people as $person) {
                $normalizedFirstName = Person::normalizeSearchText((string) $person->first_name);
                $normalizedLastName = Person::normalizeSearchText((string) $person->last_name);
                $normalizedFullName = trim($normalizedFirstName.' '.$normalizedLastName);

                $firstNameCase .= ' WHEN ? THEN ?';
                $bindings[] = $person->id;
                $bindings[] = $normalizedFirstName;

                $lastNameCase .= ' WHEN ? THEN ?';
                $bindings[] = $person->id;
                $bindings[] = $normalizedLastName;

                $fullNameCase .= ' WHEN ? THEN ?';
                $bindings[] = $person->id;
                $bindings[] = $normalizedFullName;

                $ids[] = $person->id;
            }

            $firstNameCase .= ' END';
            $lastNameCase .= ' END';
            $fullNameCase .= ' END';

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "UPDATE people
                SET normalized_first_name = {$firstNameCase},
                    normalized_last_name = {$lastNameCase},
                    normalized_full_name = {$fullNameCase}
                WHERE id IN ({$placeholders})";

            DB::update($sql, [...$bindings, ...$ids]);
            $updatedCount += count($ids);
        }, 'id');

        $message = $forceAll
            ? "Normalized search columns backfilled for {$updatedCount} people."
            : "Normalized search columns backfilled for {$updatedCount} people missing normalized search columns.";

        $this->info($message);

        return self::SUCCESS;
    }
}
