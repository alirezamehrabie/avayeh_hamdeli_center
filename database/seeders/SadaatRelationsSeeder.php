<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SadaatRelation;

class SadaatRelationsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'سادات موسوی',
            'سادات حسنی',
            'سادات حسینی',
            'سادات هاشمی',
            'سادات طباطبایی',
            'سادات رضوی',
        ];

        $data = collect($items)
            ->map(fn($name, $i) => [
                'name'       => $name,
                'sort_order' => $i + 1,
            ])
            ->toArray();

        SadaatRelation::upsert($data, ['name'], ['sort_order']);
    }
}
