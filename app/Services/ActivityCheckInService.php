<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityCheckInService
{
    public function checkInByQr(Activity $activity, string $payload, User $operator): ActivityCheckInResult
    {
        $token = $this->extractQrToken($payload);

        if (! $token) {
            return new ActivityCheckInResult(false, 'invalid_qr', 'QR خوانده‌شده معتبر نیست.', $activity);
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'activity-check-in');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity) {
            return new ActivityCheckInResult(false, 'invalid_qr', 'QR نامعتبر، غیرفعال یا ابطال‌شده است.', $activity);
        }

        if ($identity->subject_type !== QrIdentity::SUBJECT_PERSON) {
            return new ActivityCheckInResult(false, 'not_beneficiary', 'این QR متعلق به مددجو نیست.', $activity);
        }

        $person = $identity->subject instanceof Person
            ? $identity->subject
            : Person::query()->find((int) $identity->subject_id);

        return $this->checkInPerson($activity, $person, $operator, 'qr', $identity);
    }

    public function checkInPerson(Activity $activity, ?Person $person, User $operator, string $method = 'manual', ?QrIdentity $qrIdentity = null): ActivityCheckInResult
    {
        if (! $person || (method_exists($person, 'trashed') && $person->trashed())) {
            return new ActivityCheckInResult(false, 'beneficiary_unavailable', 'مددجوی انتخاب‌شده در دسترس نیست.', $activity);
        }

        return DB::transaction(function () use ($activity, $person, $operator, $method, $qrIdentity): ActivityCheckInResult {
            $lockedActivity = Activity::query()
                ->whereKey($activity->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedActivity) {
                return new ActivityCheckInResult(false, 'activity_unavailable', 'فعالیت پیدا نشد.');
            }

            if ($lockedActivity->status !== 'ongoing') {
                return new ActivityCheckInResult(false, 'activity_not_active', 'ثبت حضور فقط برای فعالیت آماده برگزاری مجاز است.', $lockedActivity, $person);
            }

            $existingAttendance = ActivityAttendance::query()
                ->where('activity_id', $lockedActivity->id)
                ->where('person_id', $person->id)
                ->first();

            if ($existingAttendance) {
                return new ActivityCheckInResult(true, 'duplicate', 'حضور این مددجو قبلاً ثبت شده است.', $lockedActivity, $person, $existingAttendance);
            }

            if ($lockedActivity->capacity !== null) {
                $checkedInCount = ActivityAttendance::query()
                    ->where('activity_id', $lockedActivity->id)
                    ->where('status', 'present')
                    ->count();

                if ($checkedInCount >= (int) $lockedActivity->capacity) {
                    return new ActivityCheckInResult(false, 'capacity_full', 'ظرفیت فعالیت تکمیل شده است.', $lockedActivity, $person);
                }
            }

            try {
                $attendance = ActivityAttendance::query()->create([
                    'activity_id' => $lockedActivity->id,
                    'person_id' => $person->id,
                    'qr_identity_id' => $qrIdentity?->id,
                    'status' => 'present',
                    'registration_method' => $method,
                    'checked_in_at' => now(),
                    'recorded_by' => $operator->id,
                ]);
            } catch (QueryException) {
                $attendance = ActivityAttendance::query()
                    ->where('activity_id', $lockedActivity->id)
                    ->where('person_id', $person->id)
                    ->first();

                return new ActivityCheckInResult(true, 'duplicate', 'حضور این مددجو قبلاً ثبت شده است.', $lockedActivity, $person, $attendance);
            }

            return new ActivityCheckInResult(true, 'checked_in', 'حضور مددجو با موفقیت ثبت شد.', $lockedActivity, $person, $attendance);
        });
    }

    private function resolvePublicCode(string $token): ?QrIdentity
    {
        $identity = QrIdentity::query()
            ->where('public_code', strtoupper(trim($token)))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();

        if (! $identity) {
            return null;
        }

        $subject = $identity->subject;

        if (! $subject || method_exists($subject, 'trashed') && $subject->trashed()) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity->setRelation('subject', $subject);
    }

    private function extractQrToken(string $payload): ?string
    {
        $value = trim($payload);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\/qr\/r\/([^\/?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }
}
