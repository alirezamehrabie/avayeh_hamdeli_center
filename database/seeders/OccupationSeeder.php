<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Occupation;

class OccupationSeeder extends Seeder
{
    public function run()
    {
        $items = [
            'کارمند (دولتی / خصوصی)',
            'کارگر',
            'آزاد (شغل آزاد)',
            'فروشنده',
            'بازنشسته',
            'بیکار',
            'استادکار / فنی‌کار',
            'راننده',
            'خانه‌دار',
            'دانشجو',
            'مغازه‌دار',
            'مدیر / سرپرست',
            'معلم / دبیر',
        ];

        $data = collect($items)
            ->map(fn($name, $i) => [
                'name'       => $name,
                'sort_order' => $i + 1,
            ])->toArray();

        // Upsert: تکراری‌ها آپدیت شوند، در غیر این‌صورت درج شوند
        Occupation::upsert($data, ['name'], ['sort_order']);

        Occupation::updateOrCreate(
            ['name' => 'سایر'],
            ['sort_order' => 999]
        );
    }
}
