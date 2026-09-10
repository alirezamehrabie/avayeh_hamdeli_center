<?php

namespace App\Services\Search;

use App\Models\Person;
use Illuminate\Support\Facades\DB;

/**
 * Bulk recomputes the normalized/compact search columns of a name table
 * (people or guardians) with chunked CASE updates, using the exact same fold
 * the models apply on save — so backfilled rows and future rows stay comparable.
 */
class BackfillNormalizedSearchColumns
{
    private const COLUMNS = [
        'normalized_first_name',
        'normalized_last_name',
        'normalized_full_name',
        'compact_first_name',
        'compact_last_name',
        'compact_full_name',
    ];

    public function run(string $table, int $chunkSize = 500, bool $forceAll = false): int
    {
        $updatedCount = 0;

        $query = DB::table($table)
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('id');

        if (! $forceAll) {
            $query->where(function ($builder): void {
                foreach (self::COLUMNS as $column) {
                    $builder->orWhereNull($column);
                }
            });
        }

        $query->chunkById($chunkSize, function ($rows) use (&$updatedCount, $table): void {
            if ($rows->isEmpty()) {
                return;
            }

            // Bindings must be grouped per column (all pairs of one CASE, then
            // the next), not interleaved per row, or the placeholders of each
            // CASE expression consume the wrong values.
            $cases = [];
            $bindings = [];
            $ids = [];

            foreach (self::COLUMNS as $column) {
                $cases[$column] = 'CASE id';
                $bindings[$column] = [];
            }

            foreach ($rows as $row) {
                $normalizedFirstName = Person::normalizeSearchText((string) $row->first_name);
                $normalizedLastName = Person::normalizeSearchText((string) $row->last_name);
                $normalizedFullName = trim($normalizedFirstName.' '.$normalizedLastName);

                $values = [
                    'normalized_first_name' => $normalizedFirstName,
                    'normalized_last_name' => $normalizedLastName,
                    'normalized_full_name' => $normalizedFullName,
                    'compact_first_name' => Person::normalizeCompactSearchText($normalizedFirstName),
                    'compact_last_name' => Person::normalizeCompactSearchText($normalizedLastName),
                    'compact_full_name' => Person::normalizeCompactSearchText($normalizedFullName),
                ];

                foreach ($values as $column => $value) {
                    $cases[$column] .= ' WHEN ? THEN ?';
                    $bindings[$column][] = $row->id;
                    $bindings[$column][] = $value;
                }

                $ids[] = $row->id;
            }

            $assignments = [];
            $flatBindings = [];

            foreach (self::COLUMNS as $column) {
                $cases[$column] .= ' END';
                $assignments[] = "{$column} = {$cases[$column]}";
                $flatBindings = [...$flatBindings, ...$bindings[$column]];
            }

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "UPDATE {$table} SET ".implode(', ', $assignments)." WHERE id IN ({$placeholders})";

            DB::update($sql, [
                ...$flatBindings,
                ...$ids,
            ]);
            $updatedCount += count($ids);
        }, 'id');

        return $updatedCount;
    }
}
