<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\ActivityCheckInService;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCheckInServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_check_in_creates_attendance_for_ready_to_hold_activity(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('1234567890', '14001');
        $issued = app(QrIdentityService::class)->issueFor($person, $user->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $result = app(ActivityCheckInService::class)->checkInByQr($activity, $token, $user);

        $this->assertTrue($result->ok);
        $this->assertSame('checked_in', $result->code);
        $this->assertDatabaseHas('activity_attendances', [
            'activity_id' => $activity->id,
            'person_id' => $person->id,
            'registration_method' => 'qr',
            'status' => 'present',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_duplicate_check_in_returns_existing_attendance(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('2234567890', '14002');

        $first = app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');
        $second = app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');

        $this->assertSame('checked_in', $first->code);
        $this->assertSame('duplicate', $second->code);
        $this->assertSame(1, ActivityAttendance::query()->where('activity_id', $activity->id)->where('person_id', $person->id)->count());
    }

    public function test_check_in_requires_ready_to_hold_activity(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'draft']);
        $person = $this->person('3234567890', '14003');

        $result = app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');

        $this->assertFalse($result->ok);
        $this->assertSame('activity_not_active', $result->code);
        $this->assertDatabaseMissing('activity_attendances', [
            'activity_id' => $activity->id,
            'person_id' => $person->id,
        ]);
    }

    public function test_capacity_limit_blocks_new_attendance(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing', 'capacity' => 1]);
        $firstPerson = $this->person('4234567890', '14004');
        $secondPerson = $this->person('5234567890', '14005');

        app(ActivityCheckInService::class)->checkInPerson($activity, $firstPerson, $user, 'manual');
        $result = app(ActivityCheckInService::class)->checkInPerson($activity, $secondPerson, $user, 'manual');

        $this->assertFalse($result->ok);
        $this->assertSame('capacity_full', $result->code);
        $this->assertDatabaseMissing('activity_attendances', [
            'activity_id' => $activity->id,
            'person_id' => $secondPerson->id,
        ]);
    }

    public function test_activity_check_in_creates_deliveries_for_linked_activity_services(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('6234567890', '14006');
        $service = $this->activityService($activity, $user, 'Ceremony Food');
        $firstCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Food',
            'quantity' => 50,
            'unit' => 'portion',
            'value' => 20000,
            'created_by' => $user->id,
        ]);
        $secondCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Sweets',
            'quantity' => 50,
            'unit' => 'pack',
            'value' => 10000,
            'created_by' => $user->id,
        ]);

        $result = app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');

        $this->assertTrue($result->ok);
        $this->assertSame('checked_in', $result->code);

        $attendance = ActivityAttendance::query()
            ->where('activity_id', $activity->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        foreach ([$firstCategory, $secondCategory] as $category) {
            $this->assertDatabaseHas('service_deliveries', [
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'activity_attendance_id' => $attendance->id,
                'delivery_channel' => Service::DELIVERY_CHANNEL_ACTIVITY,
                'social_worker_id' => null,
                'person_id' => $person->id,
                'delivered_quantity' => 1,
                'created_by' => $user->id,
            ]);
        }

        $this->assertSame(2, ServiceDelivery::query()->where('activity_attendance_id', $attendance->id)->count());
        $this->assertSame('2.00', (string) $service->fresh()->quantity_delivered);
    }

    public function test_duplicate_activity_check_in_does_not_duplicate_activity_deliveries(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('7234567890', '14007');
        $service = $this->activityService($activity, $user, 'Duplicate Guard Service');
        $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Gift',
            'quantity' => 50,
            'unit' => 'pack',
            'value' => 10000,
            'created_by' => $user->id,
        ]);

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');
        $second = app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');

        $this->assertSame('duplicate', $second->code);
        $this->assertSame(1, ServiceDelivery::query()->where('service_id', $service->id)->count());
    }

    private function operator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function activity(array $overrides = []): Activity
    {
        return Activity::query()->create(array_merge([
            'name' => 'Test Activity',
            'activity_type' => 'group_activity',
            'status' => 'draft',
            'created_by' => null,
        ], $overrides));
    }

    private function person(string $nationalId, string $personCode): Person
    {
        return Person::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Person',
            'national_id' => $nationalId,
            'person_code' => $personCode,
        ]);
    }

    private function activityService(Activity $activity, User $creator, string $name): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);

        return Service::query()->create([
            'activity_id' => $activity->id,
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => false,
            'supports_activity_delivery' => true,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 50,
            'total_service_value' => 0,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
    }
}
