<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NeedLevelType;

class NeedLevelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'A', 'title' => 'بحرانی',    'severity_order' => 1],
            ['code' => 'B', 'title' => 'زیاد',      'severity_order' => 2],
            ['code' => 'C', 'title' => 'متوسط',     'severity_order' => 3],
            ['code' => 'D', 'title' => 'پایین',     'severity_order' => 4],
            ['code' => 'E', 'title' => 'خیلی پایین','severity_order' => 5],
        ];

        foreach ($levels as $level) {
            NeedLevelType::updateOrCreate(['code' => $level['code']], $level);
        }
    }
}
