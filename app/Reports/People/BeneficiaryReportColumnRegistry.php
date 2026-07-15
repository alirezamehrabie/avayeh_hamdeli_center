<?php

namespace App\Reports\People;

use App\Models\Person;

final class BeneficiaryReportColumnRegistry
{
    public const DEFAULT_COLUMNS = [
        'person_code',
        'full_name',
        'national_id',
        'phone_number',
        'birth_date',
    ];

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'person_code' => 'کد مددجو',
            'full_name' => 'نام و نام خانوادگی',
            'responsible_social_worker' => 'مددکار مسئول',
            'guardian_beneficiary_with_code' => 'سرپرست و کد خانوار',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'national_id' => 'کد ملی',
            'phone_number' => 'موبایل',
            'gender' => 'جنسیت',
            'sadaat_status' => 'سادات',
            'birth_date' => 'تاریخ تولد',
            'birth_year' => 'سال تولد',
            'birth_month' => 'ماه تولد',
            'beneficiary_injury_disability_type' => 'آسیب / نوع آسیب',
            'related_description' => 'معلولیت / نوع معلولیت',
            'disability_description' => 'شرح معلولیت / بیماری',
            'client_case_history' => 'سوابق / شرح وضعیت مددجو',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function filterDefinitions(array $genderOptions, array $sadaatOptions): array
    {
        return [
            'person_code' => ['type' => 'text', 'placeholder' => 'کد مددجو'],
            'full_name' => ['type' => 'text', 'placeholder' => 'نام و نام خانوادگی'],
            'responsible_social_worker' => ['type' => 'text', 'placeholder' => 'نام یا کد مددکار'],
            'guardian_beneficiary_with_code' => ['type' => 'text', 'placeholder' => 'سرپرست یا کد خانوار'],
            'first_name' => ['type' => 'text', 'placeholder' => 'نام'],
            'last_name' => ['type' => 'text', 'placeholder' => 'نام خانوادگی'],
            'national_id' => ['type' => 'text', 'placeholder' => 'کد ملی'],
            'phone_number' => ['type' => 'text', 'placeholder' => 'موبایل'],
            'gender' => ['type' => 'select', 'options' => $genderOptions],
            'sadaat_status' => ['type' => 'select', 'options' => $sadaatOptions],
            'birth_year' => ['type' => 'number', 'placeholder' => 'سال تولد'],
            'birth_month' => ['type' => 'select', 'options' => Person::$months],
            'related_description' => ['type' => 'text', 'placeholder' => 'نوع معلولیت'],
        ];
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'person_code',
            'full_name',
            'responsible_social_worker',
            'guardian_beneficiary_with_code',
            'first_name',
            'last_name',
            'national_id',
            'phone_number',
            'gender',
            'sadaat_status',
            'birth_date',
            'birth_year',
            'birth_month',
            'related_description',
            'created_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function normalizeVisibleColumns(array $columns): array
    {
        $allowed = array_keys($this->labels());
        $normalized = collect($columns)
            ->filter(fn (mixed $column): bool => is_string($column) && in_array($column, $allowed, true))
            ->unique()
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : self::DEFAULT_COLUMNS;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeColumnFilters(array $filters): array
    {
        $allowed = array_keys($this->filterDefinitions([], []));

        return collect($filters)
            ->filter(fn (mixed $value, mixed $column): bool => is_string($column)
                && in_array($column, $allowed, true)
                && (is_scalar($value) || $value === null))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->all();
    }

    public function normalizeSortColumn(?string $column): ?string
    {
        return is_string($column) && in_array($column, $this->sortableColumns(), true)
            ? $column
            : null;
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function eagerLoads(): array
    {
        return [
            'disabilityType:id,name',
            'harmTypes:id,title',
            'guardian:id,social_worker_id,guardian_code,first_name,last_name',
            'guardian.socialWorker' => fn ($query) => app(BeneficiaryReportSemantics::class)
                ->includeHistoricalWorkers($query)
                ->select(['social_workers.id', 'first_name', 'last_name', 'is_active', 'deleted_at']),
        ];
    }

    public function value(Person $person, string $column): mixed
    {
        return match ($column) {
            'person_code' => $person->person_code,
            'full_name' => $person->full_name,
            'responsible_social_worker' => app(BeneficiaryReportSemantics::class)
                ->workerLabel($person->guardian?->socialWorker),
            'guardian_beneficiary_with_code' => $person->guardian_beneficiary_with_code,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'national_id' => $person->national_id,
            'phone_number' => $person->phone_number ?: '-',
            'gender' => $person->gender_label ?: '-',
            'sadaat_status' => $person->sadaat_status_label ?: '-',
            'birth_date' => $person->birth_date ?: '-',
            'birth_year' => $person->birth_year ?? '-',
            'birth_month' => $person->birth_month ?? '-',
            'beneficiary_injury_disability_type' => $person->harmTypes->pluck('title')->filter()->implode('، ') ?: '-',
            'related_description' => $person->disabilityType?->name ?: '-',
            'disability_description' => $person->disability_description ?: '-',
            'client_case_history' => $person->client_case_history ?: '-',
            'created_at' => $person->created_at?->format('Y/m/d') ?: '-',
            default => '-',
        };
    }
}
