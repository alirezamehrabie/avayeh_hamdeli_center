<?php

namespace Tests\Unit;

use App\Models\SocialWorker;
use App\Models\User;
use App\Services\LoginRedirector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_user_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]);

        $this->assertSame(route('admin.dashboard'), $this->redirector()->pathFor($user));
    }

    public function test_social_worker_redirects_to_social_worker_dashboard(): void
    {
        $socialWorker = SocialWorker::query()->create([
            'worker_code' => 10,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'mobile' => '09120000000',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'permissions' => [],
            'social_worker_id' => $socialWorker->id,
        ]);

        $this->assertSame(route('social-worker.dashboard'), $this->redirector()->pathFor($user));
    }

    public function test_distribution_operator_redirects_to_define_service(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $this->assertSame(route('distribution-operator.define-service'), $this->redirector()->pathFor($user));
    }

    public function test_child_supporter_redirects_to_child_supporter_dashboard(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_CHILD_SUPPORTER,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->assertSame(route('child-supporter.dashboard'), $this->redirector()->pathFor($user));
    }

    public function test_user_without_authorized_panel_redirects_to_root_fallback(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->assertSame('/', $this->redirector()->pathFor($user));
    }

    public function test_user_model_delegates_panel_redirect_to_resolver(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->assertSame($this->redirector()->pathFor($user), $user->getPanelRedirectPath());
    }

    private function redirector(): LoginRedirector
    {
        return app(LoginRedirector::class);
    }
}
