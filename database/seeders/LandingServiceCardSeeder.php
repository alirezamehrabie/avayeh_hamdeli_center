<?php

namespace Database\Seeders;

use App\Models\LandingServiceCard;
use Illuminate\Database\Seeder;

class LandingServiceCardSeeder extends Seeder
{
    /**
     * Idempotent by design: existing rows are never truncated or overwritten,
     * so this is safe to run against production data.
     */
    public function run(): void
    {
        $items = [
            ['rail_row' => 1, 'sort_id' => 1, 'image_path' => 'images/landing/services/01-hezine-tahsil.png', 'title' => 'هزینه تحصیل'],
            ['rail_row' => 1, 'sort_id' => 2, 'image_path' => 'images/landing/services/02-behdasht-darman.png', 'title' => 'بهداشت و درمان'],
            ['rail_row' => 1, 'sort_id' => 3, 'image_path' => 'images/landing/services/03-nan-mehrabani.png', 'title' => 'نان مهربانی'],
            ['rail_row' => 1, 'sort_id' => 4, 'image_path' => 'images/landing/services/04-sarparasti-ettaam.png', 'title' => 'سرپرستی ایتام'],
            ['rail_row' => 1, 'sort_id' => 5, 'image_path' => 'images/landing/services/05-pooshak.png', 'title' => 'پوشاک'],
            ['rail_row' => 1, 'sort_id' => 6, 'image_path' => 'images/landing/services/06-pack-arzaq.png', 'title' => 'پک ارزاق'],
            ['rail_row' => 2, 'sort_id' => 1, 'image_path' => 'images/landing/services/07-shir-khoshk.png', 'title' => 'شیر خشک'],
            ['rail_row' => 2, 'sort_id' => 2, 'image_path' => 'images/landing/services/08-sofreh-om-ol-banin.png', 'title' => 'سفره ام‌البنین (س)'],
            ['rail_row' => 2, 'sort_id' => 3, 'image_path' => 'images/landing/services/09-aqiqe.png', 'title' => 'عقیقه'],
            ['rail_row' => 2, 'sort_id' => 4, 'image_path' => 'images/landing/services/10-kala-daste-dom.png', 'title' => 'اهدای کالای دست دوم'],
            ['rail_row' => 2, 'sort_id' => 5, 'image_path' => 'images/landing/services/11-mashaghel-hamdeli.png', 'title' => 'مشاغل همدلی'],
            ['rail_row' => 2, 'sort_id' => 6, 'image_path' => 'images/landing/services/12-eftekharat-hamdeli.png', 'title' => 'افتخارات همدلی'],
        ];

        foreach ($items as $item) {
            LandingServiceCard::query()->firstOrCreate(
                ['image_path' => $item['image_path']],
                $item + ['active_status' => true],
            );
        }
    }
}
