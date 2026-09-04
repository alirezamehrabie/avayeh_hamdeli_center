<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
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

/**
 * ارزیابی بازه‌ای رتبه‌بندی مددکاران: مدیر می‌تواند عملکرد را در یک بازهٔ تاریخی
 * دلخواه (مثلاً ۲ شهریور تا ۲ مهر) بسنجد؛ بدون بازه، رفتار پیشین (همهٔ زمان‌ها) حفظ است.
 */
class SocialWorkerRankingDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_range_only_counts_activity_inside_the_window(): void
    {
        $user = $this->adminUser();

        $earlyWorker = $this->worker(9301, 'Early');
        $insideWorker = $this->worker(9302, 'Inside');

        [$service, $category] = $this->serviceWithCategory($user);

        // فعالیت زودهنگام: کاملاً بیرون از بازهٔ ارزیابی.
        $earlyAssigned = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($service, $category, $earlyWorker, 10, $earlyAssigned);
        $this->delivery($user, $service, $category, $earlyWorker, 10, '2026-08-01', $earlyAssigned->copy()->addHours(3));

        // فعالیت داخل بازه.
        $insideAssigned = Carbon::parse('2026-09-01 09:00:00');
        $this->allocate($service, $category, $insideWorker, 10, $insideAssigned);
        $this->delivery($user, $service, $category, $insideWorker, 10, '2026-09-01', $insideAssigned->copy()->addHours(3));

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('rankingDateFrom', $this->jalali('2026-09-01'))
            ->set('rankingDateTo', $this->jalali('2026-09-30'))
            ->call('openRankingModal')
            ->assertSee('بازهٔ ارزیابی');

        $ranking = $component->instance()->ranking();
        $rows = collect($ranking['rows'])->keyBy('id');

        $this->assertTrue($rows[$insideWorker->id]['has_data']);
        $this->assertFalse($rows[$earlyWorker->id]['has_data']);
        $this->assertSame(0, $rows[$earlyWorker->id]['deliveries']);
        $this->assertSame($insideWorker->id, $ranking['rows'][0]['id']);
    }

    public function test_invalid_range_inputs_fall_back_to_all_time_evaluation(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9303, 'Only');

        [$service, $category] = $this->serviceWithCategory($user);
        $assignedAt = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($service, $category, $worker, 10, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 10, '2026-08-01', $assignedAt->copy()->addHours(3));

        $this->actingAs($user);

        // ماه ۱۳ جلالی نامعتبر است و باید نادیده گرفته شود، نه اینکه رتبه‌بندی را خالی کند.
        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('rankingDateFrom', '1405/13/05')
            ->set('rankingDateTo', 'بی‌ربط')
            ->call('openRankingModal');

        $this->assertNull($component->instance()->rankingRange);

        $ranking = $component->instance()->ranking();

        $this->assertTrue($ranking['rows'][0]['has_data']);
        $this->assertSame($worker->id, $ranking['rows'][0]['id']);
    }

    public function test_clear_ranking_range_restores_all_time_ranking(): void
    {
        $user = $this->adminUser();
        $this->worker(9304, 'Plain');

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('rankingDateFrom', $this->jalali('2026-09-01'))
            ->call('openRankingModal');

        $this->assertNotNull($component->instance()->rankingRange);

        $component->call('clearRankingRange');

        $this->assertSame('', $component->instance()->rankingDateFrom);
        $this->assertNull($component->instance()->rankingRange);
    }

    /**
     * در ارزیابی بازه‌ای، تخصیص بی‌پاسخ تا پایان همان بازه سنجیده می‌شود؛
     * وگرنه تخصیصِ تازهٔ انتهای یک بازهٔ گذشته بی‌دلیل «رهاشده» حساب می‌شود.
     */
    public function test_pending_allocations_are_penalized_relative_to_the_range_end(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9305, 'Pending');

        [$service, $category] = $this->serviceWithCategory($user);
        $this->allocate($service, $category, $worker, 10, Carbon::now()->subDays(23));

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);

        // همهٔ زمان‌ها: ۲۳ روز بی‌پاسخی یعنی فراتر از مهلت رهاشدگی → امتیاز صفر.
        $allTime = $evaluator->evaluate($worker);
        $this->assertSame(0.0, $allTime['metrics']['response']['score']);

        // بازهٔ گذشته: در پایان بازه تنها ~۳ روز از تخصیص می‌گذشت → جریمهٔ سبک‌تر.
        $ranged = $evaluator->evaluate($worker, [
            'from' => Carbon::now()->subDays(25),
            'to' => Carbon::now()->subDays(20),
        ]);

        $this->assertSame(20.0, $ranged['metrics']['response']['score']);
        $this->assertNotNull($ranged['range']);
    }

    /**
     * همان تضمین هم‌خوانی evaluate/rankMany باید در حالت بازه‌ای هم برقرار بماند.
     */
    public function test_rank_many_matches_single_evaluation_within_a_range(): void
    {
        $user = $this->adminUser();

        $inside = $this->worker(9306, 'Inside');
        $outside = $this->worker(9307, 'Outside');

        [$service, $category] = $this->serviceWithCategory($user);

        $insideAssigned = Carbon::parse('2026-09-02 08:00:00');
        $this->allocate($service, $category, $inside, 8, $insideAssigned);
        $this->delivery($user, $service, $category, $inside, 8, '2026-09-02', $insideAssigned->copy()->addHours(5));

        $outsideAssigned = Carbon::parse('2026-08-02 08:00:00');
        $this->allocate($service, $category, $outside, 8, $outsideAssigned);
        $this->delivery($user, $service, $category, $outside, 8, '2026-08-02', $outsideAssigned->copy()->addHours(5));

        $range = ['from' => '2026-09-01', 'to' => '2026-09-30'];

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);
        $ranked = $evaluator->rankMany([$inside->id, $outside->id], $range);

        foreach ([$inside, $outside] as $worker) {
            $single = $evaluator->evaluate($worker, $range);

            $this->assertSame($single['has_data'], $ranked[$worker->id]['has_data']);
            $this->assertSame($single['score'], $ranked[$worker->id]['score']);
            $this->assertSame($single['grade']['label'], $ranked[$worker->id]['grade']['label']);
            $this->assertSame($single['delivery_summary']['count'], $ranked[$worker->id]['delivery_summary']['count']);
        }

        $this->assertTrue($ranked[$inside->id]['has_data']);
        $this->assertFalse($ranked[$outside->id]['has_data']);
    }

    public function test_consistency_counts_the_months_spanned_by_the_range(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9308, 'Monthly');

        [$service, $category] = $this->serviceWithCategory($user);
        $this->allocate($service, $category, $worker, 5, Carbon::parse('2026-08-09 09:00:00'));
        $this->delivery($user, $service, $category, $worker, 5, '2026-08-10', Carbon::parse('2026-08-10 12:00:00'));

        // بازه مرداد و شهریور را می‌پوشاند؛ تحویل فقط در یک ماه آن ثبت شده است.
        $performance = app(SocialWorkerPerformanceEvaluator::class)
            ->evaluate($worker, ['from' => '2026-08-01', 'to' => '2026-09-15']);

        $consistency = $performance['metrics']['consistency'];

        $this->assertSame(2, $consistency['stats']['total_months']);
        $this->assertSame(1, $consistency['stats']['active_months']);
        $this->assertSame(50.0, $consistency['score']);
    }

    public function test_performance_tab_from_ranking_respects_the_range(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9309, 'Drill');

        [$service, $category] = $this->serviceWithCategory($user);

        $insideAssigned = Carbon::parse('2026-09-01 09:00:00');
        $this->allocate($service, $category, $worker, 6, $insideAssigned);
        $this->delivery($user, $service, $category, $worker, 6, '2026-09-01', $insideAssigned->copy()->addHours(2));

        // تخصیص بیرون از بازه روی خدمت دوم؛ (خدمت، مددکار، دسته) یکتا است.
        [$outsideService, $outsideCategory] = $this->serviceWithCategory($user);
        $outsideAssigned = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($outsideService, $outsideCategory, $worker, 6, $outsideAssigned);
        $this->delivery($user, $outsideService, $outsideCategory, $worker, 6, '2026-08-01', $outsideAssigned->copy()->addHours(2));

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('rankingDateFrom', $this->jalali('2026-09-01'))
            ->set('rankingDateTo', $this->jalali('2026-09-30'))
            ->call('openRankingModal')
            ->call('showWorkerFromRanking', $worker->id)
            ->assertSee('ارزیابی در بازهٔ');

        $performance = $component->instance()->workerPerformance();

        $this->assertNotNull($performance['range']);
        $this->assertSame(1, $performance['metrics']['fulfillment']['stats']['total_allocations']);
        $this->assertSame(1, $performance['delivery_summary']['count']);
    }

    public function test_performance_tab_opened_directly_ignores_the_ranking_range(): void
    {
        $user = $this->adminUser();
        $worker = $this->worker(9310, 'Direct');

        [$service, $category] = $this->serviceWithCategory($user);
        $assignedAt = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($service, $category, $worker, 6, $assignedAt);
        $this->delivery($user, $service, $category, $worker, 6, '2026-08-01', $assignedAt->copy()->addHours(2));

        $this->actingAs($user);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->set('rankingDateFrom', $this->jalali('2026-09-01'))
            ->set('rankingDateTo', $this->jalali('2026-09-30'))
            ->call('showWorkerInfo', $worker->id)
            ->call('setWorkerModalTab', 'performance');

        $performance = $component->instance()->workerPerformance();

        $this->assertNull($performance['range']);
        $this->assertSame(1, $performance['metrics']['fulfillment']['stats']['total_allocations']);
    }

    private function jalali(string $gregorianDate): string
    {
        return Jalalian::fromDateTime($gregorianDate)->format('Y/m/d');
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
            'name' => 'Range '.Str::random(8),
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
