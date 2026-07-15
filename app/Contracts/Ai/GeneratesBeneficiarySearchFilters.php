<?php

namespace App\Contracts\Ai;

interface GeneratesBeneficiarySearchFilters
{
    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array{
     *     interpretation: string,
     *     filters: list<array<string, mixed>>,
     *     summaries: list<string>,
     *     unresolved: list<string>
     * }
     */
    public function generate(string $query, array $catalog): array;
}
