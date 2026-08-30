<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\AdvancedSocialWorkerReport;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TmpRankingPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_ranking_preview(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $names = [
            [9301, 'مریم', 'رضایی', 10, 3, '2026-08-02'],
            [9302, 'حسین', 'کریمی', 9, 5, '2026-08-03'],
            [9303, 'زهرا', 'محمدی', 6, 40, '2026-08-06'],
            [9304, 'علی', 'نوری', 3, 200, '2026-08-14'],
            [9305, 'سارا', 'احمدی', 0, null, null],
        ];

        $serviceName = ServiceName::query()->create([
            'name' => 'بستهٔ معیشتی '.Str::random(4),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 1000,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-08-01',
            'distribution_end_date' => '2026-08-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'بستهٔ کامل',
            'quantity' => 1000,
            'unit' => 'بسته',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $assignedAt = Carbon::parse('2026-08-01 09:00:00');

        foreach ($names as [$code, $first, $last, $delivered, $responseHours, $deliveredAt]) {
            $worker = SocialWorker::query()->create([
                'worker_code' => $code,
                'first_name' => $first,
                'last_name' => $last,
            ]);

            if ($responseHours === null) {
                continue;
            }

            $allocation = ServiceWorkerAllocation::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'social_worker_id' => $worker->id,
                'allocated_quantity' => 10,
            ]);
            $allocation->forceFill(['created_at' => $assignedAt, 'updated_at' => $assignedAt])->save();

            if ($delivered <= 0) {
                continue;
            }

            $delivery = ServiceDelivery::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'social_worker_id' => $worker->id,
                'national_id' => (string) random_int(1000000000, 9999999999),
                'full_name' => 'گیرنده '.$code,
                'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
                'delivered_quantity' => $delivered,
                'value_per_unit_snapshot' => 1000,
                'delivered_total_value' => $delivered * 1000,
                'delivered_at' => $deliveredAt,
                'created_by' => $user->id,
            ]);
            $registeredAt = $assignedAt->copy()->addHours($responseHours);
            $delivery->forceFill(['created_at' => $registeredAt, 'updated_at' => $registeredAt])->save();
        }

        $this->actingAs($user);

        $html = Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->html();

        $css = 'public/build/'.collect(json_decode(file_get_contents(public_path('build/manifest.json')), true))
            ->firstWhere('src', 'resources/css/app.css')['file'];

        file_put_contents(base_path('public/ranking-preview.html'), <<<HTML
        <!doctype html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="/{$css}">
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        </head>
        <body class="bg-slate-100">{$html}</body>
        </html>
        HTML);

        $this->assertTrue(true);
    }
}
