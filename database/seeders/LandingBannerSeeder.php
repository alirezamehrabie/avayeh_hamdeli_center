<?php

namespace Database\Seeders;

use App\Models\LandingBanner;
use Illuminate\Database\Seeder;

class LandingBannerSeeder extends Seeder
{
    /**
     * Idempotent by design: existing rows are never truncated or overwritten,
     * so this is safe to run against production data.
     */
    public function run(): void
    {
        $items = [
            ['sort_id' => 1, 'image_path' => 'images/landing/slide-1.jpg', 'alt_text' => 'کودکان تحت پوشش در مسیر آموزش و یادگیری'],
            ['sort_id' => 2, 'image_path' => 'images/landing/slide-2.jpg', 'alt_text' => 'تغذیه سالم و بسته‌های غذایی کودکان'],
            ['sort_id' => 3, 'image_path' => 'images/landing/slide-3.jpg', 'alt_text' => 'حمایت و پناه از کودکان بی‌سرپرست'],
            ['sort_id' => 4, 'image_path' => 'images/landing/slide-4.jpg', 'alt_text' => 'بازی و شادی کودکان در مرکز'],
        ];

        foreach ($items as $item) {
            LandingBanner::query()->firstOrCreate(
                ['image_path' => $item['image_path']],
                $item + ['active_status' => true],
            );
        }
    }
}
