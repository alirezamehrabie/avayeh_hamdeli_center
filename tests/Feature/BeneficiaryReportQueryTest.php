<?php

namespace Tests\Feature;

use App\Exports\PeopleExport;
use App\Models\DisabilityType;
use App\Models\Guardian;
use App\Models\HarmType;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\People\BeneficiaryReportQuery;
use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportFieldRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

    public function test_birth_date_ranges_use_the_indexed_generated_column_and_fail_closed(): void
    {
        $guardian = $this->guardian($this->worker(991005, 'Range', 'Worker'), 991005);
        $fromBoundary = $this->person($guardian, 'BRQ009', '9910000009', [
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ]);
        $toBoundary = $this->person($guardian, 'BRQ010', '9910000010', [
            'birth_year' => 1390,
            'birth_month' => 12,
            'birth_day' => 29,
        ]);
        $this->person($guardian, 'BRQ011', '9910000011', [
            'birth_year' => 1391,
            'birth_month' => 1,
            'birth_day' => 1,
        ]);

        $criteria = $this->criteria(filters: [[
            'field' => 'birth_date',
            'type' => 'date',
            'mode' => 'range',
            'from' => '1390/01/01',
            'to' => '1390/12/29',
        ]]);
        $query = app(BeneficiaryReportQuery::class)->build($criteria);

        $this->assertStringContainsString('birth_date_full', $query->toSql());
        $this->assertStringNotContainsString('concat(', strtolower($query->toSql()));
        $this->assertEqualsCanonicalizing(
            [$fromBoundary->id, $toBoundary->id],
            $query->pluck('id')->all()
        );

        $invalidCriteria = $this->criteria(filters: [[
            'field' => 'birth_date',
            'type' => 'date',
            'mode' => 'exact',
            'exact' => '1402/12/30',
        ]]);
        $reversedCriteria = $this->criteria(filters: [[
            'field' => 'birth_date',
            'type' => 'date',
            'mode' => 'range',
            'from' => '1402/01/02',
            'to' => '1402/01/01',
        ]]);

        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($invalidCriteria)->exists());
        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($reversedCriteria)->exists());
    }

    public function test_inactive_and_deleted_current_workers_remain_filterable_and_reportable(): void
    {
        $worker = $this->worker(991006, 'Historical', 'Worker');
        $guardian = $this->guardian($worker, 991006);
        $person = $this->person($guardian, 'BRQ012', '9910000012');

        $worker->forceFill(['is_active' => false])->save();
        $worker->delete();

        $criteria = $this->criteria(filters: [[
            'field' => 'caseworker',
            'type' => 'text',
            'value' => '',
            'selected' => [
                ['id' => $worker->id, 'name' => $worker->full_name, 'code' => (string) $worker->worker_code],
            ],
        ]]);

        $result = app(BeneficiaryReportQuery::class)
            ->build($criteria)
            ->with(app(BeneficiaryReportColumnRegistry::class)->eagerLoads())
            ->firstOrFail();

        $this->assertTrue($result->is($person));
        $this->assertSame(
            'Historical Worker (غیرفعال)',
            app(BeneficiaryReportColumnRegistry::class)->value($result, 'responsible_social_worker')
        );

        $textCriteria = $this->criteria(columnFilters: [
            'responsible_social_worker' => 'Historical Worker',
        ]);

        $this->assertSame(
            [$person->id],
            app(BeneficiaryReportQuery::class)->build($textCriteria)->pluck('id')->all()
        );
    }

    public function test_skills_description_column_filters_and_exports_talent_notes(): void
    {
        $guardian = $this->guardian($this->worker(991008, 'Talent', 'Worker'), 991008);
        $talentedPerson = $this->person($guardian, 'BRQ016', '9910000016', [
            'skills_description' => 'نقاشی و خوشنویسی',
        ]);
        $this->person($guardian, 'BRQ017', '9910000017', [
            'skills_description' => 'مهارت ورزشی',
        ]);

        $criteria = $this->criteria(columnFilters: ['skills_description' => 'خوشنویسی']);
        $result = app(BeneficiaryReportQuery::class)
            ->build($criteria)
            ->with(app(BeneficiaryReportColumnRegistry::class)->eagerLoads())
            ->firstOrFail();

        $this->assertTrue($result->is($talentedPerson));

        $registry = app(BeneficiaryReportColumnRegistry::class);
        $export = new PeopleExport(Person::query(), ['skills_description'], $registry);

        $this->assertSame(['نقاشی و خوشنویسی'], $export->map($result));
        $this->assertSame(['توضیحات استعداد'], $export->headings());
        $this->assertContains('skills_description', array_keys($registry->labels()));
    }

    public function test_months_without_service_has_an_inclusive_cutoff_and_current_household_scope(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        try {
            $worker = $this->worker(991007, 'Service', 'Worker');
            $guardianAtCutoff = $this->guardian($worker, 991007);
            $guardianRecent = $this->guardian($worker, 991008);
            $guardianNeverServed = $this->guardian($worker, 991009);
            $atCutoff = $this->person($guardianAtCutoff, 'BRQ013', '9910000013');
            $recentHousehold = $this->person($guardianRecent, 'BRQ014', '9910000014');
            $neverServed = $this->person($guardianNeverServed, 'BRQ015', '9910000015');
            [$serviceId, $categoryId, $creatorId] = $this->serviceFixture();

            $this->insertDelivery(
                serviceId: $serviceId,
                categoryId: $categoryId,
                creatorId: $creatorId,
                workerId: $worker->id,
                deliveredAt: '2026-01-15',
                personId: $atCutoff->id,
            );
            $this->insertDelivery(
                serviceId: $serviceId,
                categoryId: $categoryId,
                creatorId: $creatorId,
                workerId: $worker->id,
                deliveredAt: '2026-01-16',
                guardianId: $guardianRecent->id,
            );

            $criteria = $this->criteria(filters: [[
                'field' => 'months_without_service',
                'type' => 'number',
                'operator' => 'gte',
                'value' => '6',
            ]]);
            $ids = app(BeneficiaryReportQuery::class)->build($criteria)->pluck('id');

            $this->assertTrue($ids->contains($atCutoff->id));
            $this->assertFalse($ids->contains($recentHousehold->id));
            $this->assertTrue($ids->contains($neverServed->id));
        } finally {
            Carbon::setTestNow();
        }
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

    /**
     * @return array{int, int, int}
     */
    private function serviceFixture(): array
    {
        $creator = User::factory()->create();
        $serviceName = ServiceName::query()->create([
            'name' => 'Beneficiary report recency service',
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Beneficiary report recency service',
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'distribution_start_date' => '2025-01-01',
            'distribution_end_date' => null,
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 100,
            'total_service_value' => 100000,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Recency category',
            'quantity' => 100,
            'unit' => 'count',
            'value' => 1000,
            'created_by' => $creator->id,
        ]);

        return [$service->id, $category->id, $creator->id];
    }

    private function insertDelivery(
        int $serviceId,
        int $categoryId,
        int $creatorId,
        int $workerId,
        string $deliveredAt,
        ?int $personId = null,
        ?int $guardianId = null,
    ): void {
        DB::table('service_deliveries')->insert([
            'service_id' => $serviceId,
            'service_category_id' => $categoryId,
            'social_worker_id' => $workerId,
            'person_id' => $personId,
            'guardian_id' => $guardianId,
            'national_id' => '9900000000',
            'full_name' => 'Report service recipient',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => $deliveredAt,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
