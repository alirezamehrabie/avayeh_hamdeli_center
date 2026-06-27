<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_primary_manager_with_full_access(): void
    {
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
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }

    public function test_admin_user_seeder_restores_and_repairs_primary_manager(): void
    {
        $admin = User::factory()->create([
            'name' => 'broken-admin',
            'email' => User::PRIMARY_ADMIN_EMAIL,
            'first_name' => 'Broken',
            'last_name' => 'Account',
            'is_admin' => false,
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'permissions' => [],
        ]);

        $admin->delete();

        $this->seed(AdminUserSeeder::class);

        $admin = User::withTrashed()
            ->where('email', User::PRIMARY_ADMIN_EMAIL)
            ->firstOrFail();

        $this->assertFalse($admin->trashed());
        $this->assertSame(User::PRIMARY_ADMIN_USERNAME, $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertSame(User::ACCESS_LEVEL_MANAGER, $admin->access_level);
        $this->assertSame([User::PERMISSION_FULL_ACCESS], $admin->permissions);
    }
}
