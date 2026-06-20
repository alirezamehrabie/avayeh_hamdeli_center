<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceDefinition;
use App\Livewire\Services\ServiceDeliveryManager;
use App\Livewire\Services\ServiceManagement;
use App\Livewire\SocialWorkers\Dashboard as SocialWorkerDashboard;
use App\Models\Service;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_category_is_auto_created_as_template_when_saving_service_definition(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ServiceDefinition::class)
            ->set('serviceName', 'Emergency Aid Package')
            ->set('serviceType', 'individual')
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Protein Bundle',
                'quantity' => '12',
                'unit' => 'pack',
                'value' => '150,000',
            ]])
            ->call('save');

        $serviceName = ServiceName::query()->where('name', 'Emergency Aid Package')->firstOrFail();
        $service = Service::query()->where('service_name_id', $serviceName->id)->firstOrFail();

        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'service_name_id' => $serviceName->id,
            'name' => 'Protein Bundle',
        ]);

        $this->assertDatabaseHas('service_category_templates', [
            'service_name_id' => $serviceName->id,
            'name' => 'Protein Bundle',
            'created_by' => $user->id,
        ]);
    }

    public function test_delivery_channels_are_saved_with_service_definition(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ServiceDefinition::class)
            ->set('serviceName', 'Station Only Package')
            ->set('serviceType', 'individual')
            ->set('supportsGateDelivery', true)
            ->set('supportsHomeDelivery', false)
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Dry Food',
                'quantity' => '8',
                'unit' => 'pack',
                'value' => '100,000',
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $service = Service::query()->where('name', 'Station Only Package')->firstOrFail();

        $this->assertTrue($service->supports_gate_delivery);
        $this->assertFalse($service->supports_home_delivery);
    }

    public function test_at_least_one_delivery_channel_must_be_active(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(ServiceDefinition::class)
            ->set('serviceName', 'Invalid Channel Package')
            ->set('serviceType', 'individual')
            ->set('supportsGateDelivery', false)
            ->set('supportsHomeDelivery', false)
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Dry Food',
                'quantity' => '8',
                'unit' => 'pack',
                'value' => '100,000',
            ]])
            ->call('save')
            ->assertHasErrors(['supportsHomeDelivery']);
    }

    public function test_station_only_services_are_hidden_from_home_delivery_flows(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 77,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $socialWorkerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'social_worker_id' => $worker->id,
        ]);

        $stationOnlyService = $this->serviceWithCategory($manager, 'Station Only', false);
        $homeService = $this->serviceWithCategory($manager, 'Home Enabled', true);

        foreach ([$stationOnlyService, $homeService] as $service) {
            $service->workerAllocations()->create([
                'social_worker_id' => $worker->id,
                'service_category_id' => $service->categories()->firstOrFail()->id,
                'allocated_quantity' => 5,
            ]);
        }

        $this->actingAs($manager);

        Livewire::test(ServiceDeliveryManager::class)
            ->assertViewHas('services', function ($services) use ($stationOnlyService, $homeService): bool {
                return $services->contains('id', $homeService->id)
                    && ! $services->contains('id', $stationOnlyService->id);
            });

        $this->actingAs($socialWorkerUser);

        Livewire::test(SocialWorkerDashboard::class)
            ->assertSee($homeService->code)
            ->assertDontSee($stationOnlyService->code);
    }

    public function test_soft_deleted_template_is_restored_when_reused_in_service_definition(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Food Basket',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template = ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Seasonal Items',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template->delete();

        $this->actingAs($user);

        Livewire::test(ServiceDefinition::class)
            ->set('selectedServiceNameId', $serviceName->id)
            ->set('serviceName', $serviceName->name)
            ->set('serviceType', 'individual')
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Seasonal Items',
                'quantity' => '4',
                'unit' => 'pack',
                'value' => '200,000',
            ]])
            ->call('save');

        $this->assertDatabaseHas('service_category_templates', [
            'id' => $template->id,
            'service_name_id' => $serviceName->id,
            'name' => 'Seasonal Items',
        ]);

        $this->assertNull($template->fresh()?->deleted_at);
    }

    public function test_service_name_archive_hides_name_from_future_catalog_lists_but_preserves_history(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Archived Food Basket',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);
        $fallbackServiceName = ServiceName::query()->create([
            'name' => 'Active Food Basket',
            'sort_id' => 2,
            'created_by' => $user->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ServiceManagement::class)
            ->set('selectedServiceNameId', $serviceName->id)
            ->call('archiveServiceName', $serviceName->id)
            ->assertSet('selectedServiceNameId', $fallbackServiceName->id);

        $this->assertSoftDeleted('service_names', [
            'id' => $serviceName->id,
        ]);
        $this->assertSame($serviceName->name, $service->fresh()->serviceName?->name);

        Livewire::test(ServiceDefinition::class)
            ->assertViewHas('serviceNames', fn ($serviceNames): bool => $serviceNames->contains('id', $fallbackServiceName->id)
                && ! $serviceNames->contains('id', $serviceName->id));
    }

    public function test_archiving_service_name_resets_matching_edit_state(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Editable Service Name',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ServiceManagement::class)
            ->set('editingServiceNameId', $serviceName->id)
            ->set('serviceName', $serviceName->name)
            ->call('archiveServiceName', $serviceName->id)
            ->assertSet('editingServiceNameId', null)
            ->assertSet('serviceName', '');
    }

    public function test_archiving_last_service_name_does_not_show_all_categories(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Last Service Name',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);
        $template = ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Hidden Template',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ServiceManagement::class)
            ->set('selectedServiceNameId', $serviceName->id)
            ->call('archiveServiceName', $serviceName->id)
            ->assertSet('selectedServiceNameId', null)
            ->assertViewHas('serviceCategories', fn ($serviceCategories): bool => $serviceCategories->isEmpty())
            ->assertDontSee($template->name);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function serviceWithCategory(User $creator, string $name, bool $supportsHomeDelivery): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => (int) ServiceName::query()->max('sort_id') + 1,
            'created_by' => $creator->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => $supportsHomeDelivery,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name.' Category',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);

        return $service;
    }
}
