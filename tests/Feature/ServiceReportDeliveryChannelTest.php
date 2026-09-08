<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportDeliveryChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_shows_the_three_delivery_methods_before_any_list(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->assertSee('خدمات تحویل در منزل')
            ->assertSee('خدمات ایستگاه توزیع')
            ->assertSee('خدمات فعالیتی')
            ->assertDontSee('جستجوی سراسری')
            ->assertViewHas('services', fn ($services): bool => $services === null);
    }

    public function test_each_channel_lists_only_the_services_supporting_it(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $home = $this->createService($user, [Service::DELIVERY_CHANNEL_HOME]);
        $gate = $this->createService($user, [Service::DELIVERY_CHANNEL_GATE]);
        $activity = $this->createService($user, [Service::DELIVERY_CHANNEL_ACTIVITY]);
        $all = $this->createService($user, [
            Service::DELIVERY_CHANNEL_HOME,
            Service::DELIVERY_CHANNEL_GATE,
            Service::DELIVERY_CHANNEL_ACTIVITY,
        ]);

        $ids = fn (LengthAwarePaginator $services): array => $services->pluck('id')->sort()->values()->all();

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $ids($services) === [$home->id, $all->id])
            ->assertSee('جستجوی سراسری')
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $ids($services) === [$gate->id, $all->id])
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $ids($services) === [$activity->id, $all->id]);
    }

    public function test_unknown_channel_is_ignored_and_keeps_the_landing(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', 'carrier-pigeon')
            ->assertSet('deliveryChannel', null)
            ->assertViewHas('services', fn ($services): bool => $services === null);
    }

    public function test_back_returns_to_the_channel_selection_landing(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $this->createService($user, [Service::DELIVERY_CHANNEL_HOME]);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->assertSee('جستجوی سراسری')
            ->call('backToChannelSelection')
            ->assertSet('deliveryChannel', null)
            ->assertSet('selectedServiceId', null)
            ->assertDontSee('جستجوی سراسری')
            ->assertSee('خدمات تحویل در منزل')
            ->assertViewHas('services', fn ($services): bool => $services === null);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @param  array<int, string>  $channels
     */
    private function createService(User $user, array $channels): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Channeled Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => in_array(Service::DELIVERY_CHANNEL_GATE, $channels, true),
            'supports_home_delivery' => in_array(Service::DELIVERY_CHANNEL_HOME, $channels, true),
            'supports_activity_delivery' => in_array(Service::DELIVERY_CHANNEL_ACTIVITY, $channels, true),
            'total_quantity' => 30,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);
    }
}
