<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد/به‌روزرسانی حساب مدیریت اصلی سیستم (غیرقابل حذف/تنزل)
        $admin = User::withTrashed()->updateOrCreate(
            ['email' => User::PRIMARY_ADMIN_EMAIL],
            [
                'name' => User::PRIMARY_ADMIN_USERNAME,
                'first_name' => User::PRIMARY_ADMIN_FIRSTNAME,
                'last_name' => User::PRIMARY_ADMIN_LASTNAME,
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'access_level' => User::ACCESS_LEVEL_MANAGER,
                'permissions' => [User::PERMISSION_FULL_ACCESS],
            ]
        );

        if ($admin->trashed()) {
            $admin->restore();
        }
    }
}
