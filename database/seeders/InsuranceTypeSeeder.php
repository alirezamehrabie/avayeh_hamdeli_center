<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceType; // فراموش نشود

class InsuranceTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'بیمه تأمین اجتماعی',
            'بیمه سلامت ایرانیان',
            'بیمه خدمات درمانی نیروهای مسلح',
            'بیمه کارکنان دولت (خدمات درمانی سابق)',
            'بیمه روستایی و عشایری',
            'بیمه کارگران ساختمانی',
            'بیمه بیکاری',
            'بیمه بازنشستگی و ازکارافتادگی',
        ];

        foreach ($types as $type) {
            InsuranceType::firstOrCreate(['name' => $type]);
        }
    }
}
