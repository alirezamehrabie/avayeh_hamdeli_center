<?php

namespace App\Queries\Announcements;

use App\Models\MandatoryAnnouncementRecipient;
use App\Models\User;
use Illuminate\Support\Collection;

class PendingMandatoryAnnouncementQuery
{
    public function getFirstBlockingForUser(int|User $user): ?MandatoryAnnouncementRecipient
    {
        return MandatoryAnnouncementRecipient::query()
            ->forUser($user)
            ->unread()
            ->blocking()
            ->with(['announcement.creator'])
            ->oldest()
            ->first();
    }

    public function hasBlockingForUser(int|User $user): bool
    {
        return MandatoryAnnouncementRecipient::query()
            ->forUser($user)
            ->unread()
            ->blocking()
            ->exists();
    }

    /**
     * @return Collection<int, MandatoryAnnouncementRecipient>
     */
    public function getAllPendingForUser(int|User $user): Collection
    {
        return MandatoryAnnouncementRecipient::query()
            ->forUser($user)
            ->unread()
            ->with(['announcement.creator'])
            ->oldest()
            ->get();
    }
}
