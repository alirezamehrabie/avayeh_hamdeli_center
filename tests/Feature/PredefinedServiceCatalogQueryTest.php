<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\Deliveries\PredefinedServiceCatalogQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredefinedServiceCatalogQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_excludes_distribution_operator_created_services_from_the_predefined_catalog(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $sharedName = ServiceName::query()->create([
            'name' => 'Shared service',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $operatorName = ServiceName::query()->create([
            'name' => 'Operator service',
            'sort_id' => 2,
            'created_by' => $operator->id,
        ]);

        $shared = Service::query()->create([
            'service_name_id' => $sharedName->id,
            'name' => 'Shared service',
            'service_type' => 'individual',
            'total_quantity' => 5,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->toDateString(),
            'status' => 'approved',
            'created_by' => $manager->id,
        ]);
        $sharedCategory = $shared->categories()->create([
            'service_name_id' => $sharedName->id,
            'name' => 'Shared category',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $operatorService = Service::query()->create([
            'service_name_id' => $operatorName->id,
            'name' => 'Operator service',
            'service_type' => 'individual',
            'total_quantity' => 5,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->toDateString(),
            'status' => 'approved',
            'created_by' => $operator->id,
        ]);
        $operatorService->categories()->create([
            'service_name_id' => $operatorName->id,
            'name' => 'Operator category',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $services = app(PredefinedServiceCatalogQuery::class)->search('');

        $this->assertTrue($services->contains('id', $shared->id));
        $this->assertFalse($services->contains('id', $operatorService->id));

        $worker = SocialWorker::query()->create([
            'worker_code' => 100,
            'first_name' => 'Selected',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        ServiceWorkerAllocation::query()->create([
            'service_id' => $shared->id,
            'service_category_id' => $sharedCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $operator->id,
        ]);

        $this->assertFalse(
            app(PredefinedServiceCatalogQuery::class)->search('')->contains('id', $shared->id)
        );
        $this->assertTrue(
            app(PredefinedServiceCatalogQuery::class)->search('', $shared->id)->contains('id', $shared->id)
        );
        $this->assertSame(
            $shared->id,
            app(PredefinedServiceCatalogQuery::class)->findSelectableOrFail($shared->id)->id
        );
    }
}
