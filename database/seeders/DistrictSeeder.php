<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\District;

class DistrictSeeder extends Seeder
{
    public function run()
    {
        $districts = [
            'ورنوسفادران',
            'فروشان',
            'خوزان',
            'اسفریز',
            'ارغوان',
            'جویی آباد قدیم',
            'جوی آباد جدید',
            'جعفرآباد',
            'قرطمان',
            'جوادیه',
            'ادریان',
            'هرستان',
            'منظریه',
            'هفتصد دستگاه',
            'کوشک',
            'گلدشت',
            'اصغرآباد',
            'اندوان',
            'دستگرد قداده',
            'قائمیه'
        ];

        foreach ($districts as $index => $name) {
            District::updateOrCreate(
                ['name' => $name],
                ['sort_order' => $index + 1]
            );
        }
    }
}
