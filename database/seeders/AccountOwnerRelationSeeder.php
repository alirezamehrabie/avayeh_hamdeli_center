<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountOwnerRelation; // مطمئن شوید که مدل صحیح است و به آن اشاره می کند

class AccountOwnerRelationSeeder extends Seeder
{
    public function run(): void
    {
        $relations = [
            // ID: 1 - برای زمانی که has_own_account = 1 (بله)
            ['id' => 1, 'name' => 'شخص مددجو'],
            // ID: 2 - برای زمانی که has_own_account = 0 (خیر)
            ['id' => 2, 'name' => 'سرپرست قانونی'],

        ];

        foreach ($relations as $relationData) {
            AccountOwnerRelation::updateOrCreate(
                ['id' => $relationData['id']], // سعی می کنیم بر اساس ID پیدا کنیم
                ['name' => $relationData['name']] // ستون 'name' را به روزرسانی یا درج می کنیم
            );
        }
    }
}
