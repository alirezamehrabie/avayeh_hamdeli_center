<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\Deliveries\PredefinedServiceAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit-style coverage for the allocation business rules extracted out of the
 * ServiceBatchCreator Livewire component. The component-level behavior stays
 * covered by DistributionOperatorAllocationAssignerTest; this pins the service
 * so the rules survive future refactors of the component.
 */
class PredefinedServiceAllocatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocates_shared_stock_across_multiple_workers(): void
    {
        $operator = $this->operator();
        [$service, $category] = $this->makeServiceWithCategory($operator, quantity: 10);
        $first = $this->worker(101);
        $second = $this->worker(102);

        $count = $this->allocator()->allocate(
            $service,
            [
                ['social_worker_id' => $first->id, 'allocations' => [$category->id => 4]],
                ['social_worker_id' => $second->id, 'allocations' => [$category->id => 6]],
            ],
            $operator->id,
        );

        $this->assertSame(2, $count);
        $this->assertEqualsWithDelta(4.0, (float) $this->allocationFor($service, $category, $first)->allocated_quantity, 0.001);
        $this->assertEqualsWithDelta(6.0, (float) $this->allocationFor($service, $category, $second)->allocated_quantity, 0.001);
    }

    public function test_rejects_combined_over_allocation_across_workers(): void
    {
        $operator = $this->operator();
        [$service, $category] = $this->makeServiceWithCategory($operator, quantity: 10);
        $first = $this->worker(101);
        $second = $this->worker(102);

        $this->expectException(ValidationException::class);

        try {
            $this->allocator()->allocate(
                $service,
                [
                    ['social_worker_id' => $first->id, 'allocations' => [$category->id => 7]],
                    ['social_worker_id' => $second->id, 'allocations' => [$category->id => 5]],
                ],
                $operator->id,
            );
        } catch (ValidationException $e) {
            $this->assertSame(0, ServiceWorkerAllocation::query()->where('service_id', $service->id)->count());

            throw $e;
        }
    }

    public function test_protects_allocation_created_by_another_operator(): void
    {
        $operator = $this->operator();
        $otherOperator = $this->operator();
        [$service, $category] = $this->makeServiceWithCategory($operator, quantity: 10);
        $worker = $this->worker(101);

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 2,
            'assigned_by_user_id' => $otherOperator->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->allocator()->allocate(
            $service,
            [
                ['social_worker_id' => $worker->id, 'allocations' => [$category->id => 3]],
            ],
            $operator->id,
        );
    }

    public function test_requires_each_group_to_carry_a_positive_row(): void
    {
        $operator = $this->operator();
        [$service, $category] = $this->makeServiceWithCategory($operator, quantity: 10);
        $worker = $this->worker(101);

        $this->expectException(ValidationException::class);

        $this->allocator()->allocate(
            $service,
            [
                ['social_worker_id' => $worker->id, 'allocations' => [$category->id => 0]],
            ],
            $operator->id,
        );
    }

    private function allocator(): PredefinedServiceAllocator
    {
        return app(PredefinedServiceAllocator::class);
    }

    private function operator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
    }

    private function worker(int $code): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => 'Worker',
            'last_name' => (string) $code,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Service, 1: ServiceCategory}
     */
    private function makeServiceWithCategory(User $creator, float $quantity): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Predefined Allocation Service',
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);
        $service = Service::query()->create([
            'name' => 'Predefined Allocation Service',
            'service_name_id' => $serviceName->id,
            'service_type' => 'individual',
            'total_quantity' => $quantity,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
        $category = ServiceCategory::query()->create([
            'service_name_id' => $serviceName->id,
            'service_id' => $service->id,
            'name' => 'Main Category',
            'quantity' => $quantity,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);

        return [$service, $category];
    }

    private function allocationFor(Service $service, ServiceCategory $category, SocialWorker $worker): ServiceWorkerAllocation
    {
        return ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->where('service_category_id', $category->id)
            ->where('social_worker_id', $worker->id)
            ->firstOrFail();
    }
}
