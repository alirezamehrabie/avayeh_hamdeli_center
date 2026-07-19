<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\Deliveries\MiscServiceEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the misc-service edit persistence extracted out of the
 * ServiceBatchCreator component. The component still owns validation, locking
 * and the category-resolution caches; this pins the write half (category /
 * allocation upsert + prune + header update) in isolation.
 *
 * Component-level behavior stays covered by
 * DistributionOperatorAllocationAssignerTest.
 */
class MiscServiceEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_upserts_categories_and_allocations_and_prunes_removed_ones(): void
    {
        $operator = $this->operator();
        $workerA = $this->worker(701);
        $workerB = $this->worker(702);
        [$service, $serviceNameId] = $this->makeService($operator);

        // Seed an existing category assigned to worker A that will be dropped.
        $stale = $this->category($service, $serviceNameId, 'قدیمی', 'pack', 3, $operator, sortId: 1);
        $service->workerAllocations()->create([
            'service_category_id' => $stale->id,
            'social_worker_id' => $workerA->id,
            'allocated_quantity' => 3,
            'assigned_by_user_id' => $operator->id,
        ]);

        $service->setRelation('categories', $service->categories()->get());
        $existingCategories = $service->categories()->withTrashed()->get()->keyBy('id');
        $existingAllocations = ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->get()
            ->keyBy(fn (ServiceWorkerAllocation $a): string => $a->service_category_id.':'.$a->social_worker_id);

        // New submit: a brand new category assigned to worker B (stale one omitted).
        $categoryPayloads = [
            'new:'.ServiceCategory::normalizeName('تازه') => [
                'existing_id' => null,
                'name' => 'تازه',
                'unit' => 'pack',
                'quantity' => 0.0,
                'sort_id' => 1,
            ],
        ];
        $categoryWorkerAllocations = [
            'new:'.ServiceCategory::normalizeName('تازه') => [$workerB->id => 5.0],
        ];
        // Reflect the accumulated quantity the component would have summed.
        $categoryPayloads['new:'.ServiceCategory::normalizeName('تازه')]['quantity'] = 5.0;

        $version = app(MiscServiceEditor::class)->apply(
            $service,
            $existingCategories,
            $existingAllocations,
            $categoryPayloads,
            $categoryWorkerAllocations,
            $serviceNameId,
            'individual',
            'توضیح تازه',
            '2026-06-25',
            $operator->id,
        );

        $this->assertNotNull($version);

        // Stale category and its allocation are gone.
        $this->assertSoftDeleted('service_categories', ['id' => $stale->id]);
        $this->assertDatabaseMissing('service_social_worker', [
            'service_category_id' => $stale->id,
            'social_worker_id' => $workerA->id,
        ]);

        // New category persisted and allocated to worker B.
        $fresh = $service->fresh();
        $newCategory = $fresh->categories()->where('name', 'تازه')->firstOrFail();
        $this->assertEqualsWithDelta(5.0, (float) $newCategory->quantity, 0.001);
        $this->assertDatabaseHas('service_social_worker', [
            'service_category_id' => $newCategory->id,
            'social_worker_id' => $workerB->id,
            'allocated_quantity' => 5,
        ]);

        // Header updated.
        $this->assertSame('توضیح تازه', $fresh->description);
        $this->assertEqualsWithDelta(5.0, (float) $fresh->total_quantity, 0.001);
        $this->assertSame('2026-06-25', $fresh->distribution_start_date->toDateString());
    }

    public function test_reuses_existing_allocation_row_when_worker_and_category_retained(): void
    {
        $operator = $this->operator();
        $worker = $this->worker(710);
        [$service, $serviceNameId] = $this->makeService($operator);

        $category = $this->category($service, $serviceNameId, 'ثابت', 'pack', 4, $operator, sortId: 1);
        $originalAllocation = $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $operator->id,
        ]);

        $service->setRelation('categories', $service->categories()->get());
        $existingCategories = $service->categories()->withTrashed()->get()->keyBy('id');
        $existingAllocations = ServiceWorkerAllocation::query()
            ->where('service_id', $service->id)
            ->get()
            ->keyBy(fn (ServiceWorkerAllocation $a): string => $a->service_category_id.':'.$a->social_worker_id);

        $key = 'existing:'.$category->id;
        $categoryPayloads = [
            $key => [
                'existing_id' => $category->id,
                'name' => 'ثابت',
                'unit' => 'pack',
                'quantity' => 9.0,
                'sort_id' => 1,
            ],
        ];
        $categoryWorkerAllocations = [$key => [$worker->id => 9.0]];

        app(MiscServiceEditor::class)->apply(
            $service,
            $existingCategories,
            $existingAllocations,
            $categoryPayloads,
            $categoryWorkerAllocations,
            $serviceNameId,
            'individual',
            null,
            '2026-06-25',
            $operator->id,
        );

        // Same pivot row reused (id preserved), quantity updated in place.
        $this->assertSame(1, ServiceWorkerAllocation::query()->where('service_id', $service->id)->count());
        $reused = ServiceWorkerAllocation::query()->firstOrFail();
        $this->assertSame($originalAllocation->id, $reused->id);
        $this->assertEqualsWithDelta(9.0, (float) $reused->allocated_quantity, 0.001);
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
     * @return array{0: Service, 1: int}
     */
    private function makeService(User $operator): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Editable Misc Service',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $service = Service::query()->create([
            'name' => 'Editable Misc Service',
            'service_name_id' => $serviceName->id,
            'service_type' => 'individual',
            'total_quantity' => 3,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->toDateString(),
            'status' => 'in_distribution',
            'created_by' => $operator->id,
        ]);

        return [$service, (int) $serviceName->id];
    }

    private function category(Service $service, int $serviceNameId, string $name, string $unit, float $quantity, User $operator, int $sortId): ServiceCategory
    {
        return ServiceCategory::query()->create([
            'service_name_id' => $serviceNameId,
            'service_id' => $service->id,
            'name' => $name,
            'quantity' => $quantity,
            'unit' => $unit,
            'value' => 0,
            'sort_id' => $sortId,
            'created_by' => $operator->id,
        ]);
    }
}
