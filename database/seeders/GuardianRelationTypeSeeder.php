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
        ];

        foreach ($types as $type) {
            GuardianRelationType::create(['title' => $type]);
        }
    }
}
