<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['title' => 'سیکل', 'sort_order' => 1],
            ['title' => 'دیپلم', 'sort_order' => 2],
            ['title' => 'کاردانی', 'sort_order' => 3],
            ['title' => 'کارشناسی', 'sort_order' => 4],
            ['title' => 'کارشناسی ارشد', 'sort_order' => 5],
            ['title' => 'دکتری', 'sort_order' => 6],
        ];

        foreach ($levels as $level) {
            DB::table('academic_levels')->updateOrInsert(
                ['title' => $level['title']],
                ['sort_order' => $level['sort_order'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
