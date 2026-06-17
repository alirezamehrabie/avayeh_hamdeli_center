<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\User;
use App\Services\ActivityCheckInService;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCheckInServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_check_in_creates_attendance_for_ongoing_activity(): void
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

    public function test_check_in_requires_ongoing_activity(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'scheduled']);
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
}
