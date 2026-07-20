<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withTrashed()->firstOrNew([
            'email' => User::PRIMARY_ADMIN_EMAIL,
        ]);

        if (! $admin->exists) {
            $admin->password = $this->bootstrapPassword();
        }

        $admin->fill([
            'name' => User::PRIMARY_ADMIN_USERNAME,
            'first_name' => User::PRIMARY_ADMIN_FIRSTNAME,
            'last_name' => User::PRIMARY_ADMIN_LASTNAME,
            'is_admin' => true,
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ])->save();

        if ($admin->trashed()) {
            $admin->restore();
        }
    }

    private function bootstrapPassword(): string
    {
        $password = (string) config('auth.bootstrap_admin_password');

        if (trim($password) === '' || strlen($password) < 16) {
            throw new RuntimeException(
                'ADMIN_BOOTSTRAP_PASSWORD must be set to at least 16 characters before creating the primary administrator.'
            );
        }

        return $password;
    }
}
