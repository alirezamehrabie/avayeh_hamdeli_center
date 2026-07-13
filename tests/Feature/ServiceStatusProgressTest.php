<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatusProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_allocation_marks_service_as_in_distribution_before_delivery(): void
    {
        [$service] = $this->serviceWithAllocation(5);

        $service->refresh();

        $this->assertSame('in_distribution', $service->status);
        $this->assertSame('0.00', (string) $service->quantity_delivered);
    }

    public function test_distributed_service_without_worker_delivery_remains_in_distribution(): void
    {
        [$service, $category, $worker, $manager] = $this->serviceWithAllocation(5);

        $service->refreshDeliveryProgress();

        $service->refresh();

        $this->assertSame('in_distribution', $service->status);
        $this->assertSame('0.00', (string) $service->quantity_delivered);
    }

    public function test_partially_completed_worker_allocations_remain_in_distribution(): void
    {
        [$service, $category, $worker, $manager] = $this->serviceWithAllocation(5);
        $secondWorker = SocialWorker::query()->create([
            'worker_code' => 902,
            'first_name' => 'Partial',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $secondWorker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $manager->id,
        ]);

        $this->recordDelivery($service, $category, $worker, $manager, 5);

        $service->refresh();

        $this->assertSame('in_distribution', $service->status);
        $this->assertSame('5.00', (string) $service->quantity_delivered);
    }

    public function test_service_is_completed_only_after_all_worker_allocations_are_delivered(): void
    {
        [$service, $category, $worker, $manager] = $this->serviceWithAllocation(5);
        $secondWorker = SocialWorker::query()->create([
            'worker_code' => 903,
            'first_name' => 'Done',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $secondWorker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $manager->id,
        ]);

        $this->recordDelivery($service, $category, $worker, $manager, 5);
        $this->recordDelivery($service, $category, $secondWorker, $manager, 5, '2222222222');

        $service->refresh();

        $this->assertSame('completed', $service->status);
        $this->assertSame('10.00', (string) $service->quantity_delivered);
    }

    /**
     * @return array{0: Service, 1: ServiceCategory, 2: SocialWorker, 3: User}
     */
    private function serviceWithAllocation(float $allocatedQuantity): array
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 901,
            'first_name' => 'Assigned',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Status Progress Service',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'name' => 'Status Progress Service',
            'service_name_id' => $serviceName->id,
            'service_type' => 'individual',
            'total_quantity' => 10,
            'total_service_value' => 10000,
            'distribution_start_date' => now()->toDateString(),
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = ServiceCategory::query()->create([
            'service_name_id' => $serviceName->id,
            'service_id' => $service->id,
            'name' => 'Status Progress Category',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => $allocatedQuantity,
            'assigned_by_user_id' => $manager->id,
        ]);

        return [$service, $category, $worker, $manager];
    }

    private function recordDelivery(
        Service $service,
        ServiceCategory $category,
        SocialWorker $worker,
        User $manager,
        float $quantity,
        string $nationalId = '1111111111',
    ): void {
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'national_id' => $nationalId,
            'full_name' => 'Delivered Recipient',
            'delivered_quantity' => $quantity,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => (int) ($quantity * 1000),
            'delivered_at' => now()->toDateString(),
            'created_by' => $manager->id,
        ]);
    }
}
