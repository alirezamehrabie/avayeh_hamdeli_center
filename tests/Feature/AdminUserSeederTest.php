<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_primary_manager_with_full_access(): void
    {
        $password = 'test-bootstrap-secret-2026';
        config()->set('auth.bootstrap_admin_password', $password);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()
            ->where('email', User::PRIMARY_ADMIN_EMAIL)
            ->firstOrFail();

        $this->assertSame(User::PRIMARY_ADMIN_USERNAME, $admin->name);
        $this->assertSame(User::PRIMARY_ADMIN_FIRSTNAME, $admin->first_name);
        $this->assertSame(User::PRIMARY_ADMIN_LASTNAME, $admin->last_name);
        $this->assertTrue($admin->is_admin);
        $this->assertSame(User::ACCESS_LEVEL_MANAGER, $admin->access_level);
        $this->assertSame([User::PERMISSION_FULL_ACCESS], $admin->permissions);
        $this->assertTrue(Hash::check($password, $admin->password));
    }

    public function test_admin_user_seeder_requires_a_strong_password_when_creating_primary_manager(): void
    {
        config()->set('auth.bootstrap_admin_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_BOOTSTRAP_PASSWORD must be set to at least 16 characters');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_admin_user_seeder_rejects_a_short_bootstrap_password(): void
    {
        config()->set('auth.bootstrap_admin_password', 'too-short');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_BOOTSTRAP_PASSWORD must be set to at least 16 characters');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_admin_user_seeder_restores_and_repairs_primary_manager_without_changing_its_password(): void
    {
        $existingPassword = 'existing-production-secret-2026';
        $admin = User::factory()->create([
            'name' => 'broken-admin',
            'email' => User::PRIMARY_ADMIN_EMAIL,
            'first_name' => 'Broken',
            'last_name' => 'Account',
            'is_admin' => false,
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'permissions' => [],
            'password' => $existingPassword,
        ]);

        $admin->delete();

        config()->set('auth.bootstrap_admin_password', 'different-bootstrap-secret-2026');

        $this->seed(AdminUserSeeder::class);

        $admin = User::withTrashed()
            ->where('email', User::PRIMARY_ADMIN_EMAIL)
            ->firstOrFail();

        $this->assertFalse($admin->trashed());
        $this->assertSame(User::PRIMARY_ADMIN_USERNAME, $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertSame(User::ACCESS_LEVEL_MANAGER, $admin->access_level);
        $this->assertSame([User::PERMISSION_FULL_ACCESS], $admin->permissions);
        $this->assertTrue(Hash::check($existingPassword, $admin->password));
        $this->assertFalse(Hash::check('different-bootstrap-secret-2026', $admin->password));
    }
}
