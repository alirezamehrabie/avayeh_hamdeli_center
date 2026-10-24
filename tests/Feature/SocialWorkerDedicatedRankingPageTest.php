<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\SocialWorkers\SocialWorkerRanking;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerDedicatedRankingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dedicated_ranking_page_is_accessible(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $response = $this->get(route('admin.social-workers.ranking'));
        $response->assertOk();
        $response->assertSee('رتبه‌بندی و ضریب ارزیابی مدیریت مددکاران اجتماعی');
        $response->assertSee('بازگشت به گزارش پیشرفته');
        // بررسی بارگذاری در قالب اصلی داشبورد همراه با سایدبار
        $response->assertSee('dashboard-sidebar');
        $response->assertSee('لیست مددکاران');
    }

    public function test_dedicated_ranking_page_initializes_with_current_jalali_month_range(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $now = Jalalian::now();
        $monthDays = (new Jalalian($now->getYear(), $now->getMonth(), 1))->getMonthDays();
        $expectedFrom = sprintf('%04d/%02d/01', $now->getYear(), $now->getMonth());
        $expectedTo = sprintf('%04d/%02d/%02d', $now->getYear(), $now->getMonth(), $monthDays);

        Livewire::test(SocialWorkerRanking::class)
            ->assertSet('rankingYear', (int) $now->getYear())
            ->assertSet('rankingMonth', (int) $now->getMonth())
            ->assertSet('rankingDateFrom', $expectedFrom)
            ->assertSet('rankingDateTo', $expectedTo);
    }

    public function test_advanced_report_ranking_link_opens_in_same_tab(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $response = $this->get(route('admin.dashboard', ['section' => 'advanced-social-worker-report']));
        $response->assertOk();
        $response->assertSee(route('admin.social-workers.ranking'));
        $response->assertDontSee('href="'.route('admin.social-workers.ranking').'" target="_blank"', false);
    }

    public function test_dedicated_ranking_page_renders_ranked_cards(): void
    {
        $manager = $this->managerUser();
        $worker1 = $this->worker(9401, 'Maryam');
        $worker2 = $this->worker(9402, 'Hossein');

        [$service, $category] = $this->serviceWithCategory($manager);
        $assignedAt = Carbon::parse('2026-08-01 09:00:00');

        $this->allocate($service, $category, $worker1, 10, $assignedAt);
        $this->allocate($service, $category, $worker2, 10, $assignedAt);

        $this->delivery($manager, $service, $category, $worker1, 10, '2026-08-01', $assignedAt->copy()->addHours(2));
        $this->delivery($manager, $service, $category, $worker2, 5, '2026-08-05', $assignedAt->copy()->addDays(4));

        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->assertSee('Maryam Worker')
            ->assertSee('Hossein Worker');

        $ranking = $component->instance()->ranking();
        $this->assertCount(2, $ranking['rows']);
        $this->assertSame($worker1->id, $ranking['rows'][0]['id']);
        $this->assertSame($worker2->id, $ranking['rows'][1]['id']);
    }

    public function test_manager_can_update_management_score_on_dedicated_page(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9403, 'Saeed');
        $now = Jalalian::now();

        $this->actingAs($manager);

        Livewire::test(SocialWorkerRanking::class)
            ->call('updateManagementScore', $worker->id, 8)
            ->assertSet('rankingSuccessMessage', 'ضریب ارزیابی مدیریت با موفقیت بروزرسانی شد.');

        $this->assertDatabaseHas('social_worker_management_scores', [
            'social_worker_id' => $worker->id,
            'year' => $now->getYear(),
            'month' => $now->getMonth(),
            'score' => 8,
            'updated_by' => $manager->id,
        ]);
    }

    public function test_search_and_filtering_on_dedicated_page(): void
    {
        $manager = $this->managerUser();
        $worker1 = $this->worker(9404, 'Nader');
        $worker2 = $this->worker(9405, 'Parisa');

        $this->actingAs($manager);

        Livewire::test(SocialWorkerRanking::class)
            ->set('search', 'Nader')
            ->assertSee('Nader Worker')
            ->assertDontSee('Parisa Worker');
    }

    public function test_instant_modal_structure_and_skeleton_loader(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9407, 'Kamran');
        $this->actingAs($manager);

        $response = $this->get(route('admin.social-workers.ranking'));
        $response->assertOk();

        // بررسی وجود کانتینر مشترک مودال و اسکلتون لودر
        $response->assertSee('wire:key="shared-social-worker-modal"', false);
        $response->assertSee('x-show="isLoading"', false);
        $response->assertSee('animate-pulse');
        $response->assertSee('retryLoad()', false);
        $response->assertSee('@click.stop', false);
        $response->assertSee('@mousedown.stop', false);
    }

    public function test_modal_loads_worker_details_via_livewire(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9406, 'Reza');
        $this->actingAs($manager);

        Livewire::test(SocialWorkerRanking::class)
            ->call('showWorkerInfo', $worker->id)
            ->assertSet('selectedWorkerId', $worker->id)
            ->assertSet('showWorkerModal', true)
            ->assertSee('Reza Worker')
            ->call('closeWorkerModal')
            ->assertSet('selectedWorkerId', null)
            ->assertSet('showWorkerModal', false);
    }

    public function test_card_markup_includes_event_delegation_and_month_metadata(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9408, 'Arman');
        $this->actingAs($manager);

        $response = $this->get(route('admin.social-workers.ranking'));
        $response->assertOk();

        // بررسی اتصال رویداد کلیک به کانتینر والد با Event Delegation
        $response->assertSee('@click="handleCardClick($event)"', false);
        // بررسی شناسه و متادیتای کارت برای بازیابی امن در جاوااسکریپت
        $response->assertSee('data-worker-card="true"', false);
        $response->assertSee('data-worker-id="'.$worker->id.'"', false);
        $response->assertSee('data-ranking-year=', false);
        $response->assertSee('data-ranking-month=', false);
        $response->assertSee('data-no-modal="true"', false);
    }

    public function test_filter_and_month_changes_reset_modal_state(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9409, 'Sara');
        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->call('showWorkerInfo', $worker->id)
            ->assertSet('selectedWorkerId', $worker->id)
            ->assertSet('showWorkerModal', true);

        // تغییر ماه ارزیابی باید استیت مودال را ریست کند
        $component->set('rankingMonth', 5)
            ->assertSet('selectedWorkerId', null)
            ->assertSet('showWorkerModal', false);

        // باز کردن مجدد مودال و تست دکمه ماه قبل
        $component->call('showWorkerInfo', $worker->id)
            ->assertSet('showWorkerModal', true)
            ->call('setRankingToPreviousMonth')
            ->assertSet('selectedWorkerId', null)
            ->assertSet('showWorkerModal', false);

        // باز کردن مجدد مودال و تست تغییر سال
        $component->call('showWorkerInfo', $worker->id)
            ->assertSet('showWorkerModal', true)
            ->set('rankingYear', 1404)
            ->assertSet('selectedWorkerId', null)
            ->assertSet('showWorkerModal', false);
    }

    public function test_show_worker_info_accepts_custom_year_and_month(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9410, 'Navid');
        $this->actingAs($manager);

        Livewire::test(SocialWorkerRanking::class)
            ->call('showWorkerInfo', $worker->id, 1403, 6)
            ->assertSet('selectedWorkerId', $worker->id)
            ->assertSet('showWorkerModal', true)
            ->assertSet('rankingYear', 1403)
            ->assertSet('rankingMonth', 6)
            ->assertSet('rankingDateFrom', '1403/06/01')
            ->assertSet('rankingDateTo', '1403/06/31');
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
