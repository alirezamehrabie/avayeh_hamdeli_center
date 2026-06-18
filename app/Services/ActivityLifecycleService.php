<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActivityLifecycleService
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['ongoing', 'cancelled'],
        'ongoing' => ['closed', 'cancelled'],
        'closed' => ['cancelled'],
        'cancelled' => [],
    ];

    public function transition(Activity $activity, string $targetStatus, ?User $actor = null, ?string $notes = null): Activity
    {
        if (! array_key_exists($targetStatus, Activity::STATUS_OPTIONS)) {
            throw ValidationException::withMessages([
                'status' => 'وضعیت انتخاب‌شده معتبر نیست.',
            ]);
        }

        $currentStatus = (string) $activity->status;

        if ($currentStatus === $targetStatus) {
            return $activity;
        }

        if (! in_array($targetStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'تغییر وضعیت انتخاب‌شده برای این فعالیت مجاز نیست.',
            ]);
        }

        $this->assertTransitionRequirements($activity, $targetStatus);

        $activity->forceFill([
            'status' => $targetStatus,
            'status_notes' => filled($notes) ? trim((string) $notes) : $activity->status_notes,
        ])->save();

        return $activity->refresh();
    }

    /**
     * @return list<string>
     */
    public function allowedTargets(Activity $activity): array
    {
        return self::ALLOWED_TRANSITIONS[(string) $activity->status] ?? [];
    }

    private function assertTransitionRequirements(Activity $activity, string $targetStatus): void
    {
        if ($targetStatus === 'ongoing' && blank($activity->starts_at)) {
            throw ValidationException::withMessages([
                'starts_at' => 'برای شروع فعالیت، ثبت زمان شروع الزامی است.',
            ]);
        }

        if ($targetStatus === 'closed' && $activity->status !== 'ongoing') {
            throw ValidationException::withMessages([
                'status' => 'فقط فعالیت آماده برگزاری قابل بستن است.',
            ]);
        }

        if ($activity->status === 'closed' && $targetStatus === 'cancelled' && $activity->attendances()->exists()) {
            throw ValidationException::withMessages([
                'status' => 'فعالیت بسته‌شده دارای حضور و غیاب قابل لغو نیست.',
            ]);
        }
    }
}
