<?php

namespace Database\Seeders;

use App\Models\ServiceName;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $scopedCategories = [
            'ارزاق' => [
                1 => 'غذای گرم',
                2 => 'میوه جات',
                3 => 'بسته گوشت',
                4 => 'بسته گوشت مرغ',
                5 => 'تنقلات',
                6 => 'آجیل',
                7 => 'شیر خشک',
                8 => 'نان',
                9 => 'خرما',
                10 => 'تخم مرغ',
                11 => 'کله پاچه',
                12 => 'کیک تولد',
                13 => 'شیرینی / زلوبیا بامیه',
                14 => 'پک ارزاق',
                15 => 'سایر (متفرقه)',
            ],
            'پوشاک' => [
                1 => 'لباس',
                2 => 'کفش',
                3 => 'جوراب',
                4 => 'پتو',
                5 => 'حوله',
                6 => 'رخت خواب',
                7 => 'پوشک (مای‌بیبی)',
                8 => 'لباس زیر',
                9 => 'سایر (متفرقه)',
            ],
            'بهداشت و درمان' => [
                1 => 'هزینه پزشک',
                2 => 'هزینه درمان',
                3 => 'هزینه دارو',
                4 => 'پک بهداشتی خواهران',
                5 => 'سایر (متفرقه)',
            ],
            'تحصیل' => [
                1 => 'هزینه ثبت نام (مدرسه / دانشگاه)',
                2 => 'هزینه ثبت نام کتاب',
                3 => 'لباس فرم مدرسه',
                4 => 'هزینه سرویس مدارس',
                5 => 'لوازم و تحریر (نوشت افزار)',
                6 => 'سایر (متفرقه)',
            ],
            'مشاوره' => [
                1 => 'سایر (متفرقه)',
            ],
            'وام' => [
                1 => 'سایر (متفرقه)',
            ],
            'وجوهات' => [
                1 => 'سایر (متفرقه)',
            ],
            'فطریه' => [
                1 => 'سایر (متفرقه)',
            ],
            'مسکن' => [
                1 => 'سایر (متفرقه)',
            ],
            'اشتغال و کارآفرینی' => [
                1 => 'سایر (متفرقه)',
            ],
            'متفرقه' => [
                1 => 'سایر (متفرقه)',
            ],
        ];

        ServiceCategory::query()->whereNull('service_name_id')->delete();

        foreach ($scopedCategories as $serviceName => $categories) {
            $serviceNameId = ServiceName::query()->where('name', $serviceName)->value('id');

            if (! $serviceNameId) {
                continue;
            }

            foreach ($categories as $sortId => $name) {
                ServiceCategory::query()->updateOrCreate(
                    [
                        'service_name_id' => $serviceNameId,
                        'name' => $name,
                    ],
                    [
                        'sort_id' => $sortId,
                        'created_by' => null,
                    ]
                );
            }
        }
    }
}
