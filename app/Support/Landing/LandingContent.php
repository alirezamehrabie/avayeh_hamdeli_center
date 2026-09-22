<?php

namespace App\Support\Landing;

use App\Models\LandingBanner;
use App\Models\LandingServiceCard;
use Illuminate\Support\Facades\Cache;
use Throwable;

class LandingContent
{
    public const BANNERS_CACHE_KEY = 'landing_public_banners';

    public const SERVICE_CARDS_CACHE_KEY = 'landing_public_service_cards';

    private const CACHE_TTL = 3600;

    /**
     * Active banners for the public slider, ordered.
     *
     * @return array<int, array{image: string, alt: string, link: ?string}>
     */
    public static function banners(): array
    {
        try {
            $banners = Cache::remember(self::BANNERS_CACHE_KEY, self::CACHE_TTL, function (): array {
                return LandingBanner::query()->active()->ordered()->get()
                    ->map(fn (LandingBanner $banner): array => [
                        'image' => (string) $banner->image_url,
                        'alt' => (string) $banner->alt_text,
                        'link' => filled($banner->link_url) ? (string) $banner->link_url : null,
                    ])
                    ->filter(fn (array $slide): bool => $slide['image'] !== '')
                    ->values()
                    ->all();
            });
        } catch (Throwable) {
            // در Production لندینگ نباید به‌خاطر خطای دیتابیس یا کش سفید/خالی بماند.
            $banners = [];
        }

        return $banners !== [] ? $banners : self::fallbackBanners();
    }

    /**
     * Active service cards grouped into the two rails, ordered.
     *
     * @return array<int, array{label: string, items: array<int, array{image: string, title: string}>}>
     */
    public static function serviceRows(): array
    {
        try {
            $rows = Cache::remember(self::SERVICE_CARDS_CACHE_KEY, self::CACHE_TTL, function (): array {
                $cards = LandingServiceCard::query()->active()->ordered()->get();
                $labels = [1 => 'رگال خدمات، ردیف یک', 2 => 'رگال خدمات، ردیف دو'];

                $built = [];
                foreach (LandingServiceCard::RAIL_ROWS as $railRow) {
                    $items = $cards->where('rail_row', $railRow)
                        ->map(fn (LandingServiceCard $card): array => [
                            'image' => (string) $card->image_url,
                            'title' => (string) $card->title,
                        ])
                        ->filter(fn (array $item): bool => $item['image'] !== '')
                        ->values()
                        ->all();

                    if ($items !== []) {
                        $built[] = ['label' => $labels[$railRow], 'items' => $items];
                    }
                }

                return $built;
            });
        } catch (Throwable) {
            $rows = [];
        }

        return $rows !== [] ? $rows : self::fallbackServiceRows();
    }

    /**
     * All active services flattened for the services list view.
     *
     * @return array<int, array{image: string, title: string}>
     */
    public static function services(): array
    {
        return collect(self::serviceRows())
            ->pluck('items')
            ->flatten(1)
            ->values()
            ->all();
    }

    public static function flush(): void
    {
        Cache::forget(self::BANNERS_CACHE_KEY);
        Cache::forget(self::SERVICE_CARDS_CACHE_KEY);
    }

    /**
     * @return array<int, array{image: string, alt: string, link: ?string}>
     */
    private static function fallbackBanners(): array
    {
        return [
            ['image' => asset('images/landing/slide-1.jpg'), 'alt' => 'کودکان تحت پوشش در مسیر آموزش و یادگیری', 'link' => null],
            ['image' => asset('images/landing/slide-2.jpg'), 'alt' => 'تغذیه سالم و بسته‌های غذایی کودکان', 'link' => null],
            ['image' => asset('images/landing/slide-3.jpg'), 'alt' => 'حمایت و پناه از کودکان بی‌سرپرست', 'link' => null],
            ['image' => asset('images/landing/slide-4.jpg'), 'alt' => 'بازی و شادی کودکان در مرکز', 'link' => null],
        ];
    }

    /**
     * @return array<int, array{label: string, items: array<int, array{image: string, title: string}>}>
     */
    private static function fallbackServiceRows(): array
    {
        return [
            [
                'label' => 'رگال خدمات، ردیف یک',
                'items' => [
                    ['image' => asset('images/landing/services/01-hezine-tahsil.png'), 'title' => 'هزینه تحصیل'],
                    ['image' => asset('images/landing/services/02-behdasht-darman.png'), 'title' => 'بهداشت و درمان'],
                    ['image' => asset('images/landing/services/03-nan-mehrabani.png'), 'title' => 'نان مهربانی'],
                    ['image' => asset('images/landing/services/04-sarparasti-ettaam.png'), 'title' => 'سرپرستی ایتام'],
                    ['image' => asset('images/landing/services/05-pooshak.png'), 'title' => 'پوشاک'],
                    ['image' => asset('images/landing/services/06-pack-arzaq.png'), 'title' => 'پک ارزاق'],
                ],
            ],
            [
                'label' => 'رگال خدمات، ردیف دو',
                'items' => [
                    ['image' => asset('images/landing/services/07-shir-khoshk.png'), 'title' => 'شیر خشک'],
                    ['image' => asset('images/landing/services/08-sofreh-om-ol-banin.png'), 'title' => 'سفره ام‌البنین (س)'],
                    ['image' => asset('images/landing/services/09-aqiqe.png'), 'title' => 'عقیقه'],
                    ['image' => asset('images/landing/services/10-kala-daste-dom.png'), 'title' => 'اهدای کالای دست دوم'],
                    ['image' => asset('images/landing/services/11-mashaghel-hamdeli.png'), 'title' => 'مشاغل همدلی'],
                    ['image' => asset('images/landing/services/12-eftekharat-hamdeli.png'), 'title' => 'افتخارات همدلی'],
                ],
            ],
        ];
    }
}
