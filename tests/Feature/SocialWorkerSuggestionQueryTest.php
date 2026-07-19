<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\Deliveries\SocialWorkerSuggestionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the social-worker suggestion query object extracted out of the
 * ServiceBatchCreator Livewire component. The component-level autocomplete
 * behavior stays covered by DistributionOperatorAllocationAssignerTest; this
 * pins the projection and the "open allocation" accounting rules.
 */
class SocialWorkerSuggestionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_name_code_and_mobile_prefixes(): void
    {
        $ali = $this->worker(810, 'Ali', 'Rahimi', '0912111');
        $sara = $this->worker(811, 'Sara', 'Ahmadi', '0935222');

        $query = $this->query();

        $this->assertSame([$ali->id], $this->ids($query->search('Ali')));
        $this->assertSame([$ali->id], $this->ids($query->search('0912')));
        $this->assertSame([$sara->id], $this->ids($query->search('Ahmadi')));
        $this->assertSame([], $this->ids($query->search('Zzz')));
    }

    public function test_search_respects_limit_and_worker_code_order(): void
    {
        $third = $this->worker(830, 'Match', 'One', '0900001');
        $first = $this->worker(810, 'Match', 'Two', '0900002');
        $second = $this->worker(820, 'Match', 'Three', '0900003');

        $ids = $this->ids($this->query()->search('Match', 2));

        // Ordered by worker_code ascending, capped at the limit.
        $this->assertCount(2, $ids);
        $this->assertSame([$first->id, $second->id], $ids);
    }

    public function test_open_allocated_quantities_only_count_active_services(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = $this->worker(840, 'Busy', 'Worker', '0901234');

        $this->allocation($operator, $worker, 'in_distribution', 5);
        $this->allocation($operator, $worker, 'approved', 3);
        $this->allocation($operator, $worker, 'completed', 100); // must be excluded

        $query = $this->query();

        $this->assertEqualsWithDelta(8.0, (float) $query->openAllocatedQuantities([$worker->id])->get($worker->id), 0.001);
        $this->assertEqualsWithDelta(8.0, $query->openAllocatedQuantityFor($worker->id), 0.001);
    }

    public function test_open_allocated_quantities_is_empty_for_no_worker_ids(): void
    {
        $this->assertTrue($this->query()->openAllocatedQuantities([])->isEmpty());
    }

    public function test_find_returns_worker_with_projected_columns(): void
    {
        $worker = $this->worker(850, 'Counted', 'Worker', '0907777');

        $found = $this->query()->find($worker->id);

        $this->assertNotNull($found);
        $this->assertSame($worker->id, $found->id);
        $this->assertSame('Counted', $found->first_name);
        $this->assertSame('0907777', $found->mobile);

        // Behavior note: the shared projection selects an explicit column list
        // after withCount(), so the open_allocations_count subquery is dropped
        // and the payload falls back to 0. This mirrors the pre-refactor
        // component exactly; the guard pins that behavior against accidental
        // query reordering.
        $this->assertNull($found->open_allocations_count);
    }

    private function query(): SocialWorkerSuggestionQuery
    {
        return app(SocialWorkerSuggestionQuery::class);
    }

    private function worker(int $code, string $first, string $last, string $mobile): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'is_active' => true,
        ]);
    }

    private function allocation(User $operator, SocialWorker $worker, string $status, float $quantity): void
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Suggestion Service '.$status.' '.$quantity,
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $service = Service::query()->create([
            'name' => 'Suggestion Service '.$status.' '.$quantity,
            'service_name_id' => $serviceName->id,
            'service_type' => 'individual',
            'total_quantity' => $quantity,
            'total_service_value' => 0,
            'distribution_start_date' => now()->toDateString(),
            'status' => $status,
            'created_by' => $operator->id,
        ]);
        $category = ServiceCategory::query()->create([
            'service_name_id' => $serviceName->id,
            'service_id' => $service->id,
            'name' => 'Cat '.$status.' '.$quantity,
            'quantity' => $quantity,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => $quantity,
            'assigned_by_user_id' => $operator->id,
        ]);

        // Creating an allocation runs refreshDeliveryProgress(), which forces the
        // service into in_distribution. Pin the intended status afterwards with a
        // raw update so the query's status filter can be exercised directly.
        Service::query()->whereKey($service->id)->update(['status' => $status]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SocialWorker>  $workers
     * @return array<int, int>
     */
    private function ids($workers): array
    {
        return $workers->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
