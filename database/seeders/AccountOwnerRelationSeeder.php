<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountOwnerRelation;

class AccountOwnerRelationSeeder extends Seeder
{
    public function run()
    {
        $relations = [
            'شخص مددجو', // ID 1 (معمولاً)
            'پدر',
            'مادر',
            'پدربزرگ',
            'مادربزرگ',
            'سرپرست قانونی',
            'همسر',
            'قیم',
            'برادر',
            'خواهر',
            'سایر'
        ];

        foreach ($relations as $relation) {
            AccountOwnerRelation::firstOrCreate(['name' => $relation]);
        }
    }
}
