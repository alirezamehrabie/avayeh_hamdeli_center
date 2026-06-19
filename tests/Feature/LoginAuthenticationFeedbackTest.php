<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
