<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\DefineService;
use App\Livewire\DistributionOperators\EditMiscService;
use App\Livewire\DistributionOperators\ServiceBatchCreator;
use App\Livewire\DistributionOperators\ServiceList;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionOperatorServiceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_define_service_shows_latest_misc_service_quick_access(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $otherOperator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 939,
            'first_name' => 'Quick',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $oldService = $this->makeService($operator, 'Older misc package');
        $oldCategory = $this->makeCategory($oldService, 'Older category', $operator);
        $oldService->socialWorkers()->attach($worker->id, [
            'service_category_id' => $oldCategory->id,
            'allocated_quantity' => 1,
            'assigned_by_user_id' => $operator->id,
        ]);
        $oldService->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        $latestService = $this->makeService($operator, 'Latest misc package');
        $latestCategory = $this->makeCategory($latestService, 'Latest category', $operator);
        $latestService->socialWorkers()->attach($worker->id, [
            'service_category_id' => $latestCategory->id,
            'allocated_quantity' => 3,
            'assigned_by_user_id' => $operator->id,
        ]);

        $otherService = $this->makeService($otherOperator, 'Other operator package');
        $this->makeCategory($otherService, 'Other category', $otherOperator);

        $this->actingAs($operator);

        Livewire::test(DefineService::class)
            ->assertSee('Latest misc package')
            ->assertSee('pressHoldPreview()', false)
            ->assertSee('x-on:pointerdown="beginPreview()"', false)
            ->assertSee('Latest category')
            ->assertSee(route('distribution-operator.edit-service', $latestService->id), false)
            ->assertDontSee('Older misc package')
            ->assertDontSee('Other operator package');
    }

    public function test_operator_service_list_separates_campaign_and_misc_services(): void
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
            'worker_code' => 940,
            'first_name' => 'List',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $campaignService = $this->makeService($manager, 'Campaign package');
        $campaignCategory = $this->makeCategory($campaignService, 'Campaign category', $manager);
        $campaignService->workerAllocations()->create([
            'service_category_id' => $campaignCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 3,
            'assigned_by_user_id' => $operator->id,
        ]);

        $miscService = $this->makeService($operator, 'Misc package');
        $miscCategory = $this->makeCategory($miscService, 'Misc category', $operator);
        $miscService->socialWorkers()->attach($worker->id, [
            'service_category_id' => $miscCategory->id,
            'allocated_quantity' => 2,
            'assigned_by_user_id' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceList::class)
            ->assertSet('activeTab', ServiceList::TAB_CAMPAIGNS)
            ->assertSee('لیست کمپین‌ها')
            ->assertSee('Campaign package')
            ->assertSee(route('distribution-operator.edit-service', $miscService->id), false)
            ->call('switchTab', ServiceList::TAB_MISC)
            ->assertSet('activeTab', ServiceList::TAB_MISC)
            ->assertSee('لیست متفرقه')
            ->assertSee('Misc package')
            ->assertDontSee('Campaign package');

        Livewire::withQueryParams(['tab' => ServiceList::TAB_MISC])
            ->test(ServiceList::class)
            ->assertSet('activeTab', ServiceList::TAB_MISC)
            ->assertSee('Misc package')
            ->assertDontSee('Campaign package');
    }

    public function test_misc_service_is_visible_and_editable_by_other_operators(): void
    {
        $creator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $otherOperator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 942,
            'first_name' => 'Shared',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $miscService = $this->makeService($creator, 'Shared misc package');
        $miscCategory = $this->makeCategory($miscService, 'Shared misc category', $creator);
        $miscService->socialWorkers()->attach($worker->id, [
            'service_category_id' => $miscCategory->id,
            'allocated_quantity' => 2,
            'assigned_by_user_id' => $creator->id,
        ]);

        // A different operator sees the misc service in the misc tab...
        $this->actingAs($otherOperator);

        Livewire::withQueryParams(['tab' => ServiceList::TAB_MISC])
            ->test(ServiceList::class)
            ->assertSet('activeTab', ServiceList::TAB_MISC)
            ->assertSee('Shared misc package')
            ->assertSee(route('distribution-operator.edit-service', $miscService->id), false);

        // ...and can open both edit entry points without a 403.
        Livewire::test(EditMiscService::class, ['serviceId' => $miscService->id])
            ->assertSet('serviceId', $miscService->id);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $miscService->id])
            ->assertSet('editingServiceId', $miscService->id);
    }

    public function test_misc_service_is_not_shared_with_non_operators_predefined_services(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        // Predefined (admin-created) service must never be editable via the
        // operator misc-edit entry point.
        $predefinedService = $this->makeService($manager, 'Predefined package');

        $this->actingAs($operator);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $predefinedService->id]);
    }

    public function test_operator_can_search_service_catalog(): void
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
            'worker_code' => 941,
            'first_name' => 'Catalog',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $riceService = $this->makeService($manager, 'Rice support');
        $riceCategory = $this->makeCategory($riceService, 'Food basket', $manager);
        $riceService->workerAllocations()->create([
            'service_category_id' => $riceCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 3,
            'assigned_by_user_id' => $operator->id,
        ]);

        $blanketService = $this->makeService($manager, 'Winter blankets');
        $blanketCategory = $this->makeCategory($blanketService, 'Warm clothes', $manager);
        $blanketService->workerAllocations()->create([
            'service_category_id' => $blanketCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceList::class)
            ->assertSee('Rice support')
            ->assertSee('Winter blankets')
            ->set('search', 'Food basket')
            ->assertSee('Rice support')
            ->assertDontSee('Winter blankets');
    }

    public function test_misc_service_creation_rejects_duplicate_name_in_name_step(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 945,
            'first_name' => 'Duplicate',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $this->makeService($operator, 'بسته زمستانه');

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->call('requestMiscServiceName')
            ->set('miscServiceName', 'بسته زمستانه')
            ->call('confirmMiscServiceName')
            ->assertHasErrors(['miscServiceName'])
            ->assertSet('mode', '');
    }

    public function test_misc_service_creation_rejects_duplicate_name_in_save_step(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 946,
            'first_name' => 'DuplicateSave',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $this->makeService($operator, 'بسته پنیر');

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceName', 'بسته پنیر')
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'پنیر')
            ->set('miscCategories.0.quantity', '2')
            ->set('miscCategories.0.unit', 'pack')
            ->set('date', '1405/03/30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasErrors(['miscServiceName']);

        $this->assertDatabaseCount('services', 1);
    }

    protected function makeService(User $creator, string $name): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'description' => $name,
            'total_quantity' => 10,
            'total_service_value' => 100,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->addDay()->toDateString(),
            'priority' => 'normal',
            'status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }

    protected function makeCategory(Service $service, string $name, User $creator): ServiceCategory
    {
        return ServiceCategory::query()->create([
            'service_id' => $service->id,
            'service_name_id' => $service->service_name_id,
            'name' => $name,
            'quantity' => $service->total_quantity,
            'unit' => 'pack',
            'value' => 10,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);
    }
}
