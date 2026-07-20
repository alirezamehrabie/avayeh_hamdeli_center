<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductionEnvironmentConfigurationTest extends TestCase
{
    public function test_production_session_cookie_settings_are_explicit(): void
    {
        $contents = file_get_contents(base_path('.env.production'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_URL=https://avaaye-hamdely.ir', $contents);
        $this->assertStringContainsString('SESSION_DOMAIN=avaaye-hamdely.ir', $contents);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $contents);
        $this->assertStringContainsString('SESSION_SAME_SITE=lax', $contents);
        $this->assertStringContainsString('TRUSTED_PROXIES=REMOTE_ADDR', $contents);
    }

    public function test_bootstrap_configures_trusted_proxy_headers_from_config(): void
    {
        $bootstrapContents = file_get_contents(base_path('bootstrap/app.php'));
        $configContents = file_get_contents(config_path('app.php'));

        $this->assertIsString($bootstrapContents);
        $this->assertIsString($configContents);
        $this->assertStringContainsString('$middleware->trustProxies(', $bootstrapContents);
        $this->assertStringContainsString("at: config('app.trusted_proxies')", $bootstrapContents);
        $this->assertStringNotContainsString("env('TRUSTED_PROXIES')", $bootstrapContents);
        $this->assertStringContainsString('Request::HEADER_X_FORWARDED_PROTO', $bootstrapContents);
        $this->assertStringContainsString('Request::HEADER_X_FORWARDED_HOST', $bootstrapContents);
        $this->assertStringContainsString("'trusted_proxies' => env('TRUSTED_PROXIES')", $configContents);
    }
}
