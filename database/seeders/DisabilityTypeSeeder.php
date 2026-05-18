<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DisabilityType;

class DisabilityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            1 => 'جسمی حرکتی',
            2 => 'شنوایی (ناشنوا/کم‌شنوا)',
            3 => 'بینایی (نابینا/کم‌بینا)',
            4 => 'ذهنی',
            5 => 'روانی',
            6 => 'گفتاری',
            7 => 'چند معلولیتی',
            8 => 'سایر معلولیت',
            9 => 'بیماری خاص'
        ];

        foreach ($types as $id => $name) {
            DisabilityType::updateOrCreate(
                ['id' => $id],
                ['name' => $name]);
        }
    }
}
