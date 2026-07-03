<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityList;
use App\Livewire\Admin\DashboardHome;
use App\Livewire\Services\ServiceArchive;
use App\Livewire\Services\ServiceReports;
use App\Models\Activity;
use App\Models\Guardian;
use App\Models\Service;
use App\Models\ServiceName;
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

    public function test_legacy_people_case_file_route_name_opens_case_file_section(): void
    {
        $this->actingAs($this->manager());

        $this->get(route('people.case-file'))
            ->assertOk()
            ->assertSee('beneficiary-case-file');
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

    public function test_service_reports_route_opens_service_reports_section(): void
    {
        $this->actingAs($this->adminPanelUserWithoutFullAccess());

        $this->get('/admin/reports/services')
            ->assertOk()
            ->assertSeeLivewire(ServiceReports::class);
    }

    public function test_service_reports_route_with_id_deep_links_to_selected_service(): void
    {
        $this->actingAs($this->manager());
        $service = $this->service();

        $this->get('/admin/reports/services?id='.$service->id)
            ->assertOk()
            ->assertSeeLivewire(ServiceReports::class);
    }

    public function test_service_report_context_survives_mount_from_url(): void
    {
        $this->actingAs($this->manager());
        $service = $this->service();

        Livewire::withQueryParams([
            'section' => 'advanced-service-report',
            'id' => $service->id,
        ])
            ->test(DashboardHome::class)
            ->assertSet('activeSection', 'advanced-service-report')
            ->assertSet('sectionContextId', $service->id)
            ->assertSet('serviceReportServiceId', $service->id);
    }

    public function test_selecting_non_context_section_clears_service_report_context(): void
    {
        $this->actingAs($this->manager());
        $service = $this->service();

        Livewire::withQueryParams([
            'section' => 'advanced-service-report',
            'id' => $service->id,
        ])
            ->test(DashboardHome::class)
            ->call('selectSection', 'advanced-operator-report')
            ->assertSet('activeSection', 'advanced-operator-report')
            ->assertSet('sectionContextId', null)
            ->assertSet('serviceReportServiceId', null);
    }

    public function test_legacy_dashboard_section_query_still_opens_service_reports(): void
    {
        $this->actingAs($this->manager());
        $service = $this->service();

        $this->get('/admin/dashboard?section=advanced-service-report&id='.$service->id)
            ->assertOk()
            ->assertSeeLivewire(ServiceReports::class);
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

    private function service(): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Test Service Name',
            'sort_id' => ((int) ServiceName::query()->max('sort_id') ?? 0) + 1,
        ]);

        return Service::query()->create([
            'code' => 'SVC-'.random_int(10000, 99999),
            'service_name_id' => $serviceName->id,
            'name' => 'Test Service',
            'service_type' => 'individual',
            'total_quantity' => 100,
            'supports_gate_delivery' => true,
            'distribution_start_date' => now()->toDateString(),
            'created_by' => auth()->id() ?? $this->manager()->id,
        ]);
    }
}
