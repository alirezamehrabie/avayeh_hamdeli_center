<?php

namespace App\Observers;

use App\Models\Person;
use App\Models\Guardian;

class PersonObserver
{
    /**
     * Handle the Person "created" event.
     */
    public function created(Person $person): void
    {
        //
    }

    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void
    {
        //
    }

    public function saved(Person $person)
    {
        $person->guardian?->refreshChildrenInHouse();

        if ($person->wasChanged('guardian_id')) {
            $oldGuardianId = $person->getOriginal('guardian_id');
            Guardian::find($oldGuardianId)?->refreshChildrenInHouse();
        }

        // بروزرسانی آمار مددکارِ مربوط به سرپرست فعلی
        $person->guardian?->socialWorker?->updateStatistics();

        // اگر سرپرست مددجو تغییر کرده باشد، آمار مددکارِ سرپرست قبلی هم باید بروز شود
        if ($person->wasChanged('guardian_id')) {
            $oldGuardianId = $person->getOriginal('guardian_id');
            $oldGuardian = Guardian::find($oldGuardianId);
            $oldGuardian?->socialWorker?->updateStatistics();
        }
    }

    /**
     * Handle the Person "deleted" event.
     */
    public function deleted(Person $person): void
    {
        $person->guardian?->refreshChildrenInHouse();
        $person->guardian?->socialWorker?->updateStatistics();
    }

    /**
     * Handle the Person "restored" event.
     */
    public function restored(Person $person): void
    {
        $person->guardian?->refreshChildrenInHouse();
        $person->guardian?->socialWorker?->updateStatistics();
    }

    /**
     * Handle the Person "force deleted" event.
     */
    public function forceDeleted(Person $person): void
    {
        //
    }
}
