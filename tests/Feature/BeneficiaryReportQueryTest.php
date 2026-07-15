<?php

namespace Tests\Feature;

use App\Exports\PeopleExport;
use App\Models\DisabilityType;
use App\Models\Guardian;
use App\Models\HarmType;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Queries\People\BeneficiaryReportQuery;
use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportFieldRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BeneficiaryReportQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_combined_filters_use_the_guardian_worker_allocation_source(): void
    {
        $assignedWorker = $this->worker(991001, 'Assigned', 'Worker');
        $otherWorker = $this->worker(991002, 'Other', 'Worker');
        $assignedGuardian = $this->guardian($assignedWorker, 991001);
        $otherGuardian = $this->guardian($otherWorker, 991002);

        $matching = $this->person($assignedGuardian, 'BRQ001', '9910000001', [
            'first_name' => 'Target',
            'last_name' => 'Beneficiary',
            'gender' => 'female',
            'birth_year' => 1390,
        ]);
        $this->person($otherGuardian, 'BRQ002', '9910000002', [
            'first_name' => 'Target',
            'last_name' => 'Wrong Worker',
            'gender' => 'female',
            'birth_year' => 1390,
        ]);
        $this->person($assignedGuardian, 'BRQ003', '9910000003', [
            'first_name' => 'Target',
            'last_name' => 'Wrong Gender',
            'gender' => 'male',
            'birth_year' => 1390,
        ]);

        $criteria = $this->criteria(
            globalSearch: 'Target',
            filters: [
                ['field' => 'gender', 'type' => 'select', 'value' => 'female'],
                [
                    'field' => 'caseworker',
                    'type' => 'text',
                    'value' => '',
                    'selected' => [
                        ['id' => $assignedWorker->id, 'name' => $assignedWorker->full_name, 'code' => '991001'],
                    ],
                ],
            ],
            columnFilters: ['birth_year' => '1390'],
        );

        $this->assertSame(
            [$matching->id],
            app(BeneficiaryReportQuery::class)->build($criteria)->pluck('id')->all()
        );
    }

    public function test_date_filter_and_full_name_sorting_are_applied_consistently(): void
    {
        $guardian = $this->guardian($this->worker(991003, 'Date', 'Worker'), 991003);
        $laterName = $this->person($guardian, 'BRQ004', '9910000004', [
            'first_name' => 'Zed',
            'last_name' => 'Beta',
            'birth_year' => 1392,
            'birth_month' => 7,
            'birth_day' => 15,
        ]);
        $earlierName = $this->person($guardian, 'BRQ005', '9910000005', [
            'first_name' => 'Amy',
            'last_name' => 'Alpha',
            'birth_year' => 1392,
            'birth_month' => 7,
            'birth_day' => 15,
        ]);
        $this->person($guardian, 'BRQ006', '9910000006', [
            'first_name' => 'Excluded',
            'last_name' => 'Date',
            'birth_year' => 1392,
            'birth_month' => 7,
            'birth_day' => 16,
        ]);

        $criteria = $this->criteria(
            filters: [[
                'field' => 'birth_date',
                'type' => 'date',
                'mode' => 'exact',
                'exact' => '1392/07/15',
            ]],
            sortColumn: 'full_name',
            sortDirection: 'asc',
        );

        $this->assertSame(
            [$earlierName->id, $laterName->id],
            app(BeneficiaryReportQuery::class)->build($criteria)->pluck('id')->all()
        );
    }

    public function test_disability_filter_sort_and_export_values_share_one_definition(): void
    {
        $guardian = $this->guardian($this->worker(991004, 'Clinical', 'Worker'), 991004);
        $physical = DisabilityType::query()->create(['name' => 'Physical']);
        $visual = DisabilityType::query()->create(['name' => 'Visual']);
        $harm = HarmType::query()->create(['title' => 'Neglect']);

        $visualPerson = $this->person($guardian, 'BRQ007', '9910000007', [
            'has_disability' => true,
            'disability_type_id' => $visual->id,
            'disability_description' => 'Needs visual support',
        ]);
        $visualPerson->harmTypes()->attach($harm->id);

        $this->person($guardian, 'BRQ008', '9910000008', [
            'has_disability' => true,
            'disability_type_id' => $physical->id,
            'disability_description' => 'Mobility support',
        ]);

        $criteria = $this->criteria(
            columnFilters: ['related_description' => 'Visual'],
            sortColumn: 'related_description',
            sortDirection: 'asc',
        );

        $result = app(BeneficiaryReportQuery::class)
            ->build($criteria)
            ->with(app(BeneficiaryReportColumnRegistry::class)->eagerLoads())
            ->firstOrFail();

        $this->assertTrue($result->is($visualPerson));

        $columns = [
            'beneficiary_injury_disability_type',
            'related_description',
            'disability_description',
        ];
        $registry = app(BeneficiaryReportColumnRegistry::class);
        $export = new PeopleExport(Person::query(), $columns, $registry);

        $this->assertSame([
            'Neglect',
            'Visual',
            'Needs visual support',
        ], $export->map($result));
        $this->assertSame(
            collect($columns)->map(fn (string $column): string => $registry->labels()[$column])->all(),
            $export->headings()
        );
    }

    /**
     * @param  array<int, mixed>  $filters
     * @param  array<string, mixed>  $columnFilters
     */
    private function criteria(
        string $globalSearch = '',
        array $filters = [],
        array $columnFilters = [],
        ?string $sortColumn = null,
        string $sortDirection = 'desc',
    ): BeneficiaryReportCriteria {
        return BeneficiaryReportCriteria::fromLegacyState(
            globalSearch: $globalSearch,
            filters: $filters,
            columnFilters: $columnFilters,
            sortColumn: $sortColumn,
            sortDirection: $sortDirection,
            fieldRegistry: app(BeneficiaryReportFieldRegistry::class),
            columnRegistry: app(BeneficiaryReportColumnRegistry::class),
        );
    }

    private function worker(int $code, string $firstName, string $lastName): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);
    }

    private function guardian(SocialWorker $worker, int $code): Guardian
    {
        return Guardian::query()->create([
            'social_worker_id' => $worker->id,
            'guardian_code' => $code,
            'first_name' => 'Guardian',
            'last_name' => (string) $code,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function person(Guardian $guardian, string $code, string $nationalId, array $attributes = []): Person
    {
        return Person::query()->create(array_merge([
            'guardian_id' => $guardian->id,
            'person_code' => $code,
            'national_id' => $nationalId,
            'first_name' => 'Report',
            'last_name' => 'Person',
        ], $attributes));
    }
}
