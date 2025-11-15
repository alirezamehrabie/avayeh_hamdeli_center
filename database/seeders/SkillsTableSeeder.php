<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillsTableSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'ورزشی',           'slug' => 'varzeshi'],
            ['name' => 'هنری',            'slug' => 'honari'],
            ['name' => 'فرهنگی و مذهبی',  'slug' => 'farhangi-mazhabi'],
            ['name' => 'علمی و تحصیلی',   'slug' => 'elmi-tahsili'],
            ['name' => 'فنی و حرفه‌ای',   'slug' => 'fani-herfei'],
            ['name' => 'رسانه و دیجیتال', 'slug' => 'resaneh-digital'],
        ];

        foreach ($items as $item) {
            Skill::updateOrCreate(
                ['slug' => $item['slug']],
                ['name' => $item['name']]
            );
        }
    }
}
