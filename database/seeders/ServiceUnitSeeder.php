<?php

namespace Database\Seeders;

use App\Models\ServiceUnit;
use Illuminate\Database\Seeder;

class ServiceUnitSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['key' => 'package', 'label' => 'بسته', 'sort_id' => 1],
            ['key' => 'piece', 'label' => 'دست', 'sort_id' => 2],
            ['key' => 'count', 'label' => 'عدد', 'sort_id' => 3],
            ['key' => 'portion', 'label' => 'پرس', 'sort_id' => 4],
            ['key' => 'kilogram', 'label' => 'کیلوگرم', 'sort_id' => 5],
            ['key' => 'gram', 'label' => 'گرم', 'sort_id' => 6],
        ];

        foreach ($items as $item) {
            ServiceUnit::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'label' => $item['label'],
                    'sort_id' => $item['sort_id'],
                    'created_by' => null,
                ]
            );
        }
    }
}
