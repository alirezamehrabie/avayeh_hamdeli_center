<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageEncodingTest extends TestCase
{
    public function test_login_page_persian_copy_is_not_mojibake(): void
    {
        $files = [
            resource_path('views/livewire/auth/login.blade.php'),
            app_path('Livewire/Auth/Login.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents);
            $this->assertDoesNotMatchRegularExpression('/[ØÙÛÚÂâ�]/u', $contents, $file.' contains mojibake markers.');
        }

        $this->assertStringContainsString(
            'ورود به سامانه',
            file_get_contents(resource_path('views/livewire/auth/login.blade.php'))
        );

        $this->assertStringContainsString(
            'اطلاعات ورود صحیح نیست.',
            file_get_contents(app_path('Livewire/Auth/Login.php'))
        );
    }

    public function test_login_credentials_keep_ltr_mobile_input_hints(): void
    {
        $contents = file_get_contents(resource_path('views/livewire/auth/login.blade.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('inputmode="email"', $contents);
        $this->assertStringContainsString('autocomplete="username"', $contents);
        $this->assertStringContainsString('autocomplete="current-password"', $contents);
        $this->assertGreaterThanOrEqual(2, substr_count($contents, 'dir="ltr"'));
        $this->assertGreaterThanOrEqual(2, substr_count($contents, 'lang="en"'));
        $this->assertGreaterThanOrEqual(2, substr_count($contents, 'autocorrect="off"'));
        $this->assertGreaterThanOrEqual(2, substr_count($contents, 'spellcheck="false"'));
    }

    public function test_login_credentials_are_not_live_bound(): void
    {
        $contents = file_get_contents(resource_path('views/livewire/auth/login.blade.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('wire:model="email"', $contents);
        $this->assertStringContainsString('wire:model="password"', $contents);
        $this->assertStringNotContainsString('wire:model.live', $contents);
        $this->assertStringNotContainsString('wire:model.live.debounce', $contents);
    }

    public function test_login_uses_dedicated_auth_layout(): void
    {
        $component = file_get_contents(app_path('Livewire/Auth/Login.php'));
        $layout = file_get_contents(resource_path('views/layouts/auth.blade.php'));

        $this->assertIsString($component);
        $this->assertIsString($layout);
        $this->assertStringContainsString("#[Layout('layouts.auth')]", $component);
        $this->assertStringContainsString("@vite(['resources/css/app.css', 'resources/js/app.js'])", $layout);
        $this->assertStringContainsString('@livewireScriptConfig', $layout);
        $this->assertStringNotContainsString("@include('layouts.header')", $layout);
        $this->assertStringNotContainsString('class="container py-4"', $layout);
        $this->assertStringNotContainsString("asset('css/app.css')", $layout);
    }

    public function test_login_page_does_not_render_shared_public_header(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('layouts.header')
            ->assertDontSeeHtml('class="container py-4"');
    }
}
