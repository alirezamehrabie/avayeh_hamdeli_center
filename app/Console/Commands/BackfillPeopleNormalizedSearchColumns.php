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

    protected $description = 'Backfill normalized and compact search columns for people using batched bulk updates.';

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
                    ->orWhereNull('normalized_full_name')
                    ->orWhereNull('compact_first_name')
                    ->orWhereNull('compact_last_name')
                    ->orWhereNull('compact_full_name');
            });
        }

        $query->chunkById($chunkSize, function ($people) use (&$updatedCount): void {
            if ($people->isEmpty()) {
                return;
            }

            // Bindings must be grouped per column (all pairs of one CASE, then
            // the next), not interleaved per row, or the placeholders of each
            // CASE expression consume the wrong values.
            $firstNameCase = 'CASE id';
            $lastNameCase = 'CASE id';
            $fullNameCase = 'CASE id';
            $compactFirstNameCase = 'CASE id';
            $compactLastNameCase = 'CASE id';
            $compactFullNameCase = 'CASE id';
            $firstNameBindings = [];
            $lastNameBindings = [];
            $fullNameBindings = [];
            $compactFirstNameBindings = [];
            $compactLastNameBindings = [];
            $compactFullNameBindings = [];
            $ids = [];

            foreach ($people as $person) {
                $normalizedFirstName = Person::normalizeSearchText((string) $person->first_name);
                $normalizedLastName = Person::normalizeSearchText((string) $person->last_name);
                $normalizedFullName = trim($normalizedFirstName.' '.$normalizedLastName);
                $compactFirstName = Person::normalizeCompactSearchText($normalizedFirstName);
                $compactLastName = Person::normalizeCompactSearchText($normalizedLastName);
                $compactFullName = Person::normalizeCompactSearchText($normalizedFullName);

                $firstNameCase .= ' WHEN ? THEN ?';
                $firstNameBindings[] = $person->id;
                $firstNameBindings[] = $normalizedFirstName;

                $lastNameCase .= ' WHEN ? THEN ?';
                $lastNameBindings[] = $person->id;
                $lastNameBindings[] = $normalizedLastName;

                $fullNameCase .= ' WHEN ? THEN ?';
                $fullNameBindings[] = $person->id;
                $fullNameBindings[] = $normalizedFullName;

                $compactFirstNameCase .= ' WHEN ? THEN ?';
                $compactFirstNameBindings[] = $person->id;
                $compactFirstNameBindings[] = $compactFirstName;

                $compactLastNameCase .= ' WHEN ? THEN ?';
                $compactLastNameBindings[] = $person->id;
                $compactLastNameBindings[] = $compactLastName;

                $compactFullNameCase .= ' WHEN ? THEN ?';
                $compactFullNameBindings[] = $person->id;
                $compactFullNameBindings[] = $compactFullName;

                $ids[] = $person->id;
            }

            $firstNameCase .= ' END';
            $lastNameCase .= ' END';
            $fullNameCase .= ' END';
            $compactFirstNameCase .= ' END';
            $compactLastNameCase .= ' END';
            $compactFullNameCase .= ' END';

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "UPDATE people
                SET normalized_first_name = {$firstNameCase},
                    normalized_last_name = {$lastNameCase},
                    normalized_full_name = {$fullNameCase},
                    compact_first_name = {$compactFirstNameCase},
                    compact_last_name = {$compactLastNameCase},
                    compact_full_name = {$compactFullNameCase}
                WHERE id IN ({$placeholders})";

            DB::update($sql, [
                ...$firstNameBindings,
                ...$lastNameBindings,
                ...$fullNameBindings,
                ...$compactFirstNameBindings,
                ...$compactLastNameBindings,
                ...$compactFullNameBindings,
                ...$ids,
            ]);
            $updatedCount += count($ids);
        }, 'id');

        $message = $forceAll
            ? "Normalized search columns backfilled for {$updatedCount} people."
            : "Normalized search columns backfilled for {$updatedCount} people missing normalized search columns.";

        $this->info($message);

        return self::SUCCESS;
    }
}
