<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobType; // <--- این خط ضروری است و احتمالا جا افتاده

class JobTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'رسمی',
            'پیمانی',
            'قراردادی',
            'روزمزد',
            'ساعتی',
            'موقت (فصلی)',
            'پاره‌وقت',
            'تمام‌وقت',
            'مشاوره‌ای / پروژه‌ای',
            'دوره‌ای / پیمان‌کاری',
            'داوطلبانه / افتخاری',
            'حق‌التدریس / حق‌الزحمه‌ای',
            'نمایندگی / عاملیت فروش',
            'دورکاری / فریلنس',
            'از کار افتاده / مورد خاص'
        ];

        foreach ($types as $type) {
            // بررسی می‌کنیم که اگر قبلاً وجود نداشت، ایجاد شود تا از ارور تکراری جلوگیری شود
            JobType::firstOrCreate(['name' => $type]);
        }
    }
}
