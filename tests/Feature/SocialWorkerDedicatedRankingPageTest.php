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

    public function test_guardians_relation_is_lazy_loaded_only_on_profile_tab_and_limited(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9411, 'Sina');

        // ثبت ۷۰ خانوار برای مددکار
        for ($i = 1; $i <= 70; $i++) {
            \App\Models\Guardian::query()->create([
                'social_worker_id' => $worker->id,
                'first_name' => "Family {$i}",
                'last_name' => 'Guardian',
                'guardian_code' => 5000 + $i,
            ]);
        }

        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->call('showWorkerInfo', $worker->id);

        // در تب پیش‌فرض (performance)، رابطه guardians نباید لود شده باشد
        $selectedWorker = $component->instance()->selectedWorker;
        $this->assertNotNull($selectedWorker);
        $this->assertFalse($selectedWorker->relationLoaded('guardians'));
        $this->assertSame(70, (int) $selectedWorker->guardians_count);

        // با سوئیچ به تب profile، رابطه فقط تا سقف ۶۰ رکورد لود می‌شود
        $component->call('setWorkerModalTab', 'profile');
        $selectedWorkerProfile = $component->instance()->selectedWorker;
        $this->assertNotNull($selectedWorkerProfile);
        $this->assertTrue($selectedWorkerProfile->relationLoaded('guardians'));
        $this->assertCount(60, $selectedWorkerProfile->guardians);
        $this->assertSame(70, (int) $selectedWorkerProfile->guardians_count);
    }

    public function test_url_query_string_syncs_with_month_year_and_search(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        // ۱. بررسی مقداردهی اولیه از کوئری‌استرینگ URL
        Livewire::withQueryParams([
            'year' => 1403,
            'month' => 7,
            'q' => 'Mohsen',
            'status' => 'active',
        ])
            ->test(SocialWorkerRanking::class)
            ->assertSet('rankingYear', 1403)
            ->assertSet('rankingMonth', 7)
            ->assertSet('search', 'Mohsen')
            ->assertSet('status', 'active')
            ->assertSet('rankingDateFrom', '1403/07/01')
            ->assertSet('rankingDateTo', '1403/07/30');

        // ۲. بررسی به‌روزرسانی پارامترهای URL با تغییر مقادیر در کامپوننت
        $component = Livewire::test(SocialWorkerRanking::class)
            ->set('search', 'Ali')
            ->set('rankingYear', 1402)
            ->set('rankingMonth', 11);

        $this->assertSame('Ali', $component->get('search'));
        $this->assertSame(1402, $component->get('rankingYear'));
        $this->assertSame(11, $component->get('rankingMonth'));
    }

    public function test_spa_back_button_and_grid_loading_indicators_are_present(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $response = $this->get(route('admin.social-workers.ranking'));
        $response->assertOk();

        // بررسی دکمه بازگشت SPA بدون ریلود کامل مرورگر
        $response->assertSee('wire:click="$dispatch(\'open-dashboard-section\', { section: \'advanced-social-worker-report\' })"', false);
        $response->assertSee('بازگشت به گزارش پیشرفته');

        // بررسی نشانگر بارگذاری و کلاس غیرفعال‌سازی موقت کارت‌ها
        $response->assertSee('در حال به‌روزرسانی نتایج و شاخص‌های ارزیابی عملکرد...');
        $response->assertSee('wire:loading.class="opacity-50 pointer-events-none"', false);
    }

    public function test_header_statistics_distinguishes_active_from_total_and_shows_truncation_warning(): void
    {
        $manager = $this->managerUser();
        // ۱ مددکار فعال با فعالیت
        $activeWorker = $this->worker(9420, 'Active');
        [$service, $category] = $this->serviceWithCategory($manager);
        $assignedAt = Carbon::parse('2026-08-01 09:00:00');
        $this->allocate($service, $category, $activeWorker, 10, $assignedAt);
        $this->delivery($manager, $service, $category, $activeWorker, 10, '2026-08-01', $assignedAt->copy()->addHour());

        // ۱ مددکار بدون فعالیت
        $this->worker(9421, 'Idle');

        $this->actingAs($manager);

        $response = $this->get(route('admin.social-workers.ranking'));
        $response->assertOk();

        // تفکیک آمار هدر
        $response->assertSee('دارای فعالیت:');
        $response->assertSee('کل مددکاران:');
        $response->assertSee('میانگین امتیاز:');

        // ایجاد مددکاران فراتر از سقف ۱۰۰ نفر برای بررسی هشدار سقف رتبه‌بندی
        for ($i = 1; $i <= 101; $i++) {
            $this->worker(9500 + $i, "Bulk{$i}");
        }

        $component = Livewire::test(SocialWorkerRanking::class);
        $ranking = $component->instance()->ranking();

        $this->assertTrue($ranking['truncated']);
        $this->assertCount(100, $ranking['rows']);
        $component->assertSee('مددکار برتر نمایش داده شده‌اند');
    }

    public function test_mode_toggle_between_monthly_and_custom_range(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->assertSet('rankingMode', 'monthly')
            ->call('setRankingMode', 'custom')
            ->assertSet('rankingMode', 'custom');

        // بازگشت با تغییر ماه به حالت monthly
        $component->call('selectRankingMonth', 5)
            ->assertSet('rankingMode', 'monthly')
            ->assertSet('rankingMonth', 5);

        // تغییر تاریخ دستی باید حالت را به custom ببرد
        $component->set('rankingDateFrom', '1404/01/01')
            ->assertSet('rankingMode', 'custom');
    }

    public function test_custom_range_auto_swaps_dates_when_from_is_after_to(): void
    {
        $manager = $this->managerUser();
        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->call('setRankingMode', 'custom')
            ->set('rankingDateFrom', '1404/05/10')
            ->set('rankingDateTo', '1404/02/01');

        $range = $component->instance()->rankingRange();
        $this->assertNotNull($range);
        $from = $range['from'];
        $to = $range['to'];

        // تاریخ کوچکتر باید به عنوان $from و بزرگتر $to قرار گیرد
        $this->assertTrue($from->lte($to));
    }

    public function test_custom_range_averages_multi_month_management_scores(): void
    {
        $manager = $this->managerUser();
        $worker = $this->worker(9430, 'AverageTest');

        // ثبت ضریب ماه ۱: 8
        \App\Models\SocialWorkerManagementScore::create([
            'social_worker_id' => $worker->id,
            'year' => 1404,
            'month' => 1,
            'score' => 8,
            'updated_by' => $manager->id,
        ]);

        // ثبت ضریب ماه ۲: 6
        \App\Models\SocialWorkerManagementScore::create([
            'social_worker_id' => $worker->id,
            'year' => 1404,
            'month' => 2,
            'score' => 6,
            'updated_by' => $manager->id,
        ]);

        // ماه ۳ ثبت نشده است (پیش‌فرض: 10)
        // میانگین سه ماه: (8 + 6 + 10) / 3 = 24 / 3 = 8.0

        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class)
            ->call('setRankingMode', 'custom')
            ->set('rankingDateFrom', '1404/01/01')
            ->set('rankingDateTo', '1404/03/31');

        $ranking = $component->instance()->ranking();
        $workerRow = collect($ranking['rows'])->firstWhere('id', $worker->id);

        $this->assertNotNull($workerRow);
        $this->assertEquals(8.0, $workerRow['management_score']);

        // ثبت ضریب در حالت سفارشی باید خطا بدهد
        $component->call('updateManagementScore', $worker->id, 9)
            ->assertSet('rankingErrorMessage', 'تغییر ضریب ارزیابی مدیریت فقط در حالت ارزیابی ماهانه امکان‌پذیر است.');
    }

    public function test_successive_modal_openings_and_tab_state_isolation(): void
    {
        $manager = $this->managerUser();
        $workerA = $this->worker(9440, 'First');
        $workerB = $this->worker(9441, 'Second');

        $this->actingAs($manager);

        $component = Livewire::test(SocialWorkerRanking::class);

        // ۱. باز کردن مودال مددکار اول
        $component->call('showWorkerInfo', $workerA->id)
            ->assertSet('selectedWorkerId', $workerA->id)
            ->assertSet('showWorkerModal', true)
            ->assertSet('workerModalTab', 'performance')
            ->assertSee('First Worker');

        // ۲. تغییر تب به مشخصات و پرونده (profile)
        $component->call('setWorkerModalTab', 'profile')
            ->assertSet('workerModalTab', 'profile')
            ->assertSee('اطلاعات هویتی');

        // ۳. بستن مودال - استیت و تب باید به حالت اولیه ریست شوند
        $component->call('closeWorkerModal')
            ->assertSet('selectedWorkerId', null)
            ->assertSet('showWorkerModal', false)
            ->assertSet('workerModalTab', 'performance');

        // ۴. باز کردن مودال مددکار دوم - باید تب به عملکرد برگردد و اطلاعات مددکار دوم انتخاب شود
        $component->call('showWorkerInfo', $workerB->id)
            ->assertSet('selectedWorkerId', $workerB->id)
            ->assertSet('showWorkerModal', true)
            ->assertSet('workerModalTab', 'performance');

        $this->assertSame($workerB->id, $component->instance()->selectedWorker->id);

        // ۵. تغییر مجدد به تب پرونده برای مددکار دوم
        $component->call('setWorkerModalTab', 'profile')
            ->assertSet('workerModalTab', 'profile')
            ->assertSee('اطلاعات هویتی');
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
