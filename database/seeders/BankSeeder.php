<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            'ملی ایران',
            'ملت',
            'صادرات ایران',
            'سپه',
            'تجارت',
            'رفاه کارگران',
            'کشاورزی',
            'مسکن',
            'پارسیان',
            'پاسارگاد',
            'اقتصاد نوین',
            'سامان',
            'سینا',
            'شهر',
            'دی',
            'گردشگری',
            'ایران‌زمین',
            'سرمایه',
            'خاورمیانه',
            'آینده',
            'کارآفرین',
            'توسعه تعاون',
            'توسعه صادرات ایران',
            'قرض‌الحسنه رسالت',
            'قرض‌الحسنه مهر ایران',
            'موسسه اعتباری نور',
            'موسسه ملل (عسکریه سابق)',
            'موسسه اعتباری کوثر',
            'بانک امداد ولایت',
        ];

        foreach ($banks as $bank) {
            Bank::firstOrCreate(['name' => $bank]);
        }
    }
}
