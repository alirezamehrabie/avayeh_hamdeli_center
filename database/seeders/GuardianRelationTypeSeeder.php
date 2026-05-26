<?php

namespace Database\Seeders;

use App\Models\GuardianRelationType;
use Illuminate\Database\Seeder;

class GuardianRelationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'پدر',
            'مادر',
            'پدر بزرگ',
            'مادر بزرگ',
            'سایر (عمو / عمه)',
            'سایر (خاله / دایی)',
            'نا پدری',
            'نا مادری',
        ];

        foreach ($types as $type) {
            GuardianRelationType::updateOrCreate(['title' => $type], ['title' => $type]);
        }
    }
}
