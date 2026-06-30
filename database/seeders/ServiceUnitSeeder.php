<?php

namespace Database\Seeders;

use App\Models\ServiceUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ServiceUnit::query()->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $items = [
            ['key' => 'count',     'label' => 'عدد',      'sort_id' => 1],
            ['key' => 'portion',   'label' => 'پرس',      'sort_id' => 2],
            ['key' => 'pack',      'label' => 'بسته',     'sort_id' => 3],
            ['key' => 'dast',      'label' => 'دست',      'sort_id' => 4],
            ['key' => 'kilogram',  'label' => 'کیلوگرم',  'sort_id' => 5],
            ['key' => 'gram',      'label' => 'گرم',      'sort_id' => 6],
            ['key' => 'rial',      'label' => 'ریال',     'sort_id' => 7],
            ['key' => 'pair',      'label' => 'جفت',      'sort_id' => 8],
        ];

        ServiceUnit::query()->insert($items);
    }
}
