<?php

namespace App\Queries\People;

use App\Helpers\Morilog\Jalalian;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Reports\People\BeneficiaryReportColumnRegistry;
use App\Reports\People\BeneficiaryReportCriteria;
use App\Reports\People\BeneficiaryReportSemantics;
use Illuminate\Database\Eloquent\Builder;

final class BeneficiaryReportQuery
{
    public function __construct(
        private readonly BeneficiaryReportColumnRegistry $columnRegistry,
        private readonly BeneficiaryReportSemantics $semantics,
        private readonly BeneficiaryOperationalFilters $operationalFilters,
    ) {}

    public function build(BeneficiaryReportCriteria $criteria): Builder
    {
        $query = Person::query();

        $this->applyGlobalSearch($query, $criteria->globalSearch);

        foreach ($criteria->filters as $filter) {
            if ($this->operationalFilters->handles((string) ($filter['field'] ?? ''))) {
                continue;
            }

            $this->applyFilter($query, $filter);
        }

        $this->operationalFilters->apply($query, $criteria->filters);
        $this->applyColumnFilters($query, $criteria->columnFilters);
        $this->applySorting($query, $criteria->sortColumn, $criteria->sortDirection);

        return $query->select('people.*');
    }

    private function applyGlobalSearch(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $subQuery) use ($term): void {
            $subQuery->where('full_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('national_id', 'like', "%{$term}%")
                ->orWhere('phone_number', 'like', "%{$term}%");
        });
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyFilter(Builder $query, array $filter): void
    {
        match ($filter['type'] ?? null) {
            'text' => $this->applyTextFilter($query, $filter),
            'select' => $this->applySelectFilter($query, $filter),
            'date' => $this->applyDateFilter($query, $filter),
            'number' => $this->applyNumberFilter($query, $filter),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyTextFilter(Builder $query, array $filter): void
    {
        $field = (string) ($filter['field'] ?? '');
        $value = trim((string) ($filter['value'] ?? ''));
        $operator = ($filter['operator'] ?? null) === 'exact' ? 'exact' : 'contains';

        if ($field === 'caseworker') {
            $selectedWorkerIds = collect($filter['selected'] ?? [])
                ->pluck('id')
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->values();

            if ($selectedWorkerIds->isNotEmpty()) {
                $query->whereHas('guardian', function (Builder $guardianQuery) use ($selectedWorkerIds): void {
                    $guardianQuery->whereIn('social_worker_id', $selectedWorkerIds->all());
                });

                return;
            }

            if ($value !== '') {
                $workerIds = $this->semantics
                    ->historicalWorkerQuery()
                    ->autocompleteSearch($value)
                    ->select('social_workers.id');

                $query->whereHas('guardian', function (Builder $guardianQuery) use ($workerIds): void {
                    $guardianQuery->whereIn('social_worker_id', $workerIds);
                });
            }

            return;
        }

        if ($value === '') {
            return;
        }

        if ($field === 'guardian_national_code') {
            $query->whereHas('guardian', function (Builder $guardianQuery) use ($value, $operator): void {
                $guardianQuery->where(
                    'national_code',
                    $operator === 'exact' ? '=' : 'like',
                    $operator === 'exact' ? $value : "%{$value}%"
                );
            });

            return;
        }

        if ($field === 'guardian_full_name') {
            $query->whereHas('guardian', function (Builder $guardianQuery) use ($value): void {
                $guardianQuery->where(function (Builder $nameQuery) use ($value): void {
                    $nameQuery->where('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%");
                });
            });

            return;
        }

        if ($field === 'guardian_mobile') {
            $query->whereHas('guardian', function (Builder $guardianQuery) use ($value, $operator): void {
                $guardianQuery->where(
                    'guardian_phone_number',
                    $operator === 'exact' ? '=' : 'like',
                    $operator === 'exact' ? $value : "%{$value}%"
                );
            });

            return;
        }

        $query->where(
            $field,
            $operator === 'exact' ? '=' : 'like',
            $operator === 'exact' ? $value : "%{$value}%"
        );
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applySelectFilter(Builder $query, array $filter): void
    {
        $field = (string) ($filter['field'] ?? '');
        $value = $filter['value'] ?? '';

        if ($value === '') {
            return;
        }

        match ($field) {
            'skills' => $query->whereHas(
                'skills',
                fn (Builder $skillQuery): Builder => $skillQuery->where('skills.id', $value)
            ),
            'harm_types' => $query->whereHas(
                'harmTypes',
                fn (Builder $harmQuery): Builder => $harmQuery->where('harm_types.id', $value)
            ),
            'disability_status_type' => $this->applyDisabilityFilter($query, (string) $value),
            'education_status' => $query->whereHas(
                'education',
                fn (Builder $educationQuery): Builder => $educationQuery->where('is_studying', (int) $value)
            ),
            'education_level' => $query->whereHas(
                'education',
                fn (Builder $educationQuery): Builder => $educationQuery->where('education_level_id', $value)
            ),
            'works_alongside_study' => $query->whereHas(
                'education',
                fn (Builder $educationQuery): Builder => $educationQuery->where('works_alongside_study', (int) $value)
            ),
            'guardian_relation' => $query->whereHas(
                'familyStatus',
                fn (Builder $familyStatusQuery): Builder => $familyStatusQuery->where('guardian_relation_type_id', $value)
            ),
            'has_parent_disability' => $query->whereHas(
                'familyStatus',
                fn (Builder $familyStatusQuery): Builder => $familyStatusQuery->where('has_parent_disability', (int) $value)
            ),
            'economic_decile' => $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where('economic_decile', (int) $value)
            ),
            'guardian_occupation' => $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where('occupation_id', $value)
            ),
            'guardian_job_type' => $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where('job_type_id', $value)
            ),
            'insurance_status_type' => $this->applyGuardianStatusTypeFilter(
                $query,
                (string) $value,
                'insurance_status',
                'insurance_type_id'
            ),
            'vehicle_status_type' => $this->applyGuardianStatusTypeFilter(
                $query,
                (string) $value,
                'has_vehicle',
                'vehicle_type_id'
            ),
            'housing_status' => $query->whereHas(
                'guardian.residence',
                fn (Builder $residenceQuery): Builder => $residenceQuery->where('residence_status_id', $value)
            ),
            'district' => $query->whereHas(
                'guardian.residence',
                fn (Builder $residenceQuery): Builder => $residenceQuery->where('district_id', $value)
            ),
            'is_local_to_city' => $query->whereHas(
                'guardian.residence',
                fn (Builder $residenceQuery): Builder => $residenceQuery->where('is_local_to_city', (int) $value)
            ),
            'support_organization' => $query->whereHas(
                'supportCoverage',
                fn (Builder $supportCoverageQuery): Builder => $supportCoverageQuery->where('support_organization_id', $value)
            ),
            'need_level' => $query->whereHas(
                'needsLevel',
                fn (Builder $needsLevelQuery): Builder => $needsLevelQuery->where('need_level_id', $value)
            ),
            default => $query->where($field, $value),
        };
    }

    private function applyDisabilityFilter(Builder $query, string $value): void
    {
        if (str_starts_with($value, 'has:')) {
            $query->where('has_disability', (int) str_replace('has:', '', $value));

            return;
        }

        if (str_starts_with($value, 'type:')) {
            $query->where('disability_type_id', (int) str_replace('type:', '', $value));
        }
    }

    private function applyGuardianStatusTypeFilter(
        Builder $query,
        string $value,
        string $statusColumn,
        string $typeColumn,
    ): void {
        if (str_starts_with($value, 'status:')) {
            $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where(
                    $statusColumn,
                    (int) str_replace('status:', '', $value)
                )
            );

            return;
        }

        if (str_starts_with($value, 'type:')) {
            $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where(
                    $typeColumn,
                    (int) str_replace('type:', '', $value)
                )
            );
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyDateFilter(Builder $query, array $filter): void
    {
        $mode = $filter['mode'] ?? 'exact';

        if ($mode === 'exact') {
            $value = trim((string) ($filter['exact'] ?? ''));
            if ($value === '') {
                return;
            }

            $date = $this->parseJalaliDate($value);
            if ($date === null) {
                $this->rejectInvalidCriteria($query);

                return;
            }

            $query->where('birth_date_full', $this->semantics->formatJalaliDate($date));

            return;
        }

        if ($mode === 'range') {
            $fromValue = trim((string) ($filter['from'] ?? ''));
            $toValue = trim((string) ($filter['to'] ?? ''));
            if ($fromValue === '' && $toValue === '') {
                return;
            }

            $from = $fromValue === '' ? null : $this->parseJalaliDate($fromValue);
            $to = $toValue === '' ? null : $this->parseJalaliDate($toValue);

            if (($fromValue !== '' && $from === null) || ($toValue !== '' && $to === null)) {
                $this->rejectInvalidCriteria($query);

                return;
            }

            $fromDate = $from === null ? null : $this->semantics->formatJalaliDate($from);
            $toDate = $to === null ? null : $this->semantics->formatJalaliDate($to);

            if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
                $this->rejectInvalidCriteria($query);

                return;
            }

            if ($fromDate !== null && $toDate !== null) {
                $query->whereBetween('birth_date_full', [$fromDate, $toDate]);
            } elseif ($fromDate !== null) {
                $query->where('birth_date_full', '>=', $fromDate);
            } elseif ($toDate !== null) {
                $query->where('birth_date_full', '<=', $toDate);
            }

            return;
        }

        if ($mode === 'month' && is_numeric($filter['month'] ?? null)) {
            $month = (int) $filter['month'];
            if ($month >= 1 && $month <= 12) {
                $query->where('birth_month', $month);
            }

            return;
        }

        if ($mode === 'year' && is_numeric($filter['year'] ?? null)) {
            $query->where('birth_year', (int) $filter['year']);
        }
    }

    /**
     * @return array{int, int, int}|null
     */
    private function parseJalaliDate(string $value): ?array
    {
        if (! preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', trim($value), $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (! $this->semantics->isValidJalaliDate($year, $month, $day)) {
            return null;
        }

        return [$year, $month, $day];
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyNumberFilter(Builder $query, array $filter): void
    {
        $field = (string) ($filter['field'] ?? '');
        $value = $filter['value'] ?? null;

        if ($value === '' || $value === null || ! is_numeric($value)) {
            return;
        }

        $operator = [
            'eq' => '=',
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
        ][$filter['operator'] ?? 'eq'] ?? '=';
        $numericValue = (int) $value;

        match ($field) {
            'supported_children_count' => $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where('children_count', $operator, $numericValue)
            ),
            'average_income' => $query->whereHas(
                'guardian',
                fn (Builder $guardianQuery): Builder => $guardianQuery->where('average_income', $operator, $numericValue)
            ),
            'age' => $this->applyAgeFilter($query, $operator, $numericValue),
            'months_without_service' => $this->applyMonthsWithoutServiceFilter($query, $numericValue),
            default => null,
        };
    }

    private function applyAgeFilter(Builder $query, string $operator, int $age): void
    {
        $now = Jalalian::now();
        $month = $now->getMonth();
        $day = $now->getDay();

        match ($operator) {
            '=' => $query
                ->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateAfter(
                    $ageQuery,
                    $now->getYear() - $age - 1,
                    $month,
                    $day
                ))
                ->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateOnOrBefore(
                    $ageQuery,
                    $now->getYear() - $age,
                    $month,
                    $day
                )),
            '>' => $query->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateOnOrBefore(
                $ageQuery,
                $now->getYear() - $age - 1,
                $month,
                $day
            )),
            '>=' => $query->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateOnOrBefore(
                $ageQuery,
                $now->getYear() - $age,
                $month,
                $day
            )),
            '<' => $query->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateAfter(
                $ageQuery,
                $now->getYear() - $age,
                $month,
                $day
            )),
            '<=' => $query->where(fn (Builder $ageQuery): Builder => $this->whereBirthDateAfter(
                $ageQuery,
                $now->getYear() - $age - 1,
                $month,
                $day
            )),
            default => null,
        };
    }

    private function applyMonthsWithoutServiceFilter(Builder $query, int $months): void
    {
        if ($months < 1) {
            return;
        }

        $threshold = $this->semantics->serviceRecencyCutoff($months);

        $query->whereDoesntHave(
            'serviceDeliveries',
            fn (Builder $deliveryQuery): Builder => $deliveryQuery->where('delivered_at', '>', $threshold)
        )->whereDoesntHave(
            'guardian.serviceDeliveries',
            fn (Builder $deliveryQuery): Builder => $deliveryQuery->where('delivered_at', '>', $threshold)
        );
    }

    private function whereBirthDateOnOrBefore(Builder $query, int $year, int $month, int $day): Builder
    {
        return $query->where('birth_year', '<', $year)
            ->orWhere(function (Builder $sameYear) use ($year, $month, $day): void {
                $sameYear->where('birth_year', $year)
                    ->where(function (Builder $birthday) use ($month, $day): void {
                        $birthday->where('birth_month', '<', $month)
                            ->orWhere(fn (Builder $sameMonth): Builder => $sameMonth
                                ->where('birth_month', $month)
                                ->where('birth_day', '<=', $day));
                    });
            });
    }

    private function whereBirthDateAfter(Builder $query, int $year, int $month, int $day): Builder
    {
        return $query->where('birth_year', '>', $year)
            ->orWhere(function (Builder $sameYear) use ($year, $month, $day): void {
                $sameYear->where('birth_year', $year)
                    ->where(function (Builder $birthday) use ($month, $day): void {
                        $birthday->where('birth_month', '>', $month)
                            ->orWhere(fn (Builder $sameMonth): Builder => $sameMonth
                                ->where('birth_month', $month)
                                ->where('birth_day', '>', $day));
                    });
            });
    }

    /**
     * @param  array<string, mixed>  $columnFilters
     */
    private function applyColumnFilters(Builder $query, array $columnFilters): void
    {
        foreach ($columnFilters as $column => $value) {
            if ($value === '') {
                continue;
            }

            match ($column) {
                'person_code', 'full_name', 'first_name', 'last_name', 'national_id', 'phone_number' => $query
                    ->where($column, 'like', "%{$value}%"),
                'gender', 'sadaat_status', 'birth_month' => $query->where($column, $value),
                'birth_year' => $query->where('birth_year', (int) $value),
                'related_description' => $query->whereHas(
                    'disabilityType',
                    fn (Builder $disabilityQuery): Builder => $disabilityQuery->where('name', 'like', "%{$value}%")
                ),
                'skills_description' => $query->where('skills_description', 'like', "%{$value}%"),
                'responsible_social_worker' => $query->whereHas(
                    'guardian',
                    fn (Builder $guardianQuery): Builder => $guardianQuery->whereIn(
                        'social_worker_id',
                        $this->semantics
                            ->historicalWorkerQuery()
                            ->autocompleteSearch((string) $value)
                            ->select('social_workers.id')
                    )
                ),
                'guardian_beneficiary_with_code' => $query->whereHas(
                    'guardian',
                    function (Builder $guardianQuery) use ($value): void {
                        $guardianQuery->where(function (Builder $subQuery) use ($value): void {
                            $subQuery->where('first_name', 'like', "%{$value}%")
                                ->orWhere('last_name', 'like', "%{$value}%")
                                ->orWhere('guardian_code', 'like', "%{$value}%");
                        });
                    }
                ),
                default => null,
            };
        }
    }

    private function applySorting(Builder $query, ?string $sortColumn, string $sortDirection): void
    {
        $direction = $sortDirection === 'asc' ? 'asc' : 'desc';

        match ($sortColumn) {
            'person_code',
            'first_name',
            'last_name',
            'national_id',
            'phone_number',
            'gender',
            'sadaat_status',
            'birth_year',
            'birth_month' => $query->orderBy($sortColumn, $direction),
            'full_name' => $query->orderBy('last_name', $direction)->orderBy('first_name', $direction),
            'birth_date' => $query
                ->orderBy('birth_year', $direction)
                ->orderBy('birth_month', $direction)
                ->orderBy('birth_day', $direction),
            'responsible_social_worker' => $this->sortBySocialWorker($query, $direction),
            'guardian_beneficiary_with_code' => $this->sortByGuardian($query, $direction),
            'related_description' => $query->orderBy(
                \App\Models\DisabilityType::query()
                    ->select('name')
                    ->whereColumn('disability_types.id', 'people.disability_type_id')
                    ->limit(1),
                $direction
            ),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->latest(),
        };

        if ($sortColumn === 'guardian_beneficiary_with_code') {
            $query->orderBy('last_name', $direction)
                ->orderBy('first_name', $direction);
        } elseif (! in_array($sortColumn, ['full_name', 'birth_date'], true)) {
            $query->orderBy('created_at', 'desc');
        }

        $query->orderBy('people.id', 'desc');
    }

    private function sortBySocialWorker(Builder $query, string $direction): void
    {
        $query->orderBy(
            SocialWorker::query()
                ->withoutGlobalScope('active')
                ->withTrashed()
                ->select('last_name')
                ->join('guardians', 'guardians.social_worker_id', '=', 'social_workers.id')
                ->whereColumn('guardians.id', 'people.guardian_id')
                ->limit(1),
            $direction
        )->orderBy(
            SocialWorker::query()
                ->withoutGlobalScope('active')
                ->withTrashed()
                ->select('first_name')
                ->join('guardians', 'guardians.social_worker_id', '=', 'social_workers.id')
                ->whereColumn('guardians.id', 'people.guardian_id')
                ->limit(1),
            $direction
        );
    }

    private function sortByGuardian(Builder $query, string $direction): void
    {
        $query->orderBy(
            Guardian::query()
                ->select('guardian_code')
                ->whereColumn('guardians.id', 'people.guardian_id')
                ->limit(1),
            $direction
        )->orderBy(
            Guardian::query()
                ->select('last_name')
                ->whereColumn('guardians.id', 'people.guardian_id')
                ->limit(1),
            $direction
        )->orderBy(
            Guardian::query()
                ->select('first_name')
                ->whereColumn('guardians.id', 'people.guardian_id')
                ->limit(1),
            $direction
        );
    }

    private function rejectInvalidCriteria(Builder $query): void
    {
        $query->whereRaw('1 = 0');
    }
}
