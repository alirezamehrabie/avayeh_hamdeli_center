<?php

namespace Tests\Feature;

use App\Models\MandatoryAnnouncement;
use App\Models\SocialWorker;
use App\Models\User;
use App\Queries\Announcements\PendingMandatoryAnnouncementQuery;
use App\Services\Announcements\AcknowledgeMandatoryAnnouncementService;
use App\Services\Announcements\CreateMandatoryAnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MandatoryAnnouncementServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_announcement_for_all_active_social_workers(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $worker1 = SocialWorker::query()->create([
            'worker_code' => 992001,
            'first_name' => 'Ali',
            'last_name' => 'Alavi',
            'is_active' => true,
        ]);
        $user1 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker1->id,
        ]);

        $worker2 = SocialWorker::query()->create([
            'worker_code' => 992002,
            'first_name' => 'Reza',
            'last_name' => 'Razavi',
            'is_active' => true,
        ]);
        $user2 = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker2->id,
        ]);

        $service = app(CreateMandatoryAnnouncementService::class);
        $announcement = $service->create([
            'title' => 'دستورالعمل تحویل سبد کالا',
            'body' => 'لطفاً پیش از تحویل اقلام، کارت شناسایی سرپرست را اسکن نمایید.',
            'priority' => MandatoryAnnouncement::PRIORITY_URGENT,
            'target_type' => MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
            'is_blocking' => true,
            'min_reading_seconds' => 5,
        ], $admin);

        $this->assertSame(2, $announcement->recipients()->count());

        $pendingQuery = app(PendingMandatoryAnnouncementQuery::class);
        $this->assertTrue($pendingQuery->hasBlockingForUser($user1));
        $this->assertTrue($pendingQuery->hasBlockingForUser($user2));

        $pending1 = $pendingQuery->getFirstBlockingForUser($user1);
        $this->assertNotNull($pending1);
        $this->assertSame($announcement->id, $pending1->announcement_id);
        $this->assertSame('دستورالعمل تحویل سبد کالا', $pending1->announcement->title);
    }

    public function test_creates_announcement_for_specific_social_workers_only(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $workerA = SocialWorker::query()->create([
            'worker_code' => 992003,
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
            'is_active' => true,
        ]);
        $userA = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $workerA->id,
        ]);

        $workerB = SocialWorker::query()->create([
            'worker_code' => 992004,
            'first_name' => 'Neda',
            'last_name' => 'Bahrani',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $workerB->id,
        ]);

        $service = app(CreateMandatoryAnnouncementService::class);
        $announcement = $service->create([
            'title' => 'پیام اختصاصی به سارا',
            'body' => 'گزارش پرونده شماره ۱۲ را بررسی کنید.',
            'target_type' => MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS,
            'social_worker_ids' => [$workerA->id],
            'is_blocking' => true,
        ], $admin);

        $this->assertSame(1, $announcement->recipients()->count());

        $pendingQuery = app(PendingMandatoryAnnouncementQuery::class);
        $this->assertTrue($pendingQuery->hasBlockingForUser($userA));
        $this->assertFalse($pendingQuery->hasBlockingForUser($userB));
    }

    public function test_acknowledging_announcement_marks_as_read_and_unblocks_worker(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $worker = SocialWorker::query()->create([
            'worker_code' => 992005,
            'first_name' => 'Hossein',
            'last_name' => 'Hosseini',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $worker->id,
        ]);

        $createService = app(CreateMandatoryAnnouncementService::class);
        $announcement = $createService->create([
            'title' => 'بخشنامه نهایی',
            'body' => 'متن بخشنامه نهایی مدیریت',
            'is_blocking' => true,
        ], $admin);

        $pendingQuery = app(PendingMandatoryAnnouncementQuery::class);
        $this->assertTrue($pendingQuery->hasBlockingForUser($user));

        $ackService = app(AcknowledgeMandatoryAnnouncementService::class);
        $recipient = $ackService->acknowledge($announcement, $user, '192.168.1.100', 'CustomAgent/1.0');

        $this->assertNotNull($recipient);
        $this->assertTrue($recipient->isRead());
        $this->assertSame('192.168.1.100', $recipient->ip_address);
        $this->assertSame('CustomAgent/1.0', $recipient->user_agent);

        // Worker should no longer be blocked
        $this->assertFalse($pendingQuery->hasBlockingForUser($user));
        $this->assertNull($pendingQuery->getFirstBlockingForUser($user));
    }
}
