<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\Deliveries\MiscServiceEditQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiscServiceEditQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_the_existing_worker_group_state(): void
    {
        [$operator, $service, $category, $worker] = $this->editableService();

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $operator->id,
        ]);

        $groups = app(MiscServiceEditQuery::class)->workerGroups($service);

        $this->assertCount(1, $groups);
        $this->assertSame($worker->id, $groups[0]['social_worker_id']);
        $this->assertTrue($groups[0]['locked']);
        $this->assertTrue($groups[0]['is_existing']);
        $this->assertSame($category->id, $groups[0]['categories'][0]['id']);
        $this->assertSame('5', $groups[0]['categories'][0]['quantity']);
    }

    public function test_category_pool_prefers_the_used_soft_deleted_match(): void
    {
        [$operator, $service, $usedCategory, $worker] = $this->editableService();
        $usedCategory->update(['name' => 'Shared Pack']);
        $service->workerAllocations()->create([
            'service_category_id' => $usedCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 2,
            'assigned_by_user_id' => $operator->id,
        ]);
        $usedCategory->delete();
        $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Shared Pack',
            'quantity' => 3,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $operator->id,
        ]);

        $query = app(MiscServiceEditQuery::class);
        $pool = $query->categoryPool($service);

        $this->assertSame(
            $usedCategory->id,
            $query->resolveCategoryId(['id' => null, 'name' => 'Shared Pack'], $pool),
        );
    }

    /**
     * @return array{0: User, 1: Service, 2: ServiceCategory, 3: SocialWorker}
     */
    private function editableService(): array
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_SERVICE_MANAGE],
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Query Editable Service',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $service = Service::query()->create([
            'name' => 'Query Editable Service',
            'service_name_id' => $serviceName->id,
            'service_type' => 'individual',
            'total_quantity' => 5,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->toDateString(),
            'status' => 'in_distribution',
            'created_by' => $operator->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Original Pack',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 730,
            'first_name' => 'Query',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        return [$operator, $service, $category, $worker];
    }
}
