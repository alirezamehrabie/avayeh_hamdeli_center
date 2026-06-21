<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceDefinition;
use App\Livewire\Services\ServiceArchive;
use App\Livewire\Services\ServiceDeliveryManager;
use App\Livewire\Services\ServiceManagement;
use App\Livewire\SocialWorkers\Dashboard as SocialWorkerDashboard;
use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceDelivery;
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

    public function test_social_worker_delivery_rechecks_worker_category_quota_when_saving(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 78,
            'first_name' => 'Quota',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $socialWorkerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'social_worker_id' => $worker->id,
        ]);
        $service = $this->serviceWithCategory($manager, 'Home Quota', true);
        $category = $service->categories()->firstOrFail();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 5,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'national_id' => '1111111111',
            'full_name' => 'Existing Recipient',
            'delivered_quantity' => 4,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 4000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $socialWorkerUser->id,
        ]);

        $this->actingAs($socialWorkerUser);

        Livewire::test(SocialWorkerDashboard::class)
            ->set('selectedServiceId', $service->id)
            ->set('recipientEntries', [[
                'national_id' => '2222222222',
                'full_name' => 'New Recipient',
                'mobile' => null,
                'quantity' => 2,
                'service_category_id' => $category->id,
                'is_unregistered' => true,
                'not_found_notice' => '',
                'resolved_name' => '',
                'resolved_meta' => '',
                'covered_dependents_count' => null,
                'family_members_count' => null,
                'person_id' => null,
                'guardian_id' => null,
                'qr_token' => '',
            ]])
            ->set('deliveredAt', Jalalian::fromDateTime(now())->format('Y/m/d'))
            ->call('saveDelivery')
            ->assertHasErrors(['recipientEntries']);

        $this->assertDatabaseMissing('service_deliveries', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'national_id' => '2222222222',
        ]);
    }

    public function test_social_worker_delivery_rejects_duplicate_recipient_in_same_category(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 79,
            'first_name' => 'Duplicate',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $socialWorkerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'social_worker_id' => $worker->id,
        ]);
        $service = $this->serviceWithCategory($manager, 'Duplicate Guard', true);
        $category = $service->categories()->firstOrFail();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 5,
        ]);

        $this->actingAs($socialWorkerUser);

        Livewire::test(SocialWorkerDashboard::class)
            ->set('selectedServiceId', $service->id)
            ->set('recipientEntries', [
                $this->deliveryEntry('3333333333', $category->id, 1, 'Duplicate Recipient'),
                $this->deliveryEntry('3333333333', $category->id, 1, 'Duplicate Recipient'),
            ])
            ->set('deliveredAt', Jalalian::fromDateTime(now())->format('Y/m/d'))
            ->call('saveDelivery')
            ->assertHasErrors(['recipientEntries']);

        $this->assertSame(0, ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('national_id', '3333333333')
            ->count());
    }

    public function test_social_worker_delivery_allows_same_recipient_in_different_categories(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 80,
            'first_name' => 'Multi',
            'last_name' => 'Category',
            'is_active' => true,
        ]);
        $socialWorkerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'social_worker_id' => $worker->id,
        ]);
        $service = $this->serviceWithCategory($manager, 'Multi Category', true);
        $firstCategory = $service->categories()->firstOrFail();
        $secondCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Second Category',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 2000,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);

        foreach ([$firstCategory, $secondCategory] as $category) {
            $service->workerAllocations()->create([
                'social_worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'allocated_quantity' => 5,
            ]);
        }

        $this->actingAs($socialWorkerUser);

        Livewire::test(SocialWorkerDashboard::class)
            ->set('selectedServiceId', $service->id)
            ->set('recipientEntries', [
                $this->deliveryEntry('4444444444', $firstCategory->id, 1, 'Multi Recipient'),
                $this->deliveryEntry('4444444444', $secondCategory->id, 1, 'Multi Recipient'),
            ])
            ->set('deliveredAt', Jalalian::fromDateTime(now())->format('Y/m/d'))
            ->call('saveDelivery')
            ->assertHasNoErrors();

        $this->assertSame(2, ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('social_worker_id', $worker->id)
            ->where('national_id', '4444444444')
            ->count());
    }

    public function test_admin_quota_assignment_records_assigning_user(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 78,
            'first_name' => 'Quota',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $service = $this->serviceWithCategory($manager, 'Assigned By Admin', true);
        $category = $service->categories()->firstOrFail();

        $this->actingAs($manager);

        Livewire::test(ServiceDeliveryManager::class)
            ->set('selectedServiceId', $service->id)
            ->call('addSocialWorker', $worker->id)
            ->set('allocations.' . $worker->id . '.' . $category->id, '4')
            ->call('saveAllocations')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $manager->id,
        ]);
    }

    public function test_admin_cannot_reduce_worker_category_allocation_below_delivered_quantity(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 79,
            'first_name' => 'Delivered',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $service = $this->serviceWithCategory($manager, 'Delivered Allocation Guard', true);
        $category = $service->categories()->firstOrFail();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $manager->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'social_worker_id' => $worker->id,
            'person_id' => null,
            'guardian_id' => null,
            'national_id' => '1234567890',
            'full_name' => 'Delivered Recipient',
            'mobile' => null,
            'delivered_quantity' => 3,
            'service_category_id' => $category->id,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 3000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(ServiceDeliveryManager::class)
            ->set('selectedServiceId', $service->id)
            ->set('allocations.' . $worker->id . '.' . $category->id, '2')
            ->call('saveAllocations')
            ->assertHasErrors(['allocations']);

        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
        ]);
    }

    public function test_admin_cannot_remove_worker_with_existing_deliveries_from_allocations(): void
    {
        $manager = $this->manager();
        $worker = SocialWorker::query()->create([
            'worker_code' => 80,
            'first_name' => 'Removed',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $service = $this->serviceWithCategory($manager, 'Delivered Removal Guard', true);
        $category = $service->categories()->firstOrFail();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $manager->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'social_worker_id' => $worker->id,
            'person_id' => null,
            'guardian_id' => null,
            'national_id' => '1234567891',
            'full_name' => 'Delivered Recipient',
            'mobile' => null,
            'delivered_quantity' => 2,
            'service_category_id' => $category->id,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 2000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(ServiceDeliveryManager::class)
            ->set('selectedServiceId', $service->id)
            ->call('removeSocialWorker', $worker->id)
            ->call('saveAllocations')
            ->assertHasErrors(['allocations']);

        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
        ]);
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

    public function test_archived_service_names_and_categories_are_listed_and_restorable(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Restorable Service Name',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);
        $template = ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Restorable Template',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template->delete();
        $serviceName->delete();

        $this->actingAs($user);

        Livewire::test(ServiceArchive::class)
            ->assertViewHas('archivedServiceNames', fn ($items): bool => $items->contains('id', $serviceName->id))
            ->assertViewHas('archivedServiceCategories', fn ($items): bool => $items->contains('id', $template->id))
            ->call('openRestoreServiceNameConfirmation', $serviceName->id)
            ->assertDispatched('open-notification-modal')
            ->call('openRestoreCategoryConfirmation', $template->id)
            ->assertDispatched('open-notification-modal');

        $this->assertNotNull($serviceName->fresh()?->deleted_at);
        $this->assertNotNull($template->fresh()?->deleted_at);

        Livewire::test(ServiceArchive::class)
            ->call('restoreServiceName', $serviceName->id)
            ->call('restoreCategory', $template->id);

        $this->assertNull($serviceName->fresh()?->deleted_at);
        $this->assertNull($template->fresh()?->deleted_at);
    }

    public function test_archived_category_requires_active_service_name_before_restore(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Archived Parent',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);
        $template = ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Child Template',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template->delete();
        $serviceName->delete();

        $this->actingAs($user);

        Livewire::test(ServiceArchive::class)
            ->call('restoreCategory', $template->id);

        $this->assertNotNull($template->fresh()?->deleted_at);
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

    private function deliveryEntry(string $nationalId, int $categoryId, float $quantity, string $fullName): array
    {
        return [
            'national_id' => $nationalId,
            'full_name' => $fullName,
            'mobile' => null,
            'quantity' => $quantity,
            'service_category_id' => $categoryId,
            'is_unregistered' => true,
            'not_found_notice' => '',
            'resolved_name' => '',
            'resolved_meta' => '',
            'covered_dependents_count' => null,
            'family_members_count' => null,
            'person_id' => null,
            'guardian_id' => null,
            'qr_token' => '',
        ];
    }
}
