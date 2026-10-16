<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\MandatoryAnnouncementModal;
use App\Livewire\SocialWorkers\MandatoryAnnouncementsManager;
use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MandatoryAnnouncementsEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private SocialWorker $workerA;

    private User $workerUserA;

    private SocialWorker $workerB;

    private User $workerUserB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $this->workerA = SocialWorker::query()->create([
            'worker_code' => 9101,
            'first_name' => 'الهام',
            'last_name' => 'حسینی',
            'is_active' => true,
        ]);

        $this->workerUserA = User::factory()->create([
            'name' => 'الهام حسینی',
            'email' => 'e.hosseini@example.com',
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $this->workerA->id,
        ]);

        $this->workerB = SocialWorker::query()->create([
            'worker_code' => 9102,
            'first_name' => 'کامران',
            'last_name' => 'نجفی',
            'is_active' => true,
        ]);

        $this->workerUserB = User::factory()->create([
            'name' => 'کامران نجفی',
            'email' => 'k.najafi@example.com',
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $this->workerB->id,
        ]);
    }

    public function test_full_e2e_flow_from_admin_creation_to_worker_acknowledgment_and_audit(): void
    {
        // 1. Admin logs in and creates a mandatory announcement for all social workers
        $this->actingAs($this->adminUser);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', 'دستورالعمل جامع تحویل سبد کالا')
            ->set('body', 'کلیه مددکاران موظف به رعایت اولویت توزیع هستند.')
            ->set('priority', MandatoryAnnouncement::PRIORITY_URGENT)
            ->set('targetType', MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS)
            ->set('isBlocking', true)
            ->set('minReadingSeconds', 5)
            ->call('saveAnnouncement')
            ->assertHasNoErrors();

        $announcement = MandatoryAnnouncement::query()->where('title', 'دستورالعمل جامع تحویل سبد کالا')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals(2, $announcement->recipients()->count());

        // 2. Worker A logs in and visits the dashboard page
        $this->actingAs($this->workerUserA);

        $response = $this->get(route('social-worker.dashboard'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(MandatoryAnnouncementModal::class);

        // 3. Worker A's modal component loads the blocking announcement
        $modalTest = Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', true)
            ->assertSet('isBlocking', true)
            ->assertSet('announcementId', $announcement->id)
            ->assertSee('دستورالعمل جامع تحویل سبد کالا')
            ->assertSee('کلیه مددکاران موظف به رعایت اولویت توزیع هستند.')
            ->assertSee('بخشنامه فوری و الزامی');

        // 4. Worker A acknowledges the announcement
        $modalTest->call('acknowledge')
            ->assertDispatched('announcement-acknowledged', id: $announcement->id)
            ->assertSet('hasPending', false);

        // 5. Database verify acknowledgment data
        $recipientA = MandatoryAnnouncementRecipient::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $this->workerUserA->id)
            ->first();

        $this->assertNotNull($recipientA->read_at);

        // 6. Admin inspects audit log in admin panel
        $this->actingAs($this->adminUser);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->call('openAuditModal', $announcement->id)
            ->assertSet('showAuditModal', true)
            ->assertSee('الهام حسینی')
            ->assertSee('کامران نجفی')
            ->assertSee('تایید شده')
            ->assertSee('در انتظار');
    }

    public function test_multiple_blocking_announcements_are_queued_sequentially(): void
    {
        $this->actingAs($this->adminUser);

        $announcement1 = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'پیام الزامی اول',
            'body' => 'متن پیام اول',
            'priority' => MandatoryAnnouncement::PRIORITY_NORMAL,
            'is_blocking' => true,
            'min_reading_seconds' => 0,
        ]);

        $announcement2 = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'پیام الزامی دوم',
            'body' => 'متن پیام دوم',
            'priority' => MandatoryAnnouncement::PRIORITY_IMPORTANT,
            'is_blocking' => true,
            'min_reading_seconds' => 0,
        ]);

        MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement1->id,
            'user_id' => $this->workerUserA->id,
            'social_worker_id' => $this->workerA->id,
        ]);

        MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement2->id,
            'user_id' => $this->workerUserA->id,
            'social_worker_id' => $this->workerA->id,
        ]);

        // Worker A views modal
        $this->actingAs($this->workerUserA);

        $test = Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', true)
            ->assertSet('announcementId', $announcement1->id)
            ->assertSee('پیام الزامی اول');

        // Acknowledge first -> queue automatically loads the second
        $test->call('acknowledge')
            ->assertSet('hasPending', true)
            ->assertSet('announcementId', $announcement2->id)
            ->assertSee('پیام الزامی دوم');

        // Acknowledge second -> modal dismisses
        $test->call('acknowledge')
            ->assertSet('hasPending', false);

        // Verify both acknowledged in DB
        $unreadCount = MandatoryAnnouncementRecipient::query()
            ->where('user_id', $this->workerUserA->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    public function test_targeted_announcement_only_blocks_target_worker(): void
    {
        $this->actingAs($this->adminUser);

        // Send to Worker A only
        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', 'پیام محرمانه برای مددکار الف')
            ->set('body', 'این پیام فقط برای شما ارسال شده است.')
            ->set('priority', MandatoryAnnouncement::PRIORITY_IMPORTANT)
            ->set('targetType', MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS)
            ->set('selectedSocialWorkerIds', [$this->workerA->id])
            ->set('isBlocking', true)
            ->call('saveAnnouncement');

        // Worker B logs in -> NO pending blocking modal
        $this->actingAs($this->workerUserB);
        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', false);

        // Worker A logs in -> HAS pending blocking modal
        $this->actingAs($this->workerUserA);
        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', true)
            ->assertSee('پیام محرمانه برای مددکار الف');
    }

    public function test_non_blocking_announcements_do_not_halt_worker_flow(): void
    {
        $this->actingAs($this->adminUser);

        $announcement = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'اطلاعیه غیر مسدودکننده',
            'body' => 'یک پیام عمومی و اختیاری',
            'priority' => MandatoryAnnouncement::PRIORITY_NORMAL,
            'is_blocking' => false,
            'min_reading_seconds' => 0,
        ]);

        MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $this->workerUserA->id,
            'social_worker_id' => $this->workerA->id,
        ]);

        $this->actingAs($this->workerUserA);
        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', false);
    }

    public function test_social_worker_cannot_access_announcements_management_screen(): void
    {
        $this->actingAs($this->workerUserA);

        $this->get('/admin/social-workers/announcements')
            ->assertRedirect(route('social-worker.dashboard'));
    }
}
