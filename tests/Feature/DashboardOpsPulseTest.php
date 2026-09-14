<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardHome;
use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardOpsPulseTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_shows_live_operations_pulse_with_attention_states(): void
    {
        $this->actingAs($this->manager());

        $service = $this->inDistributionService();

        // دو دسته: یکی با تحویل کامل تمام می‌شود (هشدار)، دیگری موجود دارد
        // تا خدمت در حالت «در حال توزیع» بماند (منطق canonical خود سرویس).
        $exhausted = ServiceCategory::query()->create([
            'service_id' => $service->id,
            'name' => 'بسته خوراکی',
            'quantity' => 5,
        ]);
        ServiceCategory::query()->create([
            'service_id' => $service->id,
            'name' => 'بسته بهداشتی',
            'quantity' => 50,
        ]);

        // موجودی دسته با تحویل کامل صفر می‌شود: کارت «هشدار موجودی» باید چیپ «تمام‌شده» بگیرد.
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $exhausted->id,
            'national_id' => '1234567890',
            'full_name' => 'مددجو آزمایشی',
            'delivered_quantity' => 5,
            'value_per_unit_snapshot' => 50000,
            'delivered_total_value' => 250000,
            'delivered_at' => today()->toDateString(),
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'created_by' => auth()->id(),
        ]);

        GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $exhausted->id,
            'full_name' => 'مددجو آزمایشی',
            'national_id' => '1234567890',
            'status' => GateEntryAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'created_by' => auth()->id(),
        ]);

        Livewire::test(DashboardHome::class)
            ->assertSee('نبض عملیات مرکز')
            ->assertSee('ورودهای مجازِ تحویل‌نشده')
            ->assertSee('نیازمند پیگیری')
            ->assertSee('هشدار موجودی خدمات')
            ->assertSee('تمام‌شده')
            ->assertSee('حاضران همین حالا');
    }

    public function test_ops_pulse_is_hidden_for_admin_panel_users_without_full_access(): void
    {
        $this->actingAs(User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]));

        Livewire::test(DashboardHome::class)
            ->assertSee('خلاصه وضعیت مرکز نیکوکاری')
            ->assertDontSee('نبض عملیات مرکز');
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function inDistributionService(): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'بسته حمایتی آزمایشی',
            'sort_id' => ((int) ServiceName::query()->max('sort_id') ?? 0) + 1,
        ]);

        return Service::query()->create([
            'code' => 'SVC-'.random_int(10000, 99999),
            'service_name_id' => $serviceName->id,
            'name' => 'بسته حمایتی آزمایشی',
            'service_type' => 'individual',
            'status' => 'in_distribution',
            'total_quantity' => 100,
            'supports_gate_delivery' => true,
            'distribution_start_date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);
    }
}
