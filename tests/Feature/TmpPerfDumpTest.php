<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\AdvancedSocialWorkerReport;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TmpPerfDumpTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump(): void
    {
        $user = User::factory()->create(['access_level' => User::ACCESS_LEVEL_ADMIN, 'is_admin' => true, 'permissions' => [User::PERMISSION_FULL_ACCESS]]);
        $worker = SocialWorker::query()->create(['worker_code' => 7001, 'first_name' => 'نمونه', 'last_name' => 'مددکار', 'national_id' => '1234567890', 'mobile' => '09120000000']);

        $sn = ServiceName::query()->create(['name' => 'بستهٔ غذایی', 'sort_id' => 1, 'created_by' => $user->id]);
        $service = Service::query()->create([
            'service_name_id' => $sn->id, 'name' => $sn->name, 'service_type' => 'individual',
            'supports_gate_delivery' => true, 'supports_home_delivery' => true,
            'total_quantity' => 100, 'total_service_value' => 0,
            'distribution_start_date' => '2026-08-01', 'distribution_end_date' => '2026-08-20',
            'status' => 'approved', 'quantity_delivered' => 0, 'created_by' => $user->id,
        ]);
        $catNames = ['برنج', 'روغن', 'حبوبات', 'مرغ'];
        $cats = [];
        foreach ($catNames as $index => $catName) {
            $cats[] = $service->categories()->create([
                'service_name_id' => $sn->id, 'name' => $catName, 'quantity' => 50,
                'unit' => 'pack', 'value' => 1500000, 'sort_id' => $index + 1, 'created_by' => $user->id,
            ]);
        }

        $plan = [
            [10, 3, '2026-08-02', 10],
            [8, 40, '2026-08-05', 5],
            [6, 200, '2026-08-25', 6],
        ];

        foreach ($plan as $i => [$qty, $hours, $date, $deliveredQty]) {
            $assignedAt = Carbon::parse('2026-08-01 09:00:00')->addDays($i);
            $alloc = ServiceWorkerAllocation::query()->create([
                'service_id' => $service->id, 'service_category_id' => $cats[$i]->id,
                'social_worker_id' => $worker->id, 'allocated_quantity' => $qty,
            ]);
            $alloc->forceFill(['created_at' => $assignedAt, 'updated_at' => $assignedAt])->save();

            $d = ServiceDelivery::query()->create([
                'service_id' => $service->id, 'service_category_id' => $cats[$i]->id, 'social_worker_id' => $worker->id,
                'national_id' => '111111111'.$i, 'full_name' => 'گیرنده '.$i,
                'delivery_channel' => $i === 0 ? Service::DELIVERY_CHANNEL_HOME : Service::DELIVERY_CHANNEL_GATE,
                'delivered_quantity' => $deliveredQty, 'value_per_unit_snapshot' => 1500000,
                'delivered_total_value' => (int) ($deliveredQty * 1500000), 'delivered_at' => $date, 'created_by' => $user->id,
            ]);
            $reg = $assignedAt->copy()->addHours($hours);
            $d->forceFill(['created_at' => $reg, 'updated_at' => $reg])->save();
        }

        $idle = ServiceWorkerAllocation::query()->create([
            'service_id' => $service->id, 'service_category_id' => $cats[3]->id,
            'social_worker_id' => $worker->id, 'allocated_quantity' => 5,
        ]);
        $idle->forceFill(['created_at' => Carbon::now()->subDays(30), 'updated_at' => Carbon::now()->subDays(30)])->save();

        $this->actingAs($user);

        $html = Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('showWorkerInfo', $worker->id)
            ->call('setWorkerModalTab', 'performance')
            ->html();

        file_put_contents(base_path('storage/app/perf-dump.html'), $html);
        $this->assertStringContainsString('امتیاز کل عملکرد', $html);
    }
}
