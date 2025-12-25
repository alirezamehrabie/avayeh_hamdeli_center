<?php

namespace App\Observers;

use App\Models\Guardian;
use App\Models\SocialWorker;

class GuardianObserver
{
    /**
     * Handle the Guardian "created" event.
     */
    public function created(Guardian $guardian): void
    {
        //
    }

    /**
     * Handle the Guardian "updated" event.
     */
    public function updated(Guardian $guardian): void
    {
        // اگر social_worker_id تغییر کرده باشد
        if ($guardian->isDirty('social_worker_id')) {
            $oldSocialWorkerId = $guardian->getOriginal('social_worker_id');
            $newSocialWorkerId = $guardian->social_worker_id;

            // کاهش از مددکار قبلی
            if ($oldSocialWorkerId && $oldSocialWorker = SocialWorker::find($oldSocialWorkerId)) {
                $oldSocialWorker->decrementCoveredHouseholdsCount();
            }

            // افزایش به مددکار جدید
            if ($newSocialWorkerId && $guardian->socialWorker) {
                $guardian->socialWorker->incrementCoveredHouseholdsCount();
            }
        }
    }

    /**
     * Handle the Guardian "deleted" event.
     */
    public function deleted(Guardian $guardian): void
    {
        if ($guardian->socialWorker) {
            $guardian->socialWorker->decrementCoveredHouseholdsCount();
        }
    }

    /**
     * Handle the Guardian "restored" event.
     */
    public function restored(Guardian $guardian): void
    {
        //
    }

    /**
     * Handle the Guardian "force deleted" event.
     */
    public function forceDeleted(Guardian $guardian): void
    {
        //
    }
}
