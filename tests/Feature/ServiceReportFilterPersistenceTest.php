<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Guardian;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportFilterPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_filters_survive_opening_and_returning_from_service_details(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $service1 = $this->createServiceWithCategory($user, 'First Service', [Service::DELIVERY_CHANNEL_HOME]);
        $service2 = $this->createServiceWithCategory($user, 'Second Service', [Service::DELIVERY_CHANNEL_HOME]);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->set('search', 'First')
            ->set('selectedStatus', 'approved')
            ->set('selectedType', 'individual')
            ->set('serviceDateFrom', '1405/01/01')
            ->set('serviceDateTo', '1405/01/30')
            ->call('openService', $service1->id)
            ->assertSet('selectedServiceId', $service1->id)
            ->assertSet('search', 'First')
            ->assertSet('selectedStatus', 'approved')
            ->assertSet('selectedType', 'individual')
            ->assertSet('serviceDateFrom', '1405/01/01')
            ->assertSet('serviceDateTo', '1405/01/30')
            ->call('backToServices')
            ->assertSet('selectedServiceId', null)
            ->assertSet('search', 'First')
            ->assertSet('selectedStatus', 'approved')
            ->assertSet('selectedType', 'individual')
            ->assertSet('serviceDateFrom', '1405/01/01')
            ->assertSet('serviceDateTo', '1405/01/30');
    }

    public function test_service_filters_persist_in_session_and_restore_across_component_mounts(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $this->createServiceWithCategory($user, 'Test Service', [Service::DELIVERY_CHANNEL_HOME]);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->set('search', 'Alpha')
            ->set('selectedStatus', 'completed')
            ->set('selectedType', 'family')
            ->set('serviceDateFrom', '1405/02/01')
            ->set('serviceDateTo', '1405/02/20');

        Livewire::test(ServiceReports::class, [
            'deliveryChannel' => Service::DELIVERY_CHANNEL_HOME,
        ])
            ->assertSet('search', 'Alpha')
            ->assertSet('selectedStatus', 'completed')
            ->assertSet('selectedType', 'family')
            ->assertSet('serviceDateFrom', '1405/02/01')
            ->assertSet('serviceDateTo', '1405/02/20');
    }

    public function test_clear_service_filters_clears_session_and_resets_properties(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $this->createServiceWithCategory($user, 'Test Service', [Service::DELIVERY_CHANNEL_HOME]);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->set('search', 'Beta')
            ->set('selectedStatus', 'in_distribution')
            ->call('clearServiceFilters')
            ->assertSet('search', '')
            ->assertSet('selectedStatus', 'all');

        Livewire::test(ServiceReports::class, [
            'deliveryChannel' => Service::DELIVERY_CHANNEL_HOME,
        ])
            ->assertSet('search', '')
            ->assertSet('selectedStatus', 'all');
    }

    public function test_filters_are_isolated_per_channel(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $this->createServiceWithCategory($user, 'Home Item', [Service::DELIVERY_CHANNEL_HOME]);
        $this->createServiceWithCategory($user, 'Gate Item', [Service::DELIVERY_CHANNEL_GATE]);

        $test = Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->set('search', 'HomeSearch')
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
            ->assertSet('search', '')
            ->set('search', 'GateSearch')
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->assertSet('search', 'HomeSearch')
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
            ->assertSet('search', 'GateSearch');
    }

    public function test_selected_social_worker_in_service_list_is_matched_in_details_view(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $worker1 = SocialWorker::query()->create([
            'worker_code' => 501,
            'first_name' => 'Sara',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $worker2 = SocialWorker::query()->create([
            'worker_code' => 502,
            'first_name' => 'Ali',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $service = $this->createServiceWithCategory($user, 'Delivered Service', [Service::DELIVERY_CHANNEL_HOME]);
        $category = $service->categories()->first();

        $guardian1 = Guardian::query()->create([
            'guardian_code' => 1001,
            'national_code' => '1111111111',
            'first_name' => 'Household',
            'last_name' => 'WorkerOne',
            'social_worker_id' => $worker1->id,
        ]);

        $guardian2 = Guardian::query()->create([
            'guardian_code' => 1002,
            'national_code' => '2222222222',
            'first_name' => 'Household',
            'last_name' => 'WorkerTwo',
            'social_worker_id' => $worker2->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'guardian_id' => $guardian1->id,
            'national_id' => $guardian1->national_code,
            'full_name' => 'Recipient One',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 500,
            'delivered_total_value' => 500,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'guardian_id' => $guardian2->id,
            'national_id' => $guardian2->national_code,
            'full_name' => 'Recipient Two',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 500,
            'delivered_total_value' => 500,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        Livewire::test(ServiceReports::class)
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_HOME)
            ->set('selectedSocialWorker', (string) $worker1->id)
            ->call('openService', $service->id)
            ->assertSet('selectedServiceId', $service->id)
            ->assertSet('selectedSocialWorker', (string) $worker1->id)
            ->assertSet('selectedCoverageSocialWorker', (string) $worker1->id)
            ->assertViewHas('deliveryGroups', fn ($groups): bool => $groups->total() === 1
                && collect($groups->items())->first()->recipientName === 'Recipient One')
            ->call('backToServices')
            ->assertSet('selectedServiceId', null)
            ->assertSet('selectedSocialWorker', (string) $worker1->id);
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
    private function createServiceWithCategory(User $user, string $name, array $channels): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name.' '.Str::random(6),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_home_delivery' => in_array(Service::DELIVERY_CHANNEL_HOME, $channels, true),
            'supports_gate_delivery' => in_array(Service::DELIVERY_CHANNEL_GATE, $channels, true),
            'supports_activity_delivery' => in_array(Service::DELIVERY_CHANNEL_ACTIVITY, $channels, true),
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Category '.Str::random(6),
            'quantity' => 10,
            'unit' => 'piece',
            'value' => 500,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return $service;
    }
}
