<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\People\BeneficiaryReportQuery;
use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportFieldRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BeneficiaryOperationalFiltersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_service_filters_and_aggregates_use_one_correlated_delivery_set(): void
    {
        $worker = $this->worker(992001);
        $guardian = $this->guardian($worker, 992001);
        $person = $this->person($guardian, 'BRO001', '9920000001');
        [$serviceA, $categoryA, $creator] = $this->service('Correlated A');
        [$serviceB, $categoryB] = $this->service('Correlated B');
        $date = $this->gregorianDate('1405/04/24');

        $this->delivery($serviceA, $categoryA, $creator, $worker->id, $date, 'home', 2, 2500, $person->id);
        $this->delivery($serviceB, $categoryB, $creator, $worker->id, $date, 'gate', 1, 9000, $person->id);

        $falseCorrelation = $this->criteria([
            ['field' => 'service_id', 'value' => (string) $serviceA],
            ['field' => 'delivery_channel', 'value' => 'gate'],
        ]);

        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($falseCorrelation)->exists());

        $correlatedAggregates = $this->criteria([
            ['field' => 'service_id', 'value' => (string) $serviceA],
            ['field' => 'delivery_channel', 'value' => 'home'],
            ['field' => 'service_delivery_date', 'mode' => 'exact', 'exact' => '1405/04/24'],
            ['field' => 'service_delivery_count', 'operator' => 'eq', 'value' => '1'],
            ['field' => 'service_delivery_quantity', 'operator' => 'eq', 'value' => '2'],
            ['field' => 'service_delivery_value', 'operator' => 'eq', 'value' => '2500'],
        ]);

        $this->assertSame(
            [$person->id],
            app(BeneficiaryReportQuery::class)->build($correlatedAggregates)->pluck('id')->all()
        );
    }

    public function test_service_recipient_scope_and_soft_deleted_deliveries_are_respected(): void
    {
        $worker = $this->worker(992002);
        $guardian = $this->guardian($worker, 992002);
        $directPerson = $this->person($guardian, 'BRO002', '9920000002');
        $householdPerson = $this->person($guardian, 'BRO003', '9920000003');
        [$service, $category, $creator] = $this->service('Recipient scope');
        $date = $this->gregorianDate('1405/04/24');

        $this->delivery($service, $category, $creator, $worker->id, $date, 'home', 1, 1000, $directPerson->id);

        $this->assertSame(
            [$directPerson->id],
            app(BeneficiaryReportQuery::class)->build($this->criteria([
                ['field' => 'service_id', 'value' => (string) $service],
                ['field' => 'service_recipient_scope', 'value' => 'direct'],
            ]))->pluck('id')->all()
        );

        $householdDeliveryId = $this->delivery(
            $service,
            $category,
            $creator,
            $worker->id,
            $date,
            'home',
            1,
            1000,
            guardianId: $guardian->id
        );

        $householdIds = app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => 'service_id', 'value' => (string) $service],
            ['field' => 'service_recipient_scope', 'value' => 'household'],
        ]))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$directPerson->id, $householdPerson->id], $householdIds);

        DB::table('service_deliveries')->where('id', $householdDeliveryId)->update(['deleted_at' => now()]);

        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => 'service_id', 'value' => (string) $service],
            ['field' => 'service_recipient_scope', 'value' => 'household'],
        ]))->exists());
    }

    public function test_case_record_filters_correlate_type_date_count_and_amount(): void
    {
        $person = $this->person($this->guardian($this->worker(992003), 992003), 'BRO004', '9920000004');
        $creator = User::factory()->create();

        BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $creator->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_NOTE,
            'title' => 'Note',
            'recorded_at' => $this->gregorianDate('1405/04/23'),
            'amount' => 100,
        ]);
        BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $creator->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_INVOICE,
            'title' => 'Invoice',
            'recorded_at' => $this->gregorianDate('1405/04/24'),
            'amount' => 5000,
        ]);

        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => 'case_record_type', 'value' => BeneficiaryCaseRecord::TYPE_NOTE],
            ['field' => 'case_record_date', 'mode' => 'exact', 'exact' => '1405/04/24'],
        ]))->exists());

        $matching = $this->criteria([
            ['field' => 'case_record_type', 'value' => BeneficiaryCaseRecord::TYPE_INVOICE],
            ['field' => 'case_record_date', 'mode' => 'range', 'from' => '1405/04/24', 'to' => '1405/04/24'],
            ['field' => 'case_record_count', 'operator' => 'eq', 'value' => '1'],
            ['field' => 'case_record_amount', 'operator' => 'eq', 'value' => '5000'],
        ]);

        $this->assertSame([$person->id], app(BeneficiaryReportQuery::class)->build($matching)->pluck('id')->all());

        $invalidRange = $this->criteria([[
            'field' => 'case_record_date',
            'mode' => 'range',
            'from' => '1405/04/25',
            'to' => '1405/04/24',
        ]]);
        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($invalidRange)->exists());
    }

    public function test_activity_filters_correlate_activity_status_method_date_and_count(): void
    {
        $person = $this->person($this->guardian($this->worker(992004), 992004), 'BRO005', '9920000005');
        $creator = User::factory()->create();
        $workshop = Activity::query()->create([
            'name' => 'Operational workshop',
            'activity_type' => 'workshop',
            'status' => 'closed',
            'created_by' => $creator->id,
        ]);
        $camp = Activity::query()->create([
            'name' => 'Operational camp',
            'activity_type' => 'camp',
            'status' => 'closed',
            'created_by' => $creator->id,
        ]);

        ActivityAttendance::query()->create([
            'activity_id' => $workshop->id,
            'person_id' => $person->id,
            'status' => 'present',
            'registration_method' => 'qr',
            'checked_in_at' => $this->gregorianDate('1405/04/24').' 23:59:59',
            'recorded_by' => $creator->id,
        ]);
        ActivityAttendance::query()->create([
            'activity_id' => $camp->id,
            'person_id' => $person->id,
            'status' => 'absent',
            'registration_method' => 'manual',
            'checked_in_at' => $this->gregorianDate('1405/04/25').' 00:00:00',
            'recorded_by' => $creator->id,
        ]);

        $this->assertFalse(app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => 'activity_type', 'value' => 'workshop'],
            ['field' => 'activity_attendance_status', 'value' => 'absent'],
        ]))->exists());

        $matching = $this->criteria([
            ['field' => 'activity_type', 'value' => 'workshop'],
            ['field' => 'activity_attendance_status', 'value' => 'present'],
            ['field' => 'activity_registration_method', 'value' => 'qr'],
            ['field' => 'activity_attendance_date', 'mode' => 'exact', 'exact' => '1405/04/24'],
            ['field' => 'activity_attendance_count', 'operator' => 'eq', 'value' => '1'],
        ]);

        $this->assertSame([$person->id], app(BeneficiaryReportQuery::class)->build($matching)->pluck('id')->all());
    }

    public function test_worker_assignment_status_uses_the_current_household_worker(): void
    {
        $activeWorker = $this->worker(992005);
        $inactiveWorker = $this->worker(992006);
        $inactiveWorker->forceFill(['is_active' => false])->save();
        $inactiveWorker->delete();

        $active = $this->person($this->guardian($activeWorker, 992005), 'BRO006', '9920000006');
        $inactive = $this->person($this->guardian($inactiveWorker, 992006), 'BRO007', '9920000007');
        $unassigned = $this->person($this->guardian(null, 992007), 'BRO008', '9920000008');

        $this->assertSame([$active->id], $this->idsFor('worker_assignment_status', 'assigned_active'));
        $this->assertSame([$inactive->id], $this->idsFor('worker_assignment_status', 'assigned_inactive'));
        $this->assertSame([$unassigned->id], $this->idsFor('worker_assignment_status', 'unassigned'));
    }

    public function test_worker_allocation_status_compares_exact_worker_service_and_category_totals(): void
    {
        $worker = $this->worker(992007);
        $guardian = $this->guardian($worker, 992008);
        $person = $this->person($guardian, 'BRO009', '9920000009');
        [$pendingService, $pendingCategory, $creator] = $this->service('Pending allocation');
        [$completedService, $completedCategory] = $this->service('Completed allocation');
        [$unallocatedService, $unallocatedCategory] = $this->service('No allocation');
        $date = $this->gregorianDate('1405/04/24');

        $this->allocation($pendingService, $pendingCategory, $worker->id, $creator, 5);
        $this->allocation($completedService, $completedCategory, $worker->id, $creator, 2);
        $this->delivery($pendingService, $pendingCategory, $creator, $worker->id, $date, 'home', 2, 2000, $person->id);
        $this->delivery($completedService, $completedCategory, $creator, $worker->id, $date, 'home', 2, 2000, $person->id);
        $this->delivery($unallocatedService, $unallocatedCategory, $creator, null, $date, 'activity', 1, 1000, $person->id);

        $this->assertSame([$person->id], $this->allocationIds($pendingService, 'pending_allocation'));
        $this->assertSame([$person->id], $this->allocationIds($completedService, 'completed_allocation'));
        $this->assertSame([$person->id], $this->allocationIds($unallocatedService, 'no_allocation'));

        DB::table('service_deliveries')
            ->where('service_id', $pendingService)
            ->update(['delivered_quantity' => 5]);

        $this->assertSame([$person->id], $this->allocationIds($pendingService, 'completed_allocation'));
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function criteria(array $filters): BeneficiaryReportCriteria
    {
        return BeneficiaryReportCriteria::fromLegacyState(
            globalSearch: '',
            filters: $filters,
            columnFilters: [],
            sortColumn: null,
            sortDirection: 'desc',
            fieldRegistry: app(BeneficiaryReportFieldRegistry::class),
            columnRegistry: app(BeneficiaryReportColumnRegistry::class),
        );
    }

    /**
     * @return list<int>
     */
    private function idsFor(string $field, string $value): array
    {
        return app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => $field, 'value' => $value],
        ]))->pluck('id')->all();
    }

    /**
     * @return list<int>
     */
    private function allocationIds(int $serviceId, string $status): array
    {
        return app(BeneficiaryReportQuery::class)->build($this->criteria([
            ['field' => 'service_id', 'value' => (string) $serviceId],
            ['field' => 'worker_allocation_status', 'value' => $status],
        ]))->pluck('id')->all();
    }

    private function worker(int $code): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => 'Operational',
            'last_name' => (string) $code,
            'is_active' => true,
        ]);
    }

    private function guardian(?SocialWorker $worker, int $code): Guardian
    {
        return Guardian::query()->create([
            'social_worker_id' => $worker?->id,
            'guardian_code' => $code,
            'first_name' => 'Guardian',
            'last_name' => (string) $code,
        ]);
    }

    private function person(Guardian $guardian, string $code, string $nationalId): Person
    {
        return Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => $code,
            'national_id' => $nationalId,
            'first_name' => 'Operational',
            'last_name' => $code,
        ]);
    }

    /**
     * @return array{int, int, int}
     */
    private function service(string $name): array
    {
        $creator = User::factory()->create();
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => true,
            'distribution_start_date' => '2026-01-01',
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 100,
            'total_service_value' => 100000,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name.' category',
            'quantity' => 100,
            'unit' => 'count',
            'value' => 1000,
            'created_by' => $creator->id,
        ]);

        return [$service->id, $category->id, $creator->id];
    }

    private function allocation(
        int $serviceId,
        int $categoryId,
        int $workerId,
        int $creatorId,
        float $quantity,
    ): void {
        DB::table('service_social_worker')->insert([
            'service_id' => $serviceId,
            'service_category_id' => $categoryId,
            'social_worker_id' => $workerId,
            'allocated_quantity' => $quantity,
            'assigned_by_user_id' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function delivery(
        int $serviceId,
        int $categoryId,
        int $creatorId,
        ?int $workerId,
        string $deliveredAt,
        string $channel,
        float $quantity,
        int $value,
        ?int $personId = null,
        ?int $guardianId = null,
    ): int {
        return (int) DB::table('service_deliveries')->insertGetId([
            'service_id' => $serviceId,
            'service_category_id' => $categoryId,
            'social_worker_id' => $workerId,
            'person_id' => $personId,
            'guardian_id' => $guardianId,
            'delivery_channel' => $channel,
            'national_id' => '9929999999',
            'full_name' => 'Operational recipient',
            'delivered_quantity' => $quantity,
            'value_per_unit_snapshot' => $quantity > 0 ? (int) ($value / $quantity) : 0,
            'delivered_total_value' => $value,
            'delivered_at' => $deliveredAt,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function gregorianDate(string $jalali): string
    {
        [$year, $month, $day] = array_map('intval', explode('/', $jalali));

        return (new Jalalian($year, $month, $day))->toCarbon()->toDateString();
    }
}
