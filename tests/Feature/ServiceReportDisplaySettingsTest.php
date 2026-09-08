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

class ServiceReportDisplaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_categorized_mode_renders_category_accordions_and_compact_hides_them(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        // Default mode: every group card exposes per-category accordions
        // (Alpine state token of the accordion panes).
        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSee('categoryOpen', false);

        // Compact mode: recipient section headers only — no accordion markup.
        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->call('setDeliveryDisplayMode', 'compact')
            ->assertSet('deliveryDisplayMode', 'compact')
            ->assertDontSee('categoryOpen', false);
    }

    public function test_display_mode_setter_falls_back_to_the_default_for_unknown_values(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->call('setDeliveryDisplayMode', 'sideways')
            ->assertSet('deliveryDisplayMode', ServiceReports::DISPLAY_MODE_CATEGORIZED);
    }

    /**
     * @return array{0: User, 1: Service}
     */
    private function fixture(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $serviceName = ServiceName::query()->create([
            'name' => 'Display Mode Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 30,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Display Pack '.Str::random(8),
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => (string) random_int(1000000000, 9999999999),
            'full_name' => 'Display Recipient',
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 2000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        return [$user, $service];
    }
}
