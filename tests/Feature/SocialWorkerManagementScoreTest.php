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
use App\Models\SocialWorkerManagementScore;
use App\Models\User;
use App\Services\SocialWorkers\SocialWorkerPerformanceEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerManagementScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_management_score_is_ten_when_not_explicitly_recorded(): void
    {
        $worker = $this->worker(9301, 'Ali');
        $now = Jalalian::now();

        $this->assertSame(
            10,
            SocialWorkerManagementScore::getScoreFor($worker->id, $now->getYear(), $now->getMonth())
        );

        $this->assertSame(
            10,
            $worker->managementScoreFor($now->getYear(), $now->getMonth())
        );
    }

    public function test_manager_can_update_management_score(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9302, 'Reza');
        $now = Jalalian::now();

        $this->actingAs($manager);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('updateManagementScore', $worker->id, 8)
            ->assertSet('rankingSuccessMessage', 'ضریب ارزیابی مدیریت با موفقیت بروزرسانی شد.');

        $this->assertDatabaseHas('social_worker_management_scores', [
            'social_worker_id' => $worker->id,
            'year' => $now->getYear(),
            'month' => $now->getMonth(),
            'score' => 8,
            'updated_by' => $manager->id,
        ]);

        $this->assertSame(8, $worker->managementScoreFor($now->getYear(), $now->getMonth()));
    }

    public function test_manager_can_update_existing_management_score_repeatedly(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9310, 'Repeated');
        $now = Jalalian::now();

        $this->actingAs($manager);

        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('updateManagementScore', $worker->id, 8)
            ->assertSet('rankingSuccessMessage', 'ضریب ارزیابی مدیریت با موفقیت بروزرسانی شد.');

        $this->assertSame(8, $worker->fresh()->managementScoreFor($now->getYear(), $now->getMonth()));

        // Update again to 6 without throwing Eloquent Expression cast error
        $component->call('updateManagementScore', $worker->id, 6)
            ->assertSet('rankingSuccessMessage', 'ضریب ارزیابی مدیریت با موفقیت بروزرسانی شد.');

        $this->assertSame(6, $worker->fresh()->managementScoreFor($now->getYear(), $now->getMonth()));
        $this->assertDatabaseCount('social_worker_management_scores', 1);
    }

    public function test_non_manager_cannot_update_management_score(): void
    {
        $regularUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
        ]);
        $worker = $this->worker(9303, 'Sara');

        $this->actingAs($regularUser);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('updateManagementScore', $worker->id, 7)
            ->assertStatus(403);

        $this->assertDatabaseMissing('social_worker_management_scores', [
            'social_worker_id' => $worker->id,
        ]);
    }

    public function test_management_score_must_be_between_one_and_ten(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9304, 'Mina');

        $this->actingAs($manager);

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('updateManagementScore', $worker->id, 0)
            ->assertSet('rankingErrorMessage', 'ضریب مدیریت باید عددی صحیح بین ۱ تا ۱۰ باشد.');

        Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal')
            ->call('updateManagementScore', $worker->id, 11)
            ->assertSet('rankingErrorMessage', 'ضریب مدیریت باید عددی صحیح بین ۱ تا ۱۰ باشد.');

        $this->assertDatabaseMissing('social_worker_management_scores', [
            'social_worker_id' => $worker->id,
        ]);
    }

    public function test_management_score_multiplies_operational_score_and_changes_ranking(): void
    {
        $manager = $this->managerUser();

        $workerA = $this->worker(9305, 'Worker A');
        $workerB = $this->worker(9306, 'Worker B');

        [$service, $category] = $this->serviceWithCategory($manager);
        $assignedAt = Carbon::parse('2026-08-01 08:00:00');

        $this->allocate($service, $category, $workerA, 10, $assignedAt);
        $this->allocate($service, $category, $workerB, 10, $assignedAt);

        // Worker A and Worker B deliver identical performance
        $this->delivery($manager, $service, $category, $workerA, 10, '2026-08-01', $assignedAt->copy()->addHours(2));
        $this->delivery($manager, $service, $category, $workerB, 10, '2026-08-01', $assignedAt->copy()->addHours(2));

        $this->actingAs($manager);

        $now = Jalalian::now();

        // Initially both have management score 10 and equal scores
        $component = Livewire::test(AdvancedSocialWorkerReport::class)
            ->call('openRankingModal');

        $ranking = $component->instance()->ranking();
        $this->assertSame(10, $ranking['rows'][0]['management_score']);
        $this->assertSame(10, $ranking['rows'][1]['management_score']);
        $baseScore = $ranking['rows'][0]['score'];

        // Now manager reduces Worker A's management score to 5 due to underperformance
        $component->call('updateManagementScore', $workerA->id, 5);

        $newRanking = $component->instance()->ranking();

        // Worker B should now be ranked #1, and Worker A #2
        $this->assertSame($workerB->id, $newRanking['rows'][0]['id']);
        $this->assertSame($workerA->id, $newRanking['rows'][1]['id']);

        // Worker A's score should be exactly baseScore * 0.5
        $expectedScoreWorkerA = round($baseScore * 0.5, 1);
        $this->assertEquals($expectedScoreWorkerA, $newRanking['rows'][1]['score']);
        $this->assertSame(5, $newRanking['rows'][1]['management_score']);
        $this->assertEquals($baseScore, $newRanking['rows'][1]['base_score']);
    }

    public function test_scores_are_scoped_per_month_and_year(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9307, 'Zahra');

        $this->actingAs($manager);

        // Record score for month 8
        SocialWorkerManagementScore::create([
            'social_worker_id' => $worker->id,
            'year' => 1405,
            'month' => 8,
            'score' => 7,
            'updated_by' => $manager->id,
        ]);

        // Record score for month 9
        SocialWorkerManagementScore::create([
            'social_worker_id' => $worker->id,
            'year' => 1405,
            'month' => 9,
            'score' => 9,
            'updated_by' => $manager->id,
        ]);

        $this->assertSame(7, SocialWorkerManagementScore::getScoreFor($worker->id, 1405, 8));
        $this->assertSame(9, SocialWorkerManagementScore::getScoreFor($worker->id, 1405, 9));
        // Other month defaults to 10
        $this->assertSame(10, SocialWorkerManagementScore::getScoreFor($worker->id, 1405, 10));
    }

    public function test_performance_evaluator_evaluate_respects_custom_management_score(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9308, 'Single');

        [$service, $category] = $this->serviceWithCategory($manager);
        $assignedAt = Carbon::parse('2026-08-01 08:00:00');
        $this->allocate($service, $category, $worker, 5, $assignedAt);
        $this->delivery($manager, $service, $category, $worker, 5, '2026-08-01', $assignedAt->copy()->addHours(1));

        $evaluator = app(SocialWorkerPerformanceEvaluator::class);

        $defaultEval = $evaluator->evaluate($worker);
        $this->assertSame(10, $defaultEval['management_score']);
        $this->assertSame($defaultEval['base_score'], $defaultEval['score']);

        $customEval = $evaluator->evaluate($worker, managementScore: 8);
        $this->assertSame(8, $customEval['management_score']);
        $this->assertEquals(round($defaultEval['base_score'] * 0.8, 1), $customEval['score']);
    }

    private function managerUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
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

    private function serviceWithCategory(User $user): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Service '.Str::random(8),
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
            'distribution_start_date' => '2026-08-01',
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Cat '.Str::random(8),
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
