<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityList;
use App\Livewire\Activities\ActivityScanner;
use App\Livewire\Admin\GateReport;
use App\Livewire\ChildSupporters\SponsorList;
use App\Livewire\ChildSupporters\SponsorRegistration;
use App\Livewire\Services\ServiceReports;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperatorRestrictedSectionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_operator_cannot_access_restricted_routes_or_activity_endpoints(): void
    {
        $operator = $this->registrationOperator();
        $activity = $this->activity($operator);

        $this->actingAs($operator);

        foreach ([
            route('admin.activity-list'),
            route('admin.service-reports'),
            route('admin.child-supporters.sponsor-registration'),
            route('admin.child-supporters.sponsor-list'),
        ] as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $this->postJson(route('admin.activities.check-in.qr', $activity), [
            'token' => 'blocked',
        ])->assertForbidden();

        $this->postJson(route('admin.activities.check-out.qr', $activity), [
            'token' => 'blocked',
        ])->assertForbidden();
    }

    public function test_registration_operator_cannot_open_restricted_dashboard_deep_links(): void
    {
        $this->actingAs($this->registrationOperator());

        foreach ([
            'advanced-service-report',
            'advanced-gate-report',
            'activity-definition',
            'activity-list',
            'activity-scanner',
            'activity-operator-assignments',
            'child-supporter-sponsor-registration',
            'child-supporter-sponsor-edit',
            'child-supporter-sponsor-list',
        ] as $section) {
            $this->getJson(route('admin.dashboard', ['section' => $section]))
                ->assertForbidden();
        }
    }

    public function test_admin_role_operator_without_full_access_is_also_restricted(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]);

        $this->actingAs($operator)
            ->getJson(route('admin.service-reports'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->getJson(route('admin.dashboard', ['section' => 'activity-list']))
            ->assertForbidden();

        $this->actingAs($operator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee("section: 'activity-list'", false)
            ->assertDontSee("section: 'advanced-service-report'", false)
            ->assertDontSee("section: 'advanced-gate-report'", false)
            ->assertDontSee("section: 'child-supporter-sponsor-registration'", false)
            ->assertDontSee("selectSection('activity-definition')", false)
            ->assertDontSee("selectSection('child-supporter-sponsor-registration')", false);
    }

    public function test_registration_operator_cannot_mount_restricted_livewire_components(): void
    {
        $operator = $this->registrationOperator();
        $activity = $this->activity($operator);

        $this->actingAs($operator);

        Livewire::test(ActivityList::class)->assertForbidden();
        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])->assertForbidden();
        Livewire::test(GateReport::class)->assertForbidden();
        Livewire::test(ServiceReports::class)->assertForbidden();
        Livewire::test(SponsorRegistration::class)->assertForbidden();
        Livewire::test(SponsorList::class)->assertForbidden();
    }

    public function test_registration_operator_navigation_hides_restricted_sections(): void
    {
        $this->actingAs($this->registrationOperator())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee("section: 'activity-list'", false)
            ->assertDontSee("section: 'advanced-service-report'", false)
            ->assertDontSee("section: 'advanced-gate-report'", false)
            ->assertDontSee("section: 'child-supporter-sponsor-registration'", false)
            ->assertDontSee("selectSection('activity-definition')", false)
            ->assertDontSee("selectSection('child-supporter-sponsor-registration')", false)
            ->assertSee("section: 'advanced-operator-report'", false);
    }

    public function test_full_access_admin_keeps_access_to_restricted_sections(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('admin.activity-list'))
            ->assertOk()
            ->assertSeeLivewire(ActivityList::class);

        $this->actingAs($manager)
            ->get(route('admin.service-reports'))
            ->assertOk()
            ->assertSeeLivewire(ServiceReports::class);

        $this->actingAs($manager)
            ->get(route('admin.child-supporters.sponsor-registration'))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.dashboard', ['section' => 'advanced-gate-report']))
            ->assertOk()
            ->assertSeeLivewire(GateReport::class);
    }

    private function registrationOperator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
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

    private function activity(User $creator): Activity
    {
        return Activity::query()->create([
            'name' => 'Restricted Activity',
            'activity_type' => 'workshop',
            'status' => 'ongoing',
            'created_by' => $creator->id,
        ]);
    }
}
