<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\AdvancedSocialWorkerReport;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\SocialWorkers\SocialWorkerPerformanceEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerPerformanceEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fast_responding_worker_scores_higher_than_a_slow_one(): void
    {
        $user = $this->adminUser();

        $fastWorker = $this->worker(9101, 'Fast');
        $slowWorker = $this->worker(9102, 'Slow');

        [$service, $category] = $this->serviceWithCategory($user);

        $assignedAt = Carbon::parse('2026-08-01 09:00:00');

        $this->allocate($service, $category, $fastWorker, 10, $assignedAt);
        $this->allocate($service, $category, $slowWorker, 10, $assignedAt);

        // مددکار سریع، همان روز تحویل را ثبت کرده است.
        $this->delivery($user, $service, $category, $fastWorker, 10, '2026-08-01', $assignedAt->copy()->addHours(3));
        // مددکار کند، ده روز بعد اقدام کرده است.
        $this->delivery($user, $service, $category, $slowWorker, 10, '2026-08-11', $assignedAt->copy()->addDays(10));

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);

        $fast = $evaluator->evaluate($fastWorker);
        $slow = $evaluator->evaluate($slowWorker);

        $this->assertTrue($fast['has_data']);
        $this->assertGreaterThan($slow['score'], $fast['score']);
        $this->assertGreaterThan($slow['metrics']['response']['score'], $fast['metrics']['response']['score']);
        $this->assertSame(1, $this->bucketCount($fast, 'within_day'));
        $this->assertSame(1, $this->bucketCount($slow, 'within_two_weeks'));
        $this->assertSame(3.0, $fast['metrics']['response']['stats']['median_hours']);
    }

    public function test_unanswered_allocation_past_deadline_drives_response_score_to_zero(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9103, 'Idle');

        [$service, $category] = $this->serviceWithCategory($user);

        $this->allocate($service, $category, $worker, 5, Carbon::now()->subDays(20));

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertTrue($performance['has_data']);
        $this->assertSame(0.0, $performance['metrics']['response']['score']);
        $this->assertSame(0.0, $performance['metrics']['fulfillment']['score']);
        $this->assertSame(0.0, $performance['metrics']['coverage']['score']);
        $this->assertSame(1, $performance['metrics']['response']['stats']['pending_overdue']);
        $this->assertCount(1, $performance['open_allocations']);
        $this->assertNull($performance['open_allocations'][0]['response_label']);
    }

    public function test_allocation_still_inside_grace_window_is_excluded_from_response_average(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9104, 'Fresh');

        [$service, $category] = $this->serviceWithCategory($user);

        $this->allocate($service, $category, $worker, 5, Carbon::now()->subHours(2));

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertSame(0.0, $performance['metrics']['response']['score']);
        $this->assertSame(0, $performance['metrics']['response']['stats']['pending_overdue']);
        $this->assertSame(1, $performance['metrics']['response']['stats']['pending_in_grace']);
    }

    public function test_partial_delivery_reduces_fulfillment_but_keeps_coverage_full(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9105, 'Partial');

        [$service, $category] = $this->serviceWithCategory($user);

        $assignedAt = Carbon::parse('2026-08-02 08:00:00');
        $this->allocate($service, $category, $worker, 10, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 4, '2026-08-02', $assignedAt->copy()->addHours(5));

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertSame(40.0, $performance['metrics']['fulfillment']['score']);
        $this->assertSame(100.0, $performance['metrics']['coverage']['score']);
        $this->assertSame(6.0, $performance['metrics']['fulfillment']['stats']['remaining_quantity']);
        $this->assertCount(1, $performance['open_allocations']);
        $this->assertNotNull($performance['open_allocations'][0]['response_label']);
    }

    public function test_late_delivery_lowers_punctuality_score(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9106, 'Late');

        [$service, $category] = $this->serviceWithCategory($user, '2026-08-01', '2026-08-10');

        $assignedAt = Carbon::parse('2026-08-01 08:00:00');
        $this->allocate($service, $category, $worker, 4, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 2, '2026-08-05', $assignedAt->copy()->addHours(4));
        $this->delivery($user, $service, $category, $worker, 2, '2026-08-25', $assignedAt->copy()->addHours(6));

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertSame(50.0, $performance['metrics']['punctuality']['score']);
        $this->assertSame(1, $performance['metrics']['punctuality']['stats']['late']);
        $this->assertFalse($performance['metrics']['punctuality']['stats']['is_estimated']);
    }

    public function test_worker_without_allocations_or_deliveries_reports_no_data(): void
    {
        $worker = $this->worker(9107, 'Empty');

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertFalse($performance['has_data']);
        $this->assertSame(0.0, $performance['score']);
        $this->assertSame(0.0, $performance['stars']);
    }

    public function test_star_rating_stays_within_five_and_matches_score(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9108, 'Star');

        [$service, $category] = $this->serviceWithCategory($user, '2026-08-01', '2026-08-30');

        $assignedAt = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($service, $category, $worker, 10, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 10, '2026-08-01', $assignedAt->copy()->addHour());

        $performance = app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker);

        $this->assertLessThanOrEqual(5.0, $performance['stars']);
        $this->assertGreaterThan(0.0, $performance['stars']);
        $this->assertSame(round($performance['score'] / 100 * 5 * 2) / 2, $performance['stars']);
    }

    public function test_modal_exposes_performance_tab_with_evaluation_payload(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9109, 'Modal');

        [$service, $category] = $this->serviceWithCategory($user);

        $assignedAt = Carbon::parse('2026-08-03 10:00:00');
        $this->allocate($service, $category, $worker, 6, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 6, '2026-08-03', $assignedAt->copy()->addHours(2));

        $this->actingAs($user);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('showWorkerInfo', $worker->id)
            ->assertSet('workerModalTab', 'profile')
            ->call('setWorkerModalTab', 'performance')
            ->assertSet('workerModalTab', 'performance')
            ->assertSee('ارزیابی عملکرد')
            ->assertSee('سرعت واکنش به تخصیص')
            ->call('setWorkerModalTab', 'unknown-tab')
            ->assertSet('workerModalTab', 'performance')
            ->call('closeWorkerModal')
            ->assertSet('workerModalTab', 'profile')
            ->assertSet('showWorkerModal', false);
    }

    public function test_performance_tab_renders_empty_state_for_worker_without_allocations(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9110, 'Blank');

        $this->actingAs($user);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('showWorkerInfo', $worker->id)
            ->call('setWorkerModalTab', 'performance')
            ->assertSee('داده‌ای برای ارزیابی وجود ندارد')
            ->assertDontSee('امتیاز کل عملکرد');
    }

    private function bucketCount(array $performance, string $bucketKey): int
    {
        return (int) collect($performance['response_distribution'])
            ->firstWhere('key', $bucketKey)['count'];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function worker(int $code, string $label): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => $label,
            'last_name' => 'Worker',
        ]);
    }

    /**
     * @return array{0: Service, 1: ServiceCategory}
     */
    private function serviceWithCategory(User $user, ?string $start = null, ?string $end = null): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Perf '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 100,
            'total_service_value' => 0,
            'distribution_start_date' => $start ?? '2026-08-01',
            'distribution_end_date' => $end,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Pack '.Str::random(8),
            'quantity' => 100,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return [$service, $category];
    }

    private function allocate(
        Service $service,
        ServiceCategory $category,
        SocialWorker $worker,
        float $quantity,
        Carbon $assignedAt,
    ): ServiceWorkerAllocation {
        $allocation = ServiceWorkerAllocation::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => $quantity,
        ]);

        // $fillable شامل timestamps نیست، پس زمان تخصیص باید force-fill شود.
        $allocation->forceFill(['created_at' => $assignedAt, 'updated_at' => $assignedAt])->save();

        return $allocation->fresh();
    }

    private function delivery(
        User $user,
        Service $service,
        ServiceCategory $category,
        SocialWorker $worker,
        float $quantity,
        string $deliveredAt,
        Carbon $registeredAt,
    ): ServiceDelivery {
        $delivery = ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'national_id' => (string) random_int(1000000000, 9999999999),
            'full_name' => 'Recipient '.Str::random(5),
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'delivered_quantity' => $quantity,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => (int) ($quantity * 1000),
            'delivered_at' => $deliveredAt,
            'created_by' => $user->id,
        ]);

        $delivery->forceFill(['created_at' => $registeredAt, 'updated_at' => $registeredAt])->save();

        return $delivery->fresh();
    }
}
