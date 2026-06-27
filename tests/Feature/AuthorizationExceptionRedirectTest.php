<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuthorizationExceptionRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_browser_403_redirects_to_safe_panel_with_message(): void
    {
        Log::spy();

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

        Log::shouldHaveReceived('warning')
            ->with('auth.authorization.safe_panel_redirect', \Mockery::on(function (array $context) use ($operator): bool {
                return ($context['user_id'] ?? null) === $operator->id
                    && ($context['access_level'] ?? null) === User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR
                    && ($context['route'] ?? null) === 'admin.user-definition'
                    && ($context['redirect_url'] ?? null) === route('distribution-operator.define-service');
            }))
            ->once();
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
