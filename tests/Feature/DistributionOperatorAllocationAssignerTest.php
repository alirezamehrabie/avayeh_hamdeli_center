<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\ServiceBatchCreator;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionOperatorAllocationAssignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_assignment_records_assigning_user(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 901,
            'first_name' => 'Distribution',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'متفرقه',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'سایر (متفرقه)',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'بسته اپراتور')
            ->set('miscCategories.0.quantity', '6')
            ->set('miscCategories.0.unit', 'pack')
            ->set('dateYear', '1405')
            ->set('dateMonth', '3')
            ->set('dateDay', '30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 6,
            'assigned_by_user_id' => $operator->id,
        ]);
    }

    public function test_predefined_mode_allocates_existing_service_without_creating_service_or_category(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 902,
            'first_name' => 'مددکار',
            'last_name' => 'پویش',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش مدیریتی',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش مدیریتی',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'برنج',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $serviceCount = Service::query()->count();
        $categoryCount = ServiceCategory::query()->count();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->set('predefinedAllocations.' . $category->id, '4')
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertSame($serviceCount, Service::query()->count());
        $this->assertSame($categoryCount, ServiceCategory::query()->count());
        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $operator->id,
        ]);
        $this->assertSame(10.0, (float) $category->fresh()->quantity);
        $this->assertSame(10.0, (float) $service->fresh()->total_quantity);
    }

    public function test_predefined_mode_limits_repeated_allocations_by_remaining_assignable_capacity(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $firstWorker = SocialWorker::query()->create([
            'worker_code' => 912,
            'first_name' => 'First',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $secondWorker = SocialWorker::query()->create([
            'worker_code' => 913,
            'first_name' => 'Second',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Repeated Allocation Campaign',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Repeated Allocation Campaign',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Rice',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $firstWorker->full_name)
            ->set('socialWorkerId', $firstWorker->id)
            ->set('predefinedAllocations.' . $category->id, '4')
            ->call('saveBatch')
            ->assertHasNoErrors();

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $secondWorker->full_name)
            ->set('socialWorkerId', $secondWorker->id)
            ->set('predefinedAllocations.' . $category->id, '7')
            ->call('saveBatch')
            ->assertHasErrors(['predefinedAllocations.' . $category->id]);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $secondWorker->full_name)
            ->set('socialWorkerId', $secondWorker->id)
            ->set('predefinedAllocations.' . $category->id, '6')
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertSame(10.0, (float) $category->fresh()->quantity);
        $this->assertSame(10.0, (float) $service->fresh()->total_quantity);
        $this->assertSame(10.0, (float) $service->workerAllocations()->sum('allocated_quantity'));
    }

    public function test_predefined_mode_exposes_minimal_live_remaining_inventory_for_selected_categories(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش آمار',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش آمار',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 15,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $rice = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'برنج',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $oil = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'روغن',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('predefinedAllocations.' . $rice->id, '3')
            ->set('predefinedAllocations.' . $oil->id, '2')
            ->assertSee('مانده پس از تخصیص')
            ->assertSee('7.00')
            ->assertSee('3.00')
            ->assertDontSee('واردشده');
    }

    public function test_misc_mode_auto_names_created_service(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 903,
            'first_name' => 'مددکار',
            'last_name' => 'متفرقه',
            'is_active' => true,
        ]);
        ServiceName::query()->create([
            'name' => 'متفرقه - 19',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'پتو')
            ->set('miscCategories.0.quantity', '3')
            ->set('miscCategories.0.unit', 'pack')
            ->set('dateYear', '1405')
            ->set('dateMonth', '3')
            ->set('dateDay', '30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'متفرقه - 20',
            'created_by' => $operator->id,
            'total_quantity' => 3,
        ]);
        $this->assertDatabaseHas('service_names', [
            'name' => 'متفرقه - 20',
        ]);
    }

    public function test_misc_mode_continues_numbering_from_legacy_english_misc_names_but_stores_persian_name(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 904,
            'first_name' => 'مددکار',
            'last_name' => 'میراثی',
            'is_active' => true,
        ]);
        ServiceName::query()->create([
            'name' => 'Misc - 21',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'کفش')
            ->set('miscCategories.0.quantity', '2')
            ->set('miscCategories.0.unit', 'pack')
            ->set('dateYear', '1405')
            ->set('dateMonth', '3')
            ->set('dateDay', '30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'متفرقه - 22',
            'created_by' => $operator->id,
            'status_notes' => 'خدمت متفرقه ایجادشده توسط اپراتور توزیع.',
        ]);
        $this->assertDatabaseMissing('services', [
            'name' => 'Misc - 22',
        ]);
    }
}
