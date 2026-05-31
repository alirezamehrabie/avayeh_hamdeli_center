<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            1 => 'تعریف نشده',
            2 => 'معیشتی',
            3 => 'تحصیلی',
            4 => 'پزشکی / درمانی',
            5 => 'مسکن',
            6 => 'پوشاک',
        ];

        foreach ($categories as $id => $name) {
            ServiceCategory::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $name,
                    'created_by' => null,
                ]
            );
        }
    }
}
