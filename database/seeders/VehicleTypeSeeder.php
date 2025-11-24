<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;

class VehicleTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'دوچرخه',
            'موتورسیکلت',
            'خودروی سواری',
            'خودروی باری',
            'خودروی عمومی (مسافربری)',
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(['name' => $type]);
        }
    }
}
