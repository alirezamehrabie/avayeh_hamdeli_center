<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResidenceStatusType;

class ResidenceStatusTypeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'شخصی',    'sort_order' => 1],
            ['name' => 'اجاره',   'sort_order' => 2],
            ['name' => 'شراکتی',  'sort_order' => 3],
        ];

        foreach ($data as $item) {
            ResidenceStatusType::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
