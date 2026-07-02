<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityOperatorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionOperatorActivityAttendanceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_distribution_operator_cannot_access_attendance_by_role_alone(): void
    {
        $operator = $this->distributionOperator();

        $this->assertFalseAccess($operator);
        $this->assertFalse($operator->hasActivityAttendanceAccess());

        // The route is gated by can:access-activity-operator-panel, so a bare
        // distribution operator is denied entry (never a 200).
        $response = $this->actingAs($operator)->get(route('activity-operator.dashboard'));
        $this->assertNotSame(200, $response->getStatusCode());
    }

    private function assertFalseAccess(User $operator): void
    {
        $this->assertFalse($operator->canAccessActivityOperatorPanel());
    }

    public function test_admin_granted_permission_unlocks_attendance_panel(): void
    {
        $operator = $this->distributionOperator([User::PERMISSION_ACTIVITY_ATTENDANCE_REGISTER]);

        $this->assertTrue($operator->canAccessActivityOperatorPanel());

        $this->actingAs($operator)
            ->get(route('activity-operator.dashboard'))
            ->assertOk();
    }

    public function test_activity_assignment_alone_unlocks_the_assigned_activity(): void
    {
        $admin = $this->admin();
        $operator = $this->distributionOperator();
        $activity = $this->activity();

        ActivityOperatorAssignment::create([
            'activity_id' => $activity->id,
            'user_id' => $operator->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $operator->refresh();

        $this->assertTrue($operator->canAccessActivityOperatorPanel());
        $this->assertTrue($operator->canAccessActivity($activity));
        $this->assertFalse($operator->canAccessActivity($this->activity('Unassigned')));
    }

    public function test_removed_assignment_without_permission_revokes_access(): void
    {
        $admin = $this->admin();
        $operator = $this->distributionOperator();
        $activity = $this->activity();

        $assignment = ActivityOperatorAssignment::create([
            'activity_id' => $activity->id,
            'user_id' => $operator->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $assignment->delete();
        $operator->refresh();

        $this->assertFalse($operator->canAccessActivityOperatorPanel());
        $this->assertFalse($operator->canAccessActivity($activity));
    }

    public function test_admin_can_grant_attendance_permission_to_distribution_operator(): void
    {
        $this->assertContains(
            User::PERMISSION_ACTIVITY_ATTENDANCE_REGISTER,
            array_keys(User::permissionOptionsForAccessLevel(User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR))
        );
    }

    public function test_activity_operator_workflow_is_unchanged(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ACTIVITY_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_ACTIVITY_ATTENDANCE_REGISTER],
        ]);

        $this->assertTrue($operator->canAccessActivityOperatorPanel());

        $this->actingAs($operator)
            ->get(route('activity-operator.dashboard'))
            ->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function distributionOperator(array $permissions = []): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => $permissions,
        ]);
    }

    private function activity(string $name = 'Attendance Workshop'): Activity
    {
        return Activity::query()->create([
            'name' => $name,
            'activity_type' => 'workshop',
            'starts_at' => '2026-06-18 09:00:00',
            'status' => 'ongoing',
        ]);
    }
}
