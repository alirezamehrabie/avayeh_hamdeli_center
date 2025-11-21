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
            'جسمی حرکتی',
            'شنوایی (ناشنوا/کم‌شنوا)',
            'بینایی (نابینا/کم‌بینا)',
            'ذهنی',
            'روانی',
            'گفتاری',
            'چند معلولیتی',
            'سایر',
        ];

        foreach ($types as $type) {
            DisabilityType::create(['name' => $type]);
        }
    }
}
