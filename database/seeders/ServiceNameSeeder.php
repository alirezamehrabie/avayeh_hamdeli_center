<?php

namespace Database\Seeders;

use App\Models\ServiceName;
use Illuminate\Database\Seeder;

class ServiceNameSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'ارزاق', 'sort_id' => 1],
            ['name' => 'پوشاک', 'sort_id' => 2],
            ['name' => 'بهداشت و درمان', 'sort_id' => 3],
            ['name' => 'تحصیل', 'sort_id' => 4],
            ['name' => 'مشاوره', 'sort_id' => 5],
            ['name' => 'وام', 'sort_id' => 6],
            ['name' => 'وجوهات', 'sort_id' => 7],
            ['name' => 'فطریه', 'sort_id' => 8],
            ['name' => 'مسکن', 'sort_id' => 9],
            ['name' => 'اشتغال و کارآفرینی', 'sort_id' => 10],
            ['name' => 'متفرقه', 'sort_id' => 11],
        ];

        foreach ($items as $item) {
            ServiceName::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'sort_id' => $item['sort_id'],
                    'created_by' => null,
                ]
            );
        }
    }
}
