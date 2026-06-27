<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class LoginAuthenticationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_shows_form_level_auth_error(): void
    {
        User::factory()->create([
            'email' => 'operator@example.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'operator@example.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['auth'])
            ->assertHasNoErrors(['email', 'password'])
            ->assertSee('اطلاعات ورود صحیح نیست.');

        $this->assertGuest();
    }

    public function test_successful_login_ignores_stale_intended_url_and_uses_role_panel(): void
    {
        User::factory()->create([
            'email' => 'operator@example.test',
            'password' => 'correct-password',
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        session()->put('url.intended', route('admin.user-definition'));

        Livewire::test(Login::class)
            ->set('email', 'operator@example.test')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertRedirect(route('distribution-operator.define-service'));

        $this->assertAuthenticated();
        $this->assertNull(session('url.intended'));
    }

    public function test_login_rejects_account_without_authorized_panel_with_support_message(): void
    {
        User::factory()->create([
            'email' => 'regular@example.test',
            'password' => 'correct-password',
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [],
        ]);

        Livewire::test(Login::class)
            ->set('email', 'regular@example.test')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertHasErrors(['auth'])
            ->assertSee('حساب کاربری شما هنوز به پنل فعال متصل نشده است.');

        $this->assertGuest();
    }

    public function test_duplicate_in_flight_login_submit_is_rejected_without_authenticating(): void
    {
        User::factory()->create([
            'email' => 'operator@example.test',
            'password' => 'correct-password',
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        Cache::add('login:attempting:operator@example.test|127.0.0.1', true, 15);

        Livewire::test(Login::class)
            ->set('email', 'operator@example.test')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertHasErrors(['auth'])
            ->assertSee('در حال بررسی اطلاعات ورود هستیم.');

        $this->assertGuest();
    }

    public function test_credential_changes_clear_form_level_auth_error(): void
    {
        User::factory()->create([
            'email' => 'operator@example.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'operator@example.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['auth'])
            ->set('password', 'different-password')
            ->assertHasNoErrors(['auth']);
    }

    public function test_required_fields_still_use_field_level_validation_errors(): void
    {
        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['email', 'password'])
            ->assertHasNoErrors(['auth'])
            ->assertSee('نام کاربری یا ایمیل را وارد کنید.')
            ->assertSee('رمز عبور را وارد کنید.');
    }

    public function test_short_password_shows_clear_field_level_validation_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'operator@example.test')
            ->set('password', '12345')
            ->call('login')
            ->assertHasErrors(['password'])
            ->assertHasNoErrors(['auth'])
            ->assertSee('رمز عبور باید حداقل ۶ کاراکتر باشد.');
    }

    public function test_support_phone_link_is_hidden_when_not_configured(): void
    {
        config()->set('services.support.phone', null);
        config()->set('services.support.phone_label', null);

        Livewire::test(Login::class)
            ->assertSee('اطلاعات تماس از طریق مدیر داخلی مرکز اعلام می‌شود.')
            ->assertDontSee('tel:', false);
    }

    public function test_support_phone_link_uses_sanitized_dialable_value(): void
    {
        config()->set('services.support.phone', '+98 (21) 1234-5678');
        config()->set('services.support.phone_label', '۰۲۱-۱۲۳۴۵۶۷۸');

        Livewire::test(Login::class)
            ->assertSee('تماس با پشتیبانی')
            ->assertSee('۰۲۱-۱۲۳۴۵۶۷۸')
            ->assertSee('tel:+982112345678', false);
    }
}
