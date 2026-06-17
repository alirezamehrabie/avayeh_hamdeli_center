<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;

class ActivityCheckInResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $code,
        public readonly string $message,
        public readonly ?Activity $activity = null,
        public readonly ?Person $person = null,
        public readonly ?ActivityAttendance $attendance = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'code' => $this->code,
            'message' => $this->message,
            'person' => $this->person ? [
                'id' => $this->person->id,
                'name' => $this->person->full_name ?: trim($this->person->first_name . ' ' . $this->person->last_name),
                'person_code' => $this->person->person_code,
                'national_id' => $this->person->national_id,
            ] : null,
            'attendance' => $this->attendance ? [
                'id' => $this->attendance->id,
                'status' => $this->attendance->status,
                'registration_method' => $this->attendance->registration_method,
                'checked_in_at' => $this->attendance->checked_in_at?->toISOString(),
            ] : null,
            'stats' => $this->activity ? [
                'checked_in_count' => $this->activity->attendances()->where('status', 'present')->count(),
                'capacity' => $this->activity->capacity,
            ] : null,
        ];
    }
}
