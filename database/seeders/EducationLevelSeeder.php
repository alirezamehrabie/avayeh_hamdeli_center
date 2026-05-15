<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationLevel;

class EducationLevelSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            ['name' => 'پیش دبستانی', 'sort_order' => 1],
            ['name' => 'پایه اول ابتدایی', 'sort_order' => 2],
            ['name' => 'پایه دوم ابتدایی', 'sort_order' => 3],
            ['name' => 'پایه سوم ابتدایی', 'sort_order' => 4],
            ['name' => 'پایه چهارم ابتدایی', 'sort_order' => 5],
            ['name' => 'پایه پنجم ابتدایی', 'sort_order' => 6],
            ['name' => 'پایه ششم ابتدایی', 'sort_order' => 7],
            ['name' => 'پایه هفتم (متوسطه اول)',      'sort_order' => 8],
            ['name' => 'پایه هشتم (متوسطه اول)',      'sort_order' => 9],
            ['name' => 'پایه نهم (متوسط اول)',      'sort_order' => 10],
            ['name' => 'پایه دهم (متوسط دوم)',      'sort_order' => 11],
            ['name' => 'پایه یازدهم (متوسط دوم)',      'sort_order' => 12],
            ['name' => 'پایه دوازدهم (متوسط دوم)',      'sort_order' => 13],
            ['name' => 'کاردانی (فوق دیپلم)',      'sort_order' => 14],
            ['name' => 'کارشناسی (لیسانس)',         'sort_order' => 15],
            ['name' => 'کارشناسی ارشد (فوق لیسانس)',     'sort_order' => 16],
            ['name' => 'دکتری (حرفه‌ای)',       'sort_order' => 17],
            ['name' => 'تحصیلات حوزوی', 'sort_order' => 18],
        ];

        foreach ($levels as $level) {
            EducationLevel::updateOrCreate(
                ['name' => $level['name']],
                ['sort_order' => $level['sort_order']]
            );
        }
    }
}
