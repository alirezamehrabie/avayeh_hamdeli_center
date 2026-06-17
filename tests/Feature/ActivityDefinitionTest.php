<?php

namespace Tests\Feature;

use App\Console\Commands\SyncActivityStatuses;
use App\Livewire\Activities\ActivityDefinition;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_status_is_ongoing_for_new_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->assertSet('status', 'ongoing');
    }

    public function test_manager_can_choose_scheduled_status_when_creating_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Summer Workshop')
            ->set('activityType', 'workshop')
            ->set('status', 'scheduled')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'name' => 'Summer Workshop',
            'activity_type' => 'workshop',
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);
    }

    public function test_activity_datetimes_are_saved_correctly_from_jalali_input(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Summer Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30')
            ->set('endsAt', '1405/03/28 16:45')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Summer Workshop')->firstOrFail();

        $this->assertSame('2026-06-18 14:30', $activity->starts_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
        $this->assertSame('2026-06-18 16:45', $activity->ends_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
    }

    public function test_activity_datetimes_accept_persian_digits_from_picker(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Persian Digits Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '۱۴۰۵/۰۳/۲۸ ۱۴:۳۰')
            ->set('endsAt', '۱۴۰۵/۰۳/۲۸ ۱۶:۴۵')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Persian Digits Workshop')->firstOrFail();

        $this->assertSame('2026-06-18 14:30', $activity->starts_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
        $this->assertSame('2026-06-18 16:45', $activity->ends_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
    }

    public function test_scheduled_status_changes_to_ongoing_when_start_time_has_passed_on_save(): void
    {
        $user = $this->manager();

        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0, config('app.timezone')));
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Morning Event')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 11:00')
            ->set('status', 'scheduled')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'name' => 'Morning Event',
            'status' => 'ongoing',
        ]);

        Carbon::setTestNow();
    }

    public function test_manager_can_change_status_when_editing_activity(): void
    {
        $user = $this->manager();
        $activity = Activity::query()->create([
            'name' => 'Camp Session',
            'activity_type' => 'camp',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class, ['activityId' => $activity->id])
            ->assertSet('status', 'draft')
            ->set('status', 'scheduled')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_scheduled_activities_are_promoted_to_ongoing_by_sync_command(): void
    {
        $user = $this->manager();

        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0, config('app.timezone')));

        $activity = Activity::query()->create([
            'name' => 'Camp Session',
            'activity_type' => 'camp',
            'status' => 'scheduled',
            'starts_at' => Carbon::now(config('app.timezone'))->subMinute(),
            'created_by' => $user->id,
        ]);

        $this->artisan(SyncActivityStatuses::class)
            ->expectsOutput('Updated 1 scheduled activities.')
            ->assertSuccessful();

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'ongoing',
        ]);

        Carbon::setTestNow();
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
