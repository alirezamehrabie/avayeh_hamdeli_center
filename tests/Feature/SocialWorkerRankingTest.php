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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_orders_workers_by_performance_score(): void
    {
        $user = $this->adminUser();

        $fastWorker = $this->worker(9201, 'Fast');
        $slowWorker = $this->worker(9202, 'Slow');
        $idleWorker = $this->worker(9203, 'Idle');

        [$service, $category] = $this->serviceWithCategory($user);

        $assignedAt = Carbon::parse('2026-08-01 09:00:00');

        $this->allocate($service, $category, $fastWorker, 10, $assignedAt);
        $this->allocate($service, $category, $slowWorker, 10, $assignedAt);

        $this->delivery($user, $service, $category, $fastWorker, 10, '2026-08-01', $assignedAt->copy()->addHours(3));
        $this->delivery($user, $service, $category, $slowWorker, 4, '2026-08-11', $assignedAt->copy()->addDays(10));

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->assertSet('showRankingModal', true)
            ->assertSee('جدول رتبه‌بندی مددکاران')
            ->assertSee('Fast Worker')
            ->assertSee('بدون داده');

        $ranking = $component->instance()->ranking();

        $this->assertSame(3, $ranking['total']);
        $this->assertSame(3, $ranking['evaluated']);
        $this->assertFalse($ranking['truncated']);

        $ids = array_column($ranking['rows'], 'id');
        $this->assertSame([$fastWorker->id, $slowWorker->id, $idleWorker->id], $ids);

        // نام کامل باید از first_name/last_name ساخته شود، نه از ستون خام دیتابیس.
        $this->assertSame(
            ['Fast Worker', 'Slow Worker', 'Idle Worker'],
            array_column($ranking['rows'], 'full_name')
        );

        $this->assertGreaterThan($ranking['rows'][1]['score'], $ranking['rows'][0]['score']);
        $this->assertTrue($ranking['rows'][0]['has_data']);
        $this->assertFalse($ranking['rows'][2]['has_data']);
        $this->assertSame('بدون داده', $ranking['rows'][2]['grade']['label']);
        $this->assertSame(4.5, $ranking['rows'][0]['stars']);
    }

    public function test_ranking_respects_active_filters(): void
    {
        $user = $this->adminUser();

        $matching = $this->worker(9204, 'Match');
        $this->worker(9205, 'Other');

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('globalSearch', '9204')
            ->call('openRankingModal');

        $ranking = $component->instance()->ranking();

        $this->assertSame(1, $ranking['total']);
        $this->assertSame([$matching->id], array_column($ranking['rows'], 'id'));
    }

    public function test_ranking_is_not_computed_while_modal_is_closed(): void
    {
        $user = $this->adminUser();
        $this->worker(9206, 'Hidden');

        $this->actingAs($user);

        $ranking = Livewire::test(AdvancedSocialWorkerReport::class)->instance()->ranking();

        $this->assertSame([], $ranking['rows']);
        $this->assertSame(0, $ranking['total']);
    }

    public function test_selecting_a_ranked_worker_opens_the_performance_tab(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9207, 'Jump');

        [$service, $category] = $this->serviceWithCategory($user);
        $assignedAt = Carbon::parse('2026-08-03 10:00:00');
        $this->allocate($service, $category, $worker, 6, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 6, '2026-08-03', $assignedAt->copy()->addHours(2));

        $this->actingAs($user);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('showWorkerFromRanking', $worker->id)
            ->assertSet('showRankingModal', false)
            ->assertSet('showWorkerModal', true)
            ->assertSet('selectedWorkerId', $worker->id)
            ->assertSet('workerModalTab', 'performance')
            ->assertSee('سرعت واکنش به تخصیص');
    }

    /**
     * فهرست گروهی باید همان امتیازی را بدهد که ارزیابی تک‌نفره می‌دهد.
     */
    public function test_rank_many_matches_single_worker_evaluation(): void
    {
        $user = $this->adminUser();

        $first = $this->worker(9208, 'First');
        $second = $this->worker(9209, 'Second');

        [$service, $category] = $this->serviceWithCategory($user, '2026-08-01', '2026-08-05');

        $assignedAt = Carbon::parse('2026-08-01 08:00:00');
        $this->allocate($service, $category, $first, 8, $assignedAt);
        $this->allocate($service, $category, $second, 8, $assignedAt);

        $this->delivery($user, $service, $category, $first, 8, '2026-08-02', $assignedAt->copy()->addHours(5));
        $this->delivery($user, $service, $category, $second, 3, '2026-08-09', $assignedAt->copy()->addDays(4));

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);
        $ranked = $evaluator->rankMany([$first->id, $second->id]);

        foreach ([$first, $second] as $worker) {
            $single = $evaluator->evaluate($worker);

            $this->assertSame($single['score'], $ranked[$worker->id]['score']);
            $this->assertSame($single['stars'], $ranked[$worker->id]['stars']);
            $this->assertSame($single['grade']['label'], $ranked[$worker->id]['grade']['label']);
            $this->assertSame($single['delivery_summary']['count'], $ranked[$worker->id]['delivery_summary']['count']);

            foreach ($single['metrics'] as $key => $metric) {
                $this->assertSame($metric['score'], $ranked[$worker->id]['metrics'][$key]['score'], "metric {$key}");
            }
        }
    }

    /**
     * فهرست رتبه‌بندی باید با تعداد کوئری ثابت اجرا شود، مستقل از تعداد مددکاران.
     */
    public function test_ranking_uses_a_constant_number_of_queries(): void
    {
        $user = $this->adminUser();

        [$service, $category] = $this->serviceWithCategory($user);
        $assignedAt = Carbon::parse('2026-08-01 09:00:00');

        $workers = [];
        foreach (range(9210, 9219) as $index => $code) {
            $worker = $this->worker($code, 'Bulk'.$index);
            $workers[] = $worker;
            $this->allocate($service, $category, $worker, 5, $assignedAt);
            $this->delivery($user, $service, $category, $worker, 5, '2026-08-02', $assignedAt->copy()->addHours($index + 1));
        }

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);
        $ids = array_map(fn (SocialWorker $worker): int => $worker->id, $workers);

        DB::enableQueryLog();
        $evaluator->rankMany(array_slice($ids, 0, 2));
        $twoWorkerQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $evaluator->rankMany($ids);
        $tenWorkerQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoWorkerQueries, $tenWorkerQueries);
        $this->assertLessThanOrEqual(6, $tenWorkerQueries);
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
            'name' => 'Rank '.Str::random(8),
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
