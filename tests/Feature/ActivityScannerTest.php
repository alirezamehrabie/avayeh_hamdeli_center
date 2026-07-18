<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityScanner;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\User;
use App\Services\ActivityCheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityScannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_search_prefers_index_friendly_prefix_matches(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $activity = Activity::query()->create([
            'name' => 'Manual Search Activity',
            'activity_type' => 'group_activity',
            'status' => 'ongoing',
            'created_by' => $user->id,
        ]);

        $codeMatch = Person::query()->create([
            'first_name' => 'Code',
            'last_name' => 'Match',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $middleNameMatch = Person::query()->create([
            'first_name' => 'Middle',
            'last_name' => 'Contains',
            'national_id' => '2234567890',
            'person_code' => '24001',
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])
            ->set('manualSearch', '4')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->isEmpty())
            ->set('manualSearch', '14')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->contains('id', $codeMatch->id))
            ->set('manualSearch', 'dd')
            ->assertViewHas('manualCandidates', fn ($candidates) => ! $candidates->contains('id', $middleNameMatch->id))
            ->set('manualSearch', 'idd')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->contains('id', $middleNameMatch->id));
    }

    public function test_check_out_mode_limits_manual_candidates_to_checked_in_people(): void
    {
        $user = $this->adminUser();
        $activity = $this->ongoingActivity($user);

        $checkedIn = Person::query()->create([
            'first_name' => 'Checked',
            'last_name' => 'In',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $notCheckedIn = Person::query()->create([
            'first_name' => 'Never',
            'last_name' => 'Entered',
            'national_id' => '2234567890',
            'person_code' => '14002',
        ]);

        app(ActivityCheckInService::class)->checkInPerson($activity, $checkedIn, $user, 'manual');

        $this->actingAs($user);

        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])
            ->call('setMode', 'check-out')
            ->set('manualSearch', '14')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->contains('id', $checkedIn->id)
                && ! $candidates->contains('id', $notCheckedIn->id));
    }

    public function test_manual_registration_in_check_out_mode_records_check_out(): void
    {
        $user = $this->adminUser();
        $activity = $this->ongoingActivity($user);

        $person = Person::query()->create([
            'first_name' => 'Exit',
            'last_name' => 'Candidate',
            'national_id' => '3234567890',
            'person_code' => '14003',
        ]);

        app(ActivityCheckInService::class)->checkInPerson($activity, $person, $user, 'qr');

        $this->actingAs($user);

        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])
            ->call('setMode', 'check-out')
            ->call('selectManualPerson', $person->id)
            ->call('manualCheckIn');

        $attendance = ActivityAttendance::query()
            ->where('activity_id', $activity->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $this->assertNotNull($attendance->checked_out_at);
        $this->assertSame('manual', $attendance->check_out_method);
        $this->assertSame($user->id, $attendance->checked_out_by);
    }

    public function test_check_in_mode_is_unaffected_by_invalid_mode_values(): void
    {
        $user = $this->adminUser();
        $activity = $this->ongoingActivity($user);

        $this->actingAs($user);

        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])
            ->call('setMode', 'bogus-mode')
            ->assertSet('mode', 'check-in');
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function ongoingActivity(User $creator): Activity
    {
        return Activity::query()->create([
            'name' => 'Check Out Activity',
            'activity_type' => 'group_activity',
            'status' => 'ongoing',
            'created_by' => $creator->id,
        ]);
    }
}
