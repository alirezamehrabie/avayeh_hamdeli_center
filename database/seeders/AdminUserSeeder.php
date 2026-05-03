<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد یک کاربر با مشخصات مدیر
        User::updateOrCreate(
            ['email' => 'admin@avayeh-hamdeli.ir'], // اگر این ایمیل وجود نداشت بساز، اگر داشت آپدیت کن
            [
                'name' => 'مدیر سیستم',
                'password' => Hash::make('123456'), // حتماً بعداً رمز را تغییر دهید
                'is_admin' => true, // تنظیم به عنوان مدیر
            ]
        );
    }
}
