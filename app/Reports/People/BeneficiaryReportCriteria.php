<?php

namespace App\Reports\People;

final class BeneficiaryReportCriteria
{
    public const VERSION = 3;

    /**
     * @param  list<array<string, mixed>>  $filters
     * @param  array<string, mixed>  $columnFilters
     */
    public function __construct(
        public readonly string $globalSearch = '',
        public readonly array $filters = [],
        public readonly array $columnFilters = [],
        public readonly ?string $sortColumn = null,
        public readonly string $sortDirection = 'desc',
        public readonly int $version = self::VERSION,
    ) {}

    /**
     * Builds the current criteria format from the flat state used by existing
     * Livewire requests and saved beneficiary filters.
     *
     * @param  array<int, mixed>  $filters
     * @param  array<string, mixed>  $columnFilters
     */
    public static function fromLegacyState(
        string $globalSearch,
        array $filters,
        array $columnFilters,
        ?string $sortColumn,
        string $sortDirection,
        BeneficiaryReportFieldRegistry $fieldRegistry,
        BeneficiaryReportColumnRegistry $columnRegistry,
    ): self {
        return new self(
            globalSearch: trim($globalSearch),
            filters: $fieldRegistry->normalizeFilters($filters),
            columnFilters: $columnRegistry->normalizeColumnFilters($columnFilters),
            sortColumn: $columnRegistry->normalizeSortColumn($sortColumn),
            sortDirection: strtolower($sortDirection) === 'asc' ? 'asc' : 'desc',
        );
    }

    /**
     * @return array{
     *     version: int,
     *     global_search: string,
     *     filters: list<array<string, mixed>>,
     *     column_filters: array<string, mixed>,
     *     sort_column: string|null,
     *     sort_direction: string
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'global_search' => $this->globalSearch,
            'filters' => $this->filters,
            'column_filters' => $this->columnFilters,
            'sort_column' => $this->sortColumn,
            'sort_direction' => $this->sortDirection,
        ];
    }
}
