<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityDefinition;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_choose_status_when_creating_activity(): void
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

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
