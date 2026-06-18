<?php

namespace Tests\Feature;

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

    public function test_default_status_is_ready_to_hold_for_new_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->assertSet('status', 'ongoing');
    }

    public function test_unknown_status_is_rejected_when_creating_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Summer Workshop')
            ->set('activityType', 'workshop')
            ->set('status', 'archived')
            ->call('save')
            ->assertHasErrors(['status']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Summer Workshop',
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
        $this->assertMatchesRegularExpression('/^ACT-\d{5}$/', (string) $activity->code);
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

    public function test_malformed_jalali_date_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed Date Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28abc')
            ->call('save')
            ->assertHasErrors(['startsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed Date Workshop',
        ]);
    }

    public function test_malformed_jalali_time_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed Time Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30x')
            ->call('save')
            ->assertHasErrors(['startsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed Time Workshop',
        ]);
    }

    public function test_malformed_jalali_end_time_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed End Time Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30')
            ->set('endsAt', '1405/03/28 16:45x')
            ->call('save')
            ->assertHasErrors(['endsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed End Time Workshop',
        ]);
    }

    public function test_ready_to_hold_status_is_preserved_when_start_time_is_in_future(): void
    {
        $user = $this->manager();

        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0, config('app.timezone')));
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Morning Event')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 13:00')
            ->set('status', 'ongoing')
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
            ->set('status', 'cancelled')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_new_activity_code_is_generated_on_save(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Code Generation Workshop')
            ->set('activityType', 'workshop')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Code Generation Workshop')->firstOrFail();

        $this->assertMatchesRegularExpression('/^ACT-\d{5}$/', (string) $activity->code);
    }

    public function test_new_activity_code_ignores_soft_deleted_rows_when_generating_sequence(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        $softDeletedActivity = Activity::query()->create([
            'code' => 'ACT-00041',
            'name' => 'Archived Session',
            'activity_type' => 'workshop',
            'status' => 'closed',
            'created_by' => $user->id,
        ]);

        $softDeletedActivity->delete();

        $nextCode = Activity::generateNextCode();

        $this->assertSame('ACT-00042', $nextCode);
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
