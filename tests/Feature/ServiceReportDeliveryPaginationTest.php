<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportDeliveryPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_groups_paginate_20_per_page(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $this->actingAs($user);

        $service = $this->serviceWithGroups(25, $user);

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id]);

        $page1 = $component->instance()->deliveryGroups;
        $this->assertCount(20, $page1->items());
        $this->assertSame(25, $page1->total());

        $component->call('gotoPage', 2, 'deliveries');
        $this->assertCount(5, $component->instance()->deliveryGroups->items());

        // The modal breakdown aggregates all filtered deliveries, not just the current page.
        $this->assertSame(25, (int) $component->instance()->deliveredCategoryBreakdown->sum('recordCount'));
    }

    public function test_delivery_list_orders_from_oldest_registration_to_newest(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $this->actingAs($user);

        $service = $this->serviceWithGroups(0, $user);
        $category = $service->categories()->first();

        // Four recipient groups; group order follows the earliest created_at,
        // and the pair inside group A stays oldest-first too.
        $this->record($service, $category, $user, 'D oldest', '9000000001', 5);
        $this->record($service, $category, $user, 'B', '9000000003', 4);
        $this->record($service, $category, $user, 'A old', '9000000002', 3);
        $this->record($service, $category, $user, 'C newest', '9000000004', 2);
        $this->record($service, $category, $user, 'A new', '9000000002', 1);

        $items = collect(Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->instance()->deliveryGroups->items());

        $this->assertSame(
            ['D oldest', 'B', 'A old', 'C newest'],
            $items->pluck('recipientName')->all()
        );

        // Group A sits third: its two deliveries must read oldest → newest.
        $this->assertSame(
            ['A old', 'A new'],
            collect($items[2]->deliveries)->pluck('full_name')->all()
        );
    }

    private function record(Service $service, ServiceCategory $category, User $user, string $name, string $nationalId, int $hoursAgo): ServiceDelivery
    {
        $delivery = ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'national_id' => $nationalId,
            'full_name' => $name,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        // created_at is guarded on the model: pin it explicitly so the
        // second-precision timestamps never collide.
        $delivery->forceFill(['created_at' => now()->subHours($hoursAgo)])->save();

        return $delivery->fresh();
    }

    /**
     * One recipient group per delivery (distinct manual national ids).
     */
    private function serviceWithGroups(int $count, User $user): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Paged Service '.Str::random(8),
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
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Pack '.Str::random(8),
            'quantity' => 1000,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        for ($i = 0; $i < $count; $i++) {
            ServiceDelivery::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'national_id' => '1'.str_pad((string) $i, 9, '0', STR_PAD_LEFT),
                'full_name' => 'Recipient '.$i,
                'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
                'delivered_quantity' => 1,
                'value_per_unit_snapshot' => 1000,
                'delivered_total_value' => 1000,
                'delivered_at' => now()->subMinutes($i + 1),
                'created_by' => $user->id,
            ]);
        }

        return $service->fresh();
    }
}
