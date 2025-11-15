<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationLevel;

class EducationLevelSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            ['name' => 'بی سواد',      'sort_order' => 1],
            ['name' => 'سیکل',      'sort_order' => 2],
            ['name' => 'دیپلم',         'sort_order' => 3],
            ['name' => 'فوق دیپلم (کاردانی)',     'sort_order' => 4],
            ['name' => 'کارشناسی (لیسانس)',       'sort_order' => 5],
            ['name' => 'کارشناسی ارشد (فوق لیسانس)',  'sort_order' => 6],
            ['name' => 'دکتری',         'sort_order' => 7],
        ];

        foreach ($levels as $level) {
            EducationLevel::updateOrCreate(
                ['name' => $level['name']],
                ['sort_order' => $level['sort_order']]
            );
        }
    }
}
