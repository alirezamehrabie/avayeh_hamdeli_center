<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardHome;
use App\Livewire\SocialWorkers\MandatoryAnnouncementsManager;
use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MandatoryAnnouncementsManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function createSocialWorker(int $code, string $firstName, string $lastName): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);
    }

    public function test_guests_cannot_access_announcements_manager(): void
    {
        $this->get('/admin/social-workers/announcements')
            ->assertRedirect('/login');
    }

    public function test_admin_can_view_announcements_manager_via_dashboard_section(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::withQueryParams(['section' => 'social-worker-announcements'])
            ->test(DashboardHome::class)
            ->assertStatus(200)
            ->assertSeeLivewire(MandatoryAnnouncementsManager::class);
    }

    public function test_announcements_list_displays_correctly(): void
    {
        $this->actingAs($this->adminUser);

        $worker1 = $this->createSocialWorker(9001, 'مریم', 'کاظمی');
        $user1 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker1->id,
        ]);

        $announcement = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'بخشنامه سراسری مهم',
            'body' => 'متن آزمایشی بخشنامه مدیریت',
            'priority' => MandatoryAnnouncement::PRIORITY_URGENT,
            'target_type' => MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
            'is_blocking' => true,
            'min_reading_seconds' => 5,
        ]);

        MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user1->id,
            'social_worker_id' => $worker1->id,
        ]);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->assertStatus(200)
            ->assertSee('بخشنامه سراسری مهم')
            ->assertSee('بسیار فوری / اضطراری')
            ->assertSee('مسدودکننده پنل');
    }

    public function test_admin_can_create_announcement_for_all_social_workers(): void
    {
        $this->actingAs($this->adminUser);

        $worker1 = $this->createSocialWorker(9002, 'علی', 'احمدی');
        $user1 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker1->id,
        ]);

        $worker2 = $this->createSocialWorker(9003, 'رضا', 'محمدی');
        $user2 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker2->id,
        ]);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', 'دستورالعمل جدید ابلاغی')
            ->set('body', 'لطفا این متن را به دقت مطالعه فرمایید.')
            ->set('priority', MandatoryAnnouncement::PRIORITY_IMPORTANT)
            ->set('targetType', MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS)
            ->set('isBlocking', true)
            ->set('minReadingSeconds', 10)
            ->call('saveAnnouncement')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'list');

        $this->assertDatabaseHas('mandatory_announcements', [
            'title' => 'دستورالعمل جدید ابلاغی',
            'priority' => MandatoryAnnouncement::PRIORITY_IMPORTANT,
            'min_reading_seconds' => 10,
            'is_blocking' => true,
        ]);

        $announcement = MandatoryAnnouncement::query()->where('title', 'دستورالعمل جدید ابلاغی')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals(2, $announcement->recipients()->count());
    }

    public function test_admin_can_create_targeted_announcement_for_specific_social_workers(): void
    {
        $this->actingAs($this->adminUser);

        $worker1 = $this->createSocialWorker(9004, 'سارا', 'کریمی');
        $user1 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker1->id,
        ]);

        $worker2 = $this->createSocialWorker(9005, 'زهرا', 'نوری');
        $user2 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker2->id,
        ]);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', 'پیام اختصاصی مددکار اول')
            ->set('body', 'متن اختصاصی برای بررسی پرونده‌ها')
            ->set('priority', MandatoryAnnouncement::PRIORITY_NORMAL)
            ->set('targetType', MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS)
            ->set('selectedSocialWorkerIds', [$worker1->id])
            ->set('isBlocking', false)
            ->set('minReadingSeconds', 0)
            ->call('saveAnnouncement')
            ->assertHasNoErrors();

        $announcement = MandatoryAnnouncement::query()->where('title', 'پیام اختصاصی مددکار اول')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals(1, $announcement->recipients()->count());
        $this->assertEquals($user1->id, $announcement->recipients()->first()->user_id);
    }

    public function test_validation_fails_for_empty_fields(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', '')
            ->set('body', '')
            ->call('saveAnnouncement')
            ->assertHasErrors(['title', 'body']);
    }

    public function test_validation_fails_if_specific_workers_chosen_without_selection(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->set('activeTab', 'create')
            ->set('title', 'عنوان معتبر')
            ->set('body', 'متن معتبر و کامل بخشنامه')
            ->set('targetType', MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS)
            ->set('selectedSocialWorkerIds', [])
            ->call('saveAnnouncement')
            ->assertHasErrors(['selectedSocialWorkerIds']);
    }

    public function test_admin_can_open_audit_modal_and_view_read_details(): void
    {
        $this->actingAs($this->adminUser);

        $worker1 = $this->createSocialWorker(9006, 'سعید', 'رضایی');
        $user1 = User::factory()->create([
            'name' => 'سعید رضایی',
            'email' => 's.rezaei@example.com',
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker1->id,
        ]);

        $announcement = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'تست لاگ تاییدیه',
            'body' => 'متن تست برای مودال',
            'priority' => MandatoryAnnouncement::PRIORITY_NORMAL,
            'target_type' => MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
            'is_blocking' => true,
            'min_reading_seconds' => 0,
        ]);

        MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user1->id,
            'social_worker_id' => $worker1->id,
            'read_at' => now(),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->call('openAuditModal', $announcement->id)
            ->assertSet('showAuditModal', true)
            ->assertSee('سعید رضایی')
            ->assertSee('192.168.1.100')
            ->assertSee('گزارش و ردپای مشاهده (Audit Log)');
    }

    public function test_admin_can_delete_announcement(): void
    {
        $this->actingAs($this->adminUser);

        $announcement = MandatoryAnnouncement::query()->create([
            'created_by' => $this->adminUser->id,
            'title' => 'بخشنامه جهت حذف',
            'body' => 'متن آزمایشی برای تست حذف',
            'priority' => MandatoryAnnouncement::PRIORITY_NORMAL,
            'target_type' => MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
            'is_blocking' => true,
            'min_reading_seconds' => 0,
        ]);

        Livewire::test(MandatoryAnnouncementsManager::class)
            ->call('deleteAnnouncement', $announcement->id)
            ->assertStatus(200);

        $this->assertSoftDeleted('mandatory_announcements', ['id' => $announcement->id]);
    }
}
