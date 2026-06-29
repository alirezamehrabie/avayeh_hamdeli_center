<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardHome;
use App\Livewire\Activities\ActivityList;
use App\Livewire\Services\ServiceArchive;
use App\Models\Activity;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardHomeNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_section_takes_precedence_over_activity_route_default(): void
    {
        $this->actingAs($this->manager());

        $this->get('/admin/activities/activity-definition?section=activity-list')
            ->assertOk()
            ->assertSeeLivewire(ActivityList::class);
    }

    public function test_admin_panel_user_without_full_access_can_open_activity_list_route(): void
    {
        $this->actingAs($this->adminPanelUserWithoutFullAccess());

        $this->get('/admin/activities/activity-list')
            ->assertOk()
            ->assertSeeLivewire(ActivityList::class);
    }

    public function test_service_archive_route_opens_archive_section(): void
    {
        $this->actingAs($this->manager());

        $this->get('/admin/services/service-archive')
            ->assertOk()
            ->assertSeeLivewire(ServiceArchive::class);
    }

    public function test_activity_definition_context_survives_mount_from_url(): void
    {
        $this->actingAs($this->manager());
        $activity = $this->activity();

        Livewire::withQueryParams([
            'section' => 'activity-definition',
            'activity' => $activity->id,
        ])
            ->test(DashboardHome::class)
            ->assertSet('activeSection', 'activity-definition')
            ->assertSet('editingActivityId', $activity->id)
            ->assertSet('scanningActivityId', null);
    }

    public function test_activity_scanner_context_survives_mount_from_url(): void
    {
        $this->actingAs($this->manager());
        $activity = $this->activity();

        Livewire::withQueryParams([
            'section' => 'activity-scanner',
            'activity' => $activity->id,
        ])
            ->test(DashboardHome::class)
            ->assertSet('activeSection', 'activity-scanner')
            ->assertSet('scanningActivityId', $activity->id)
            ->assertSet('editingActivityId', null);
    }

    public function test_guardian_edit_context_survives_mount_from_url(): void
    {
        $this->actingAs($this->manager());
        $guardian = $this->guardian();

        Livewire::withQueryParams([
            'section' => 'guardian-edit',
            'id' => $guardian->id,
        ])
            ->test(DashboardHome::class)
            ->assertSet('activeSection', 'guardian-edit')
            ->assertSet('sectionContextId', $guardian->id)
            ->assertSet('editingGuardianId', $guardian->id);
    }

    public function test_selecting_non_activity_section_clears_activity_context(): void
    {
        $this->actingAs($this->manager());
        $activity = $this->activity();

        Livewire::withQueryParams([
            'section' => 'activity-scanner',
            'activity' => $activity->id,
        ])
            ->test(DashboardHome::class)
            ->call('selectSection', 'activity-list')
            ->assertSet('activeSection', 'activity-list')
            ->assertSet('activityContextId', null)
            ->assertSet('scanningActivityId', null)
            ->assertSet('editingActivityId', null);
    }

    public function test_selecting_non_context_section_clears_guardian_context(): void
    {
        $this->actingAs($this->manager());
        $guardian = $this->guardian();

        Livewire::withQueryParams([
            'section' => 'guardian-edit',
            'id' => $guardian->id,
        ])
            ->test(DashboardHome::class)
            ->call('selectSection', 'guardians-list')
            ->assertSet('activeSection', 'guardians-list')
            ->assertSet('sectionContextId', null)
            ->assertSet('editingGuardianId', null);
    }

    public function test_url_section_change_clears_stale_activity_context(): void
    {
        $this->actingAs($this->manager());
        $activity = $this->activity();

        Livewire::withQueryParams([
            'section' => 'activity-scanner',
            'activity' => $activity->id,
        ])
            ->test(DashboardHome::class)
            ->set('activeSection', 'activity-list')
            ->assertSet('activeSection', 'activity-list')
            ->assertSet('activityContextId', null)
            ->assertSet('scanningActivityId', null)
            ->assertSet('editingActivityId', null);
    }

    public function test_url_section_change_clears_stale_guardian_context(): void
    {
        $this->actingAs($this->manager());
        $guardian = $this->guardian();

        Livewire::withQueryParams([
            'section' => 'guardian-edit',
            'id' => $guardian->id,
        ])
            ->test(DashboardHome::class)
            ->set('activeSection', 'guardians-list')
            ->assertSet('activeSection', 'guardians-list')
            ->assertSet('sectionContextId', null)
            ->assertSet('editingGuardianId', null);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function adminPanelUserWithoutFullAccess(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]);
    }

    private function activity(): Activity
    {
        return Activity::query()->create([
            'name' => 'Navigation Activity',
            'activity_type' => 'workshop',
            'status' => 'ongoing',
        ]);
    }

    private function guardian(): Guardian
    {
        return Guardian::query()->create([
            'guardian_code' => random_int(100000, 999999),
            'first_name' => 'Guardian',
            'last_name' => 'Navigation',
        ]);
    }
}
