<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionOperatorPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_distribution_gate_permissions_are_assignable_options(): void
    {
        $options = User::permissionOptions();

        $this->assertSame('Inbound Gate Access', $options[User::PERMISSION_DISTRIBUTION_INBOUND_GATE]);
        $this->assertSame('Delivery Gate Access', $options[User::PERMISSION_DISTRIBUTION_DELIVERY_GATE]);
        $this->assertSame('Outbound Gate Access', $options[User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE]);
    }

    public function test_distribution_access_definitions_map_access_types_to_permissions_and_gates(): void
    {
        $definitions = User::distributionAccessDefinitions();

        $this->assertSame(User::PERMISSION_DISTRIBUTION_INBOUND_GATE, $definitions[User::DISTRIBUTION_ACCESS_INBOUND]['permission']);
        $this->assertSame('access-distribution-inbound-gate', $definitions[User::DISTRIBUTION_ACCESS_INBOUND]['gate']);
        $this->assertSame(User::PERMISSION_DISTRIBUTION_DELIVERY_GATE, $definitions[User::DISTRIBUTION_ACCESS_DELIVERY]['permission']);
        $this->assertSame('access-distribution-delivery-gate', $definitions[User::DISTRIBUTION_ACCESS_DELIVERY]['gate']);
        $this->assertSame(User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE, $definitions[User::DISTRIBUTION_ACCESS_OUTBOUND]['permission']);
        $this->assertSame('access-distribution-outbound-gate', $definitions[User::DISTRIBUTION_ACCESS_OUTBOUND]['gate']);
    }

    public function test_distribution_gate_access_requires_operator_role_and_matching_permission(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $adminWithPermission = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $this->assertTrue($operator->can('access-distribution-inbound-gate'));
        $this->assertFalse($operator->can('access-distribution-delivery-gate'));
        $this->assertFalse($operator->can('access-distribution-outbound-gate'));
        $this->assertFalse($adminWithPermission->can('access-distribution-inbound-gate'));
    }

    public function test_full_access_still_collapses_other_permissions(): void
    {
        $this->assertSame([
            User::PERMISSION_FULL_ACCESS,
        ], User::normalizePermissionKeys([
            User::PERMISSION_DISTRIBUTION_INBOUND_GATE,
            User::PERMISSION_DISTRIBUTION_DELIVERY_GATE,
            User::PERMISSION_FULL_ACCESS,
        ]));
    }
}
