<?php

namespace Tests\Unit;

use Tests\TestCase;

class AppServiceProviderBootTest extends TestCase
{
    public function test_app_service_provider_does_not_manage_primary_admin_account_on_boot(): void
    {
        $contents = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('Schema::hasTable', $contents);
        $this->assertStringNotContainsString('Schema::hasColumn', $contents);
        $this->assertStringNotContainsString('firstOrCreate', $contents);
        $this->assertStringNotContainsString('updateOrCreate', $contents);
        $this->assertStringNotContainsString('admin123', $contents);
    }
}
