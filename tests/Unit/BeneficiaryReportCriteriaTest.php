<?php

namespace Tests\Unit;

use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportFieldRegistry;
use PHPUnit\Framework\TestCase;

class BeneficiaryReportCriteriaTest extends TestCase
{
    public function test_legacy_state_is_normalized_into_the_current_version(): void
    {
        $criteria = BeneficiaryReportCriteria::fromLegacyState(
            globalSearch: '  Reza  ',
            filters: [
                [
                    'field' => 'caseworker',
                    'type' => 'select',
                    'operator' => 'invalid',
                    'value' => ['invalid'],
                    'selected' => [
                        ['id' => '12', 'name' => 'Worker One', 'code' => 100],
                        ['id' => 12, 'name' => 'Duplicate', 'code' => 101],
                        ['id' => 'invalid', 'name' => 'Ignored'],
                        'invalid',
                    ],
                ],
                [
                    'field' => 'months_without_service',
                    'type' => 'text',
                    'operator' => 'invalid',
                    'value' => 6,
                ],
                [
                    'field' => 'unknown_column',
                    'type' => 'text',
                    'value' => 'ignored',
                ],
                'invalid',
            ],
            columnFilters: [
                'national_id' => 1234,
                'unknown_column' => 'ignored',
                'full_name' => ['invalid'],
            ],
            sortColumn: 'unknown_column',
            sortDirection: 'ASC',
            fieldRegistry: new BeneficiaryReportFieldRegistry,
            columnRegistry: new BeneficiaryReportColumnRegistry,
        );

        $this->assertSame(BeneficiaryReportCriteria::VERSION, $criteria->version);
        $this->assertSame('Reza', $criteria->globalSearch);
        $this->assertNull($criteria->sortColumn);
        $this->assertSame('asc', $criteria->sortDirection);
        $this->assertSame([
            [
                'field' => 'caseworker',
                'type' => 'text',
                'operator' => 'contains',
                'value' => '',
                'selected' => [
                    ['id' => 12, 'name' => 'Worker One', 'code' => '100'],
                ],
            ],
            [
                'field' => 'months_without_service',
                'type' => 'number',
                'operator' => 'gte',
                'value' => '6',
            ],
        ], $criteria->filters);
        $this->assertSame(['national_id' => '1234'], $criteria->columnFilters);
        $this->assertSame(1, $criteria->toArray()['version']);
    }

    public function test_visible_columns_and_sorting_are_restricted_to_the_registry(): void
    {
        $registry = new BeneficiaryReportColumnRegistry;

        $this->assertSame(
            ['full_name', 'national_id'],
            $registry->normalizeVisibleColumns([
                'full_name',
                'unknown',
                'national_id',
                'full_name',
                123,
            ])
        );
        $this->assertSame(
            BeneficiaryReportColumnRegistry::DEFAULT_COLUMNS,
            $registry->normalizeVisibleColumns([])
        );
        $this->assertSame('related_description', $registry->normalizeSortColumn('related_description'));
        $this->assertNull($registry->normalizeSortColumn('disability_description'));
    }
}
