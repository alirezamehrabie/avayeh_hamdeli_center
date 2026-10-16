<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\MandatoryAnnouncementModal;
use App\Models\MandatoryAnnouncement;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\Announcements\CreateMandatoryAnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MandatoryAnnouncementModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_renders_blocking_overlay_when_worker_has_pending_announcement(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $worker = SocialWorker::query()->create([
            'worker_code' => 993001,
            'first_name' => 'Fatemeh',
            'last_name' => 'Mohammadi',
            'is_active' => true,
        ]);

        $workerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker->id,
        ]);

        app(CreateMandatoryAnnouncementService::class)->create([
            'title' => 'جلسه توجیهی پروتکل‌های جدید',
            'body' => 'حضور در این جلسه برای کلیه مددکاران گرامی الزامی است.',
            'priority' => MandatoryAnnouncement::PRIORITY_URGENT,
            'is_blocking' => true,
            'min_reading_seconds' => 4,
        ], $admin);

        $this->actingAs($workerUser);

        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', true)
            ->assertSet('title', 'جلسه توجیهی پروتکل‌های جدید')
            ->assertSet('priority', MandatoryAnnouncement::PRIORITY_URGENT)
            ->assertSet('minReadingSeconds', 4)
            ->assertSee('جلسه توجیهی پروتکل‌های جدید')
            ->assertSee('بخشنامه فوری و الزامی')
            ->assertSee('حضور در این جلسه برای کلیه مددکاران گرامی الزامی است.');
    }

    public function test_modal_does_not_render_when_worker_has_no_pending_announcement(): void
    {
        $worker = SocialWorker::query()->create([
            'worker_code' => 993002,
            'first_name' => 'Mina',
            'last_name' => 'Karimi',
            'is_active' => true,
        ]);

        $workerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker->id,
        ]);

        $this->actingAs($workerUser);

        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', false)
            ->assertDontSee('بخشنامه فوری و الزامی')
            ->assertDontSee('اطلاعیه مهم مدیریت مرکز');
    }

    public function test_worker_can_acknowledge_announcement_and_dismiss_modal(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $worker = SocialWorker::query()->create([
            'worker_code' => 993003,
            'first_name' => 'Saeed',
            'last_name' => 'Saeedi',
            'is_active' => true,
        ]);

        $workerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker->id,
        ]);

        $announcement = app(CreateMandatoryAnnouncementService::class)->create([
            'title' => 'تغییر ساعات کار مرکز',
            'body' => 'از شنبه هفته آینده ساعات کاری از ۸ صبح تا ۱۵ بعد از ظهر خواهد بود.',
            'priority' => MandatoryAnnouncement::PRIORITY_IMPORTANT,
            'is_blocking' => true,
        ], $admin);

        $this->actingAs($workerUser);

        Livewire::test(MandatoryAnnouncementModal::class)
            ->assertSet('hasPending', true)
            ->call('acknowledge')
            ->assertDispatched('announcement-acknowledged', id: $announcement->id)
            ->assertSet('hasPending', false);

        $recipient = $announcement->recipients()->where('user_id', $workerUser->id)->first();
        $this->assertNotNull($recipient);
        $this->assertTrue($recipient->isRead());
        $this->assertNotNull($recipient->read_at);
    }
}
