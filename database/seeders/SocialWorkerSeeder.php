<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SocialWorker;

class SocialWorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workers = [
            [
                'first_name' => 'مریم',
                'last_name' => 'احمدی',
                'national_id' => '1234567890',
                'role' => 'مددکار ارشد',
            ],
            [
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'national_id' => '0987654321',
                'role' => 'مددکار',
            ],
            [
                'first_name' => 'زهرا',
                'last_name' => 'حسینی',
                'national_id' => '1122334455',
                'role' => 'مددکار',
            ],
        ];

        foreach ($workers as $workerData) {
            SocialWorker::updateOrCreate(
                ['national_id' => $workerData['national_id']], // کلید جستجو برای جلوگیری از ثبت تکراری
                $workerData // مقادیری که باید ثبت یا به‌روز شوند
            );
        }
    }
}
