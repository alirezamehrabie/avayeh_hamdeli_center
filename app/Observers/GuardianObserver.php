<?php

namespace App\Observers;

use App\Models\Guardian;
use App\Models\ServiceDelivery;
use App\Models\SocialWorker;

class GuardianObserver
{
    /**
     * Handle the Guardian "created" event.
     */
    public function created(Guardian $guardian): void
    {
        ServiceDelivery::attachToGuardian($guardian);
    }

    /**
     * Handle the Guardian "updated" event.
     */
    public function updated(Guardian $guardian): void
    {
        if ($guardian->wasChanged('national_code')) {
            ServiceDelivery::attachToGuardian($guardian);
        }
    }

    public function saved(Guardian $guardian)
    {
        // اگر مددکار تغییر کرده باشد، آمار مددکار قبلی و جدید هر دو بروز شود
        if ($guardian->isDirty('social_worker_id')) {
            $oldWorkerId = $guardian->getOriginal('social_worker_id');
            if ($oldWorkerId) {
                SocialWorker::find($oldWorkerId)?->updateStatistics();
            }
        }

        $guardian->socialWorker?->updateStatistics();
    }

    /**
     * Handle the Guardian "deleted" event.
     */
    public function deleted(Guardian $guardian): void
    {
        $guardian->socialWorker?->updateStatistics();
    }

    /**
     * Handle the Guardian "restored" event.
     */
    public function restored(Guardian $guardian): void
    {
        $guardian->socialWorker?->updateStatistics();
    }

    /**
     * Handle the Guardian "force deleted" event.
     */
    public function forceDeleted(Guardian $guardian): void
    {
        //
    }
}
