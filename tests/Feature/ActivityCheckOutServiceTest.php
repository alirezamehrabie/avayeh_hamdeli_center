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

class ActivityCheckOutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_check_out_updates_attendance_for_checked_in_person(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('1234567890', '15001');
        $issued = app(QrIdentityService::class)->issueFor($person, $user->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');

        $result = app(ActivityCheckInService::class)->checkOutByQr($activity, $token, $user);

        $this->assertTrue($result->ok);
        $this->assertSame('checked_out', $result->code);

        $attendance = ActivityAttendance::query()
            ->where('activity_id', $activity->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $this->assertNotNull($attendance->checked_out_at);
        $this->assertSame('qr', $attendance->check_out_method);
        $this->assertSame($user->id, $attendance->checked_out_by);
    }

    public function test_manual_check_out_records_method_and_operator(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('2234567890', '15002');

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'qr');
        $result = app(ActivityCheckInService::class)->checkOutPerson($activity, $person, $user, 'manual');

        $this->assertTrue($result->ok);
        $this->assertSame('checked_out', $result->code);
        $this->assertDatabaseHas('activity_attendances', [
            'activity_id' => $activity->id,
            'person_id' => $person->id,
            'check_out_method' => 'manual',
            'checked_out_by' => $user->id,
        ]);
    }

    public function test_check_out_requires_existing_check_in(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('3234567890', '15003');

        $result = app(ActivityCheckInService::class)->checkOutPerson($activity, $person, $user, 'manual');

        $this->assertFalse($result->ok);
        $this->assertSame('not_checked_in', $result->code);
        $this->assertDatabaseMissing('activity_attendances', [
            'activity_id' => $activity->id,
            'person_id' => $person->id,
        ]);
    }

    public function test_duplicate_check_out_is_reported_and_not_overwritten(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('4234567890', '15004');

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');
        $first = app(ActivityCheckInService::class)->checkOutPerson($activity, $person, $user, 'manual');

        $firstCheckedOutAt = ActivityAttendance::query()
            ->where('activity_id', $activity->id)
            ->where('person_id', $person->id)
            ->value('checked_out_at');

        $this->travel(5)->minutes();
        $second = app(ActivityCheckInService::class)->checkOutPerson($activity, $person, $user, 'qr');

        $this->assertSame('checked_out', $first->code);
        $this->assertTrue($second->ok);
        $this->assertSame('already_checked_out', $second->code);

        $attendance = ActivityAttendance::query()
            ->where('activity_id', $activity->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $this->assertSame((string) $firstCheckedOutAt, (string) $attendance->checked_out_at);
        $this->assertSame('manual', $attendance->check_out_method);
    }

    public function test_check_out_requires_ready_to_hold_activity(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('5234567890', '15005');

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');
        $activity->forceFill(['status' => 'closed'])->save();

        $result = app(ActivityCheckInService::class)->checkOutPerson($activity->fresh(), $person, $user, 'manual');

        $this->assertFalse($result->ok);
        $this->assertSame('activity_not_active', $result->code);
        $this->assertNull(
            ActivityAttendance::query()
                ->where('activity_id', $activity->id)
                ->where('person_id', $person->id)
                ->value('checked_out_at')
        );
    }

    public function test_check_out_by_qr_rejects_invalid_token(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);

        $result = app(ActivityCheckInService::class)->checkOutByQr($activity, 'not-a-real-token', $user);

        $this->assertFalse($result->ok);
        $this->assertSame('invalid_qr', $result->code);
    }

    public function test_check_out_result_includes_check_out_stats(): void
    {
        $user = $this->operator();
        $activity = $this->activity(['status' => 'ongoing']);
        $person = $this->person('6234567890', '15006');

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'manual');
        $result = app(ActivityCheckInService::class)->checkOutPerson($activity, $person, $user, 'manual');

        $payload = $result->toArray();

        $this->assertSame(1, $payload['stats']['checked_out_count']);
        $this->assertNotNull($payload['attendance']['checked_out_at']);
        $this->assertNotNull($payload['attendance']['checked_out_time']);
        $this->assertSame('manual', $payload['attendance']['check_out_method']);
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
