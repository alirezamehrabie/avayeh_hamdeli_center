<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\DefineService;
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
