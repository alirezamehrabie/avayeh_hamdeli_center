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
}
