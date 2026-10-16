<?php

namespace Tests\Feature;

use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MandatoryAnnouncementModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_and_recipients_relationships_and_scopes(): void
    {
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $socialWorker = SocialWorker::query()->create([
            'worker_code' => 991001,
            'first_name' => 'Maryam',
            'last_name' => 'Rad',
        ]);

        $workerUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'social_worker_id' => $socialWorker->id,
        ]);

        $announcement = MandatoryAnnouncement::query()->create([
            'created_by' => $admin->id,
            'title' => 'جلسه فوری مددکاران',
            'body' => 'حضور کلیه مددکاران در جلسه فردا الزامی است.',
            'priority' => MandatoryAnnouncement::PRIORITY_URGENT,
            'target_type' => MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
            'is_blocking' => true,
            'min_reading_seconds' => 3,
        ]);

        $recipient = MandatoryAnnouncementRecipient::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $workerUser->id,
            'social_worker_id' => $socialWorker->id,
        ]);

        $this->assertTrue($announcement->creator->is($admin));
        $this->assertCount(1, $announcement->recipients);
        $this->assertCount(1, $announcement->unreadRecipients);
        $this->assertCount(0, $announcement->readRecipients);

        $this->assertSame('بسیار فوری / اضطراری', $announcement->priorityLabel());
        $this->assertFalse($recipient->isRead());

        // Check scopes
        $this->assertTrue(
            MandatoryAnnouncementRecipient::query()
                ->unread()
                ->forUser($workerUser)
                ->blocking()
                ->exists()
        );

        // Mark as read
        $recipient->markAsRead(ip: '127.0.0.1', userAgent: 'PHPUnit Browser');
        $recipient->refresh();

        $this->assertTrue($recipient->isRead());
        $this->assertNotNull($recipient->read_at);
        $this->assertSame('127.0.0.1', $recipient->ip_address);
        $this->assertSame('PHPUnit Browser', $recipient->user_agent);

        $this->assertCount(0, $announcement->fresh()->unreadRecipients);
        $this->assertCount(1, $announcement->fresh()->readRecipients);
        $this->assertFalse(
            MandatoryAnnouncementRecipient::query()
                ->unread()
                ->forUser($workerUser)
                ->exists()
        );
    }
}
