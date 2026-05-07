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
        User::updateOrCreate(
            ['email' => User::PRIMARY_ADMIN_EMAIL],
            [
                'name' => User::PRIMARY_ADMIN_USERNAME,
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'access_level' => User::ACCESS_LEVEL_MANAGER,
            ]
        );
    }
}
