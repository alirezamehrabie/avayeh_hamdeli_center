<?php

namespace App\Services;

use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AttendanceSheetService
{
    public function checkInByQr(AttendanceSheet $sheet, string $payload, User $operator): AttendanceSheetResult
    {
        $resolution = $this->resolvePerson($sheet, $payload, 'social-worker-attendance-in');

        if ($resolution instanceof AttendanceSheetResult) {
            return $resolution;
        }

        [$person, $identity] = $resolution;

        return $this->checkInPerson($sheet, $person, $operator, 'qr', $identity);
    }

    public function checkOutByQr(AttendanceSheet $sheet, string $payload, User $operator): AttendanceSheetResult
    {
        $resolution = $this->resolvePerson($sheet, $payload, 'social-worker-attendance-out');

        if ($resolution instanceof AttendanceSheetResult) {
            return $resolution;
        }

        [$person] = $resolution;

        return $this->checkOutPerson($sheet, $person, $operator, 'qr');
    }

    public function checkInPerson(
        AttendanceSheet $sheet,
        ?Person $person,
        User $operator,
        string $method = 'manual',
        ?QrIdentity $qrIdentity = null
    ): AttendanceSheetResult {
        if (! $person || $person->trashed()) {
            return new AttendanceSheetResult(false, 'beneficiary_unavailable', 'اطلاعات مددجو در دسترس نیست.', $sheet);
        }

        if (! $this->belongsToSheetOwner($sheet, $person)) {
            return new AttendanceSheetResult(false, 'not_own_beneficiary', 'این مددجو در فهرست مددجویان شما نیست.', $sheet, $person);
        }

        return DB::transaction(function () use ($sheet, $person, $operator, $method, $qrIdentity): AttendanceSheetResult {
            $lockedSheet = AttendanceSheet::query()->whereKey($sheet->id)->lockForUpdate()->first();

            if (! $lockedSheet) {
                return new AttendanceSheetResult(false, 'sheet_unavailable', 'این حضور و غیاب پیدا نشد.');
            }

            $existing = AttendanceSheetEntry::query()
                ->where('attendance_sheet_id', $lockedSheet->id)
                ->where('person_id', $person->id)
                ->first();

            if ($existing) {
                return new AttendanceSheetResult(true, 'duplicate', 'ورود این مددجو قبلاً ثبت شده است.', $lockedSheet, $person, $existing);
            }

            try {
                $entry = AttendanceSheetEntry::query()->create(
                    $this->entryAttributes($lockedSheet, $person, $operator, $method, $qrIdentity)
                );
            } catch (QueryException) {
                $entry = AttendanceSheetEntry::query()
                    ->where('attendance_sheet_id', $lockedSheet->id)
                    ->where('person_id', $person->id)
                    ->first();

                return new AttendanceSheetResult(true, 'duplicate', 'ورود این مددجو قبلاً ثبت شده است.', $lockedSheet, $person, $entry);
            }

            return new AttendanceSheetResult(true, 'checked_in', 'ورود ثبت شد.', $lockedSheet, $person, $entry);
        });
    }

    public function checkOutPerson(
        AttendanceSheet $sheet,
        ?Person $person,
        User $operator,
        string $method = 'manual'
    ): AttendanceSheetResult {
        if (! $person || $person->trashed()) {
            return new AttendanceSheetResult(false, 'beneficiary_unavailable', 'اطلاعات مددجو در دسترس نیست.', $sheet);
        }

        if (! $this->belongsToSheetOwner($sheet, $person)) {
            return new AttendanceSheetResult(false, 'not_own_beneficiary', 'این مددجو در فهرست مددجویان شما نیست.', $sheet, $person);
        }

        return DB::transaction(function () use ($sheet, $person, $operator, $method): AttendanceSheetResult {
            $lockedSheet = AttendanceSheet::query()->whereKey($sheet->id)->lockForUpdate()->first();

            if (! $lockedSheet) {
                return new AttendanceSheetResult(false, 'sheet_unavailable', 'این حضور و غیاب پیدا نشد.');
            }

            $entry = AttendanceSheetEntry::query()
                ->where('attendance_sheet_id', $lockedSheet->id)
                ->where('person_id', $person->id)
                ->lockForUpdate()
                ->first();

            if (! $entry || ! $entry->checked_in_at) {
                return new AttendanceSheetResult(false, 'not_checked_in', 'ورود این مددجو در این حضور و غیاب ثبت نشده است.', $lockedSheet, $person, $entry);
            }

            if ($entry->checked_out_at) {
                return new AttendanceSheetResult(true, 'already_checked_out', 'خروج این مددجو قبلاً ثبت شده است.', $lockedSheet, $person, $entry);
            }

            $entry->forceFill([
                'checked_out_at' => now(),
                'check_out_method' => $method,
                'checked_out_by' => $operator->id,
            ])->save();

            return new AttendanceSheetResult(true, 'checked_out', 'خروج ثبت شد.', $lockedSheet, $person, $entry);
        });
    }

    /**
     * @return array{0: Person, 1: ?QrIdentity}|AttendanceSheetResult
     */
    private function resolvePerson(AttendanceSheet $sheet, string $payload, string $context): array|AttendanceSheetResult
    {
        $qrService = app(QrIdentityService::class);
        $token = $qrService->extractToken($payload);

        if (! $token) {
            return new AttendanceSheetResult(false, 'invalid_qr', 'کد QR خوانده‌شده معتبر نیست.', $sheet);
        }

        $identity = $qrService->resolveToken($token, $context) ?: $qrService->resolvePublicCode($token);

        if (! $identity) {
            return new AttendanceSheetResult(false, 'invalid_qr', 'کد QR نامعتبر، غیرفعال یا ابطال‌شده است.', $sheet);
        }

        if ($identity->subject_type !== QrIdentity::SUBJECT_PERSON) {
            return new AttendanceSheetResult(false, 'not_beneficiary', 'این QR متعلق به مددجو نیست.', $sheet);
        }

        $person = $identity->subject instanceof Person
            ? $identity->subject
            : Person::query()->find((int) $identity->subject_id);

        if (! $person) {
            return new AttendanceSheetResult(false, 'beneficiary_unavailable', 'اطلاعات مددجو در دسترس نیست.', $sheet);
        }

        return [$person, $identity];
    }

    private function belongsToSheetOwner(AttendanceSheet $sheet, Person $person): bool
    {
        return Person::query()
            ->whereKey($person->id)
            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $sheet->social_worker_id))
            ->exists();
    }

    private function entryAttributes(
        AttendanceSheet $sheet,
        Person $person,
        User $operator,
        string $method,
        ?QrIdentity $qrIdentity
    ): array {
        return [
            'attendance_sheet_id' => $sheet->id,
            'person_id' => $person->id,
            'qr_identity_id' => $qrIdentity?->id,
            'person_name' => $person->full_name ?: trim(($person->first_name ?? '').' '.($person->last_name ?? '')),
            'person_code' => $person->person_code,
            'national_id' => $person->national_id,
            'checked_in_at' => now(),
            'check_in_method' => $method,
            'checked_in_by' => $operator->id,
        ];
    }
}
