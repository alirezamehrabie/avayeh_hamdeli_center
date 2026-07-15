<?php

namespace App\Services\Ai;

use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\Person;
use App\Models\ServiceDelivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BeneficiaryCaseContextBuilder
{
    public function build(
        Person $person,
        Collection $serviceDeliveries,
        Collection $activityAttendances,
        Collection $caseRecords,
    ): array {
        $guardian = $person->relationLoaded('guardian') ? $person->getRelation('guardian') : null;
        $redactions = collect([
            $person->first_name,
            $person->last_name,
            $person->full_name,
            $person->father_name,
            $guardian?->first_name,
            $guardian?->last_name,
            $guardian?->full_name,
        ])->filter(fn (mixed $value): bool => mb_strlen(trim((string) $value)) >= 3)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        return [
            'profile' => array_filter([
                'age' => $person->exact_age,
                'gender' => $person->gender,
                'has_disability' => $this->triState($person->has_disability),
                'disability_description' => $this->text($person->disability_description, 500, $redactions),
                'skills' => $this->text($person->skills_description, 700, $redactions),
                'case_history' => $this->text($person->client_case_history, 1500, $redactions),
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
            'services' => $serviceDeliveries->take(30)->map(fn (ServiceDelivery $delivery): array => array_filter([
                'date' => $delivery->delivered_at?->toDateString(),
                'service' => $this->text($delivery->service?->name ?: $delivery->service?->serviceName?->name, 200, $redactions),
                'category' => $this->text($delivery->serviceCategory?->name, 200, $redactions),
                'quantity' => $delivery->delivered_quantity,
                'value_rials' => $delivery->delivered_total_value,
                'notes' => $this->text($delivery->notes, 500, $redactions),
                'scope' => (int) $delivery->person_id === (int) $person->id ? 'beneficiary' : 'household',
            ], fn (mixed $value): bool => $value !== null && $value !== ''))->values()->all(),
            'activities' => $activityAttendances->take(30)->map(fn (ActivityAttendance $attendance): array => array_filter([
                'date' => $attendance->checked_in_at?->toDateString(),
                'activity' => $this->text($attendance->activity?->name, 200, $redactions),
                'status' => $attendance->status,
                'notes' => $this->text($attendance->notes, 500, $redactions),
            ], fn (mixed $value): bool => $value !== null && $value !== ''))->values()->all(),
            'case_records' => $caseRecords->take(30)->map(fn (BeneficiaryCaseRecord $record): array => array_filter([
                'date' => $record->recorded_at?->toDateString(),
                'type' => $record->record_type,
                'title' => $this->text($record->title, 250, $redactions),
                'description' => $this->text($record->description, 1000, $redactions),
                'amount_rials' => $record->amount,
            ], fn (mixed $value): bool => $value !== null && $value !== ''))->values()->all(),
        ];
    }

    private function text(?string $value, int $limit, array $redactions): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[ایمیل حذف شد]', $value) ?? $value;
        $value = preg_replace('/[\p{N}][\p{N}\s\-]{6,}[\p{N}]/u', '[شناسه حذف شد]', $value) ?? $value;
        $value = str_ireplace($redactions, '[نام حذف شد]', $value);

        return Str::limit($value, $limit, '...');
    }

    private function triState(?bool $value): string
    {
        return match ($value) {
            true => 'yes',
            false => 'no',
            null => 'unknown',
        };
    }
}
