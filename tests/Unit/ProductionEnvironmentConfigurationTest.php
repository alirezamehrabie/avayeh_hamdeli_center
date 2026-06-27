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

    public function test_bootstrap_configures_trusted_proxy_headers_from_environment(): void
    {
        $contents = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('$middleware->trustProxies(', $contents);
        $this->assertStringContainsString("at: env('TRUSTED_PROXIES')", $contents);
        $this->assertStringContainsString('Request::HEADER_X_FORWARDED_PROTO', $contents);
        $this->assertStringContainsString('Request::HEADER_X_FORWARDED_HOST', $contents);
    }
}
