<?php

namespace App\Services;

use App\Helpers\Morilog\Jalalian;
use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\Person;

class AttendanceSheetResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $code,
        public readonly string $message,
        public readonly ?AttendanceSheet $sheet = null,
        public readonly ?Person $person = null,
        public readonly ?AttendanceSheetEntry $entry = null,
    ) {}

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'code' => $this->code,
            'message' => $this->message,
            'person' => $this->person ? [
                'id' => $this->person->id,
                'name' => $this->personName(),
                'person_code' => $this->person->person_code,
                'national_id' => $this->person->national_id,
            ] : null,
            'entry' => $this->entry ? [
                'id' => $this->entry->id,
                'check_in_method' => $this->entry->check_in_method,
                'checked_in_at' => $this->entry->checked_in_at?->toISOString(),
                'checked_in_time' => $this->formatTime($this->entry->checked_in_at),
                'check_out_method' => $this->entry->check_out_method,
                'checked_out_at' => $this->entry->checked_out_at?->toISOString(),
                'checked_out_time' => $this->formatTime($this->entry->checked_out_at),
            ] : null,
            'attendance' => $this->entry ? [
                'checked_in_time' => $this->formatTime($this->entry->checked_in_at),
                'checked_out_time' => $this->formatTime($this->entry->checked_out_at),
            ] : null,
            'stats' => $this->sheet ? [
                'checked_in_count' => $this->sheet->entries()->whereNotNull('checked_in_at')->count(),
                'checked_out_count' => $this->sheet->entries()->whereNotNull('checked_out_at')->count(),
            ] : null,
        ];
    }

    private function personName(): string
    {
        if (! $this->person) {
            return '';
        }

        return $this->person->full_name
            ?: trim(($this->person->first_name ?? '').' '.($this->person->last_name ?? ''));
    }

    private function formatTime(mixed $value): ?string
    {
        return $value ? Jalalian::fromDateTime($value)->format('H:i') : null;
    }
}
