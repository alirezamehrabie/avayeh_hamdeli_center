<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HarmTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $items = [
            'فوت پدر',
            'فوت مادر',
            'اعتیاد والدین',
            'بیمار اعصاب و روان والدین',
            'زندانی والدین',
            'کم بضاعت (بی‌بضاعت)',
            'فرزند طلاق',
            'آسیب دیده اجتماعی (فردی)',
            'افسردگی (فردی)',
            'بیماری صعب العلاج'
        ];

        foreach ($items as $title) {
            \App\Models\HarmType::create(['title' => $title]);
        }
    }

}
