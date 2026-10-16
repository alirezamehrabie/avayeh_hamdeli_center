<?php

namespace App\Services\Announcements;

use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\User;

class AcknowledgeMandatoryAnnouncementService
{
    public function acknowledge(
        MandatoryAnnouncement|int $announcement,
        User|int $user,
        ?string $ip = null,
        ?string $userAgent = null
    ): ?MandatoryAnnouncementRecipient {
        $announcementId = $announcement instanceof MandatoryAnnouncement ? $announcement->id : $announcement;
        $userId = $user instanceof User ? $user->id : $user;

        $recipient = MandatoryAnnouncementRecipient::query()
            ->where('announcement_id', $announcementId)
            ->where('user_id', $userId)
            ->first();

        if (! $recipient) {
            return null;
        }

        $recipient->markAsRead(ip: $ip, userAgent: $userAgent);

        return $recipient;
    }
}
