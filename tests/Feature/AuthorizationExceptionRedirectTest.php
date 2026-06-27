<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationExceptionRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_browser_403_redirects_to_safe_panel_with_message(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $response = $this->actingAs($operator)
            ->get(route('admin.user-definition'));

        $response
            ->assertRedirect(route('distribution-operator.define-service'))
            ->assertSessionHas('error', 'شما به این بخش دسترسی ندارید و به پنل مجاز خود هدایت شدید.');
    }

    public function test_json_403_keeps_forbidden_response(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $this->actingAs($operator)
            ->getJson(route('admin.user-definition'))
            ->assertForbidden();
    }
}
