<?php

namespace App\Queries\People;

use App\Helpers\Carbon\Carbon;
use App\Helpers\Morilog\Jalalian;
use App\Reports\People\BeneficiaryReportSemantics;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class BeneficiaryOperationalFilters
{
    private const SERVICE_FIELDS = [
        'service_id',
        'service_category_id',
        'service_type',
        'service_status',
        'service_priority',
        'delivery_channel',
        'service_recipient_scope',
        'service_delivery_date',
        'service_delivery_count',
        'service_delivery_quantity',
        'service_delivery_value',
    ];

    private const CASE_FIELDS = [
        'case_record_type',
        'case_record_date',
        'case_record_count',
        'case_record_amount',
    ];

    private const ACTIVITY_FIELDS = [
        'activity_type',
        'activity_attendance_status',
        'activity_registration_method',
        'activity_attendance_date',
        'activity_attendance_count',
    ];

    private const WORKER_FIELDS = [
        'worker_assignment_status',
        'worker_allocation_status',
    ];

    public function __construct(
        private readonly BeneficiaryReportSemantics $semantics,
    ) {}

    public function handles(string $field): bool
    {
        return in_array($field, [
            ...self::SERVICE_FIELDS,
            ...self::CASE_FIELDS,
            ...self::ACTIVITY_FIELDS,
            ...self::WORKER_FIELDS,
        ], true);
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    public function apply(EloquentBuilder $query, array $filters): void
    {
        $this->applyServiceFilters($query, $this->forFields($filters, self::SERVICE_FIELDS));
        $this->applyCaseFilters($query, $this->forFields($filters, self::CASE_FIELDS));
        $this->applyActivityFilters($query, $this->forFields($filters, self::ACTIVITY_FIELDS));
        $this->applyWorkerFilters($query, $this->forFields($filters, self::WORKER_FIELDS), $filters);
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     * @param  list<string>  $fields
     * @return list<array<string, mixed>>
     */
    private function forFields(array $filters, array $fields): array
    {
        return array_values(array_filter(
            $filters,
            fn (array $filter): bool => in_array((string) ($filter['field'] ?? ''), $fields, true)
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function applyServiceFilters(EloquentBuilder $query, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $scopes = collect($filters)
            ->where('field', 'service_recipient_scope')
            ->pluck('value')
            ->filter()
            ->unique()
            ->values();

        if ($scopes->contains(fn (mixed $scope): bool => ! in_array($scope, ['either', 'direct', 'household'], true))
            || ($scopes->contains('direct') && $scopes->contains('household'))) {
            $this->reject($query);

            return;
        }

        $scope = $scopes->contains('direct')
            ? 'direct'
            : ($scopes->contains('household') ? 'household' : 'either');
        $deliveries = $this->serviceDeliverySubquery($scope);
        $hasConstraint = $scopes->isNotEmpty();
        $aggregateFilters = [];

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');

            if (in_array($field, ['service_delivery_count', 'service_delivery_quantity', 'service_delivery_value'], true)) {
                if (($filter['value'] ?? '') !== '') {
                    $aggregateFilters[] = $filter;
                }

                continue;
            }

            if ($field === 'service_recipient_scope') {
                continue;
            }

            if ($field === 'service_delivery_date') {
                [$active, $valid] = $this->applyDateFilter($deliveries, $filter, 'report_deliveries.delivered_at');
                if (! $valid) {
                    $this->reject($query);

                    return;
                }

                $hasConstraint = $hasConstraint || $active;

                continue;
            }

            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $hasConstraint = true;

            match ($field) {
                'service_id' => is_numeric($value)
                    ? $deliveries->where('report_deliveries.service_id', (int) $value)
                    : $this->reject($query),
                'service_category_id' => is_numeric($value)
                    ? $deliveries->where('report_deliveries.service_category_id', (int) $value)
                    : $this->reject($query),
                'service_type' => $deliveries->where('report_services.service_type', $value),
                'service_status' => $deliveries->where('report_services.status', $value),
                'service_priority' => $deliveries->where('report_services.priority', $value),
                'delivery_channel' => $deliveries->where('report_deliveries.delivery_channel', $value),
                default => null,
            };
        }

        if ($aggregateFilters !== []) {
            foreach ($aggregateFilters as $filter) {
                if (! $this->applyAggregateFilter(
                    $query,
                    $deliveries,
                    $filter,
                    match ($filter['field']) {
                        'service_delivery_count' => 'COUNT(*)',
                        'service_delivery_quantity' => 'COALESCE(SUM(report_deliveries.delivered_quantity), 0)',
                        'service_delivery_value' => 'COALESCE(SUM(report_deliveries.delivered_total_value), 0)',
                    },
                    ($filter['field'] ?? null) === 'service_delivery_count'
                )) {
                    return;
                }
            }

            return;
        }

        if ($hasConstraint) {
            $query->whereExists((clone $deliveries)->selectRaw('1'));
        }
    }

    private function serviceDeliverySubquery(string $scope): Builder
    {
        $query = DB::table('service_deliveries as report_deliveries')
            ->join('services as report_services', 'report_services.id', '=', 'report_deliveries.service_id')
            ->whereNull('report_deliveries.deleted_at');

        if ($scope === 'direct') {
            return $query->whereColumn('report_deliveries.person_id', 'people.id');
        }

        if ($scope === 'household') {
            return $query->whereColumn('report_deliveries.guardian_id', 'people.guardian_id');
        }

        return $query->where(function (Builder $recipient): void {
            $recipient->whereColumn('report_deliveries.person_id', 'people.id')
                ->orWhereColumn('report_deliveries.guardian_id', 'people.guardian_id');
        });
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function applyCaseFilters(EloquentBuilder $query, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $records = DB::table('beneficiary_case_records as report_case_records')
            ->whereColumn('report_case_records.person_id', 'people.id');
        $hasConstraint = false;
        $aggregateFilters = [];

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');

            if (in_array($field, ['case_record_count', 'case_record_amount'], true)) {
                if (($filter['value'] ?? '') !== '') {
                    $aggregateFilters[] = $filter;
                }

                continue;
            }

            if ($field === 'case_record_date') {
                [$active, $valid] = $this->applyDateFilter($records, $filter, 'report_case_records.recorded_at');
                if (! $valid) {
                    $this->reject($query);

                    return;
                }

                $hasConstraint = $hasConstraint || $active;

                continue;
            }

            $value = trim((string) ($filter['value'] ?? ''));
            if ($field === 'case_record_type' && $value !== '') {
                $records->where('report_case_records.record_type', $value);
                $hasConstraint = true;
            }
        }

        if ($aggregateFilters !== []) {
            foreach ($aggregateFilters as $filter) {
                if (! $this->applyAggregateFilter(
                    $query,
                    $records,
                    $filter,
                    ($filter['field'] ?? null) === 'case_record_count'
                        ? 'COUNT(*)'
                        : 'COALESCE(SUM(report_case_records.amount), 0)',
                    ($filter['field'] ?? null) === 'case_record_count'
                )) {
                    return;
                }
            }

            return;
        }

        if ($hasConstraint) {
            $query->whereExists((clone $records)->selectRaw('1'));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function applyActivityFilters(EloquentBuilder $query, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $attendances = DB::table('activity_attendances as report_attendances')
            ->join('activities as report_activities', 'report_activities.id', '=', 'report_attendances.activity_id')
            ->whereColumn('report_attendances.person_id', 'people.id');
        $hasConstraint = false;
        $aggregateFilters = [];

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');

            if ($field === 'activity_attendance_count') {
                if (($filter['value'] ?? '') !== '') {
                    $aggregateFilters[] = $filter;
                }

                continue;
            }

            if ($field === 'activity_attendance_date') {
                [$active, $valid] = $this->applyDateFilter(
                    $attendances,
                    $filter,
                    'report_attendances.checked_in_at',
                    true
                );
                if (! $valid) {
                    $this->reject($query);

                    return;
                }

                $hasConstraint = $hasConstraint || $active;

                continue;
            }

            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $hasConstraint = true;

            match ($field) {
                'activity_type' => $attendances->where('report_activities.activity_type', $value),
                'activity_attendance_status' => $attendances->where('report_attendances.status', $value),
                'activity_registration_method' => $attendances->where('report_attendances.registration_method', $value),
                default => null,
            };
        }

        if ($aggregateFilters !== []) {
            foreach ($aggregateFilters as $filter) {
                if (! $this->applyAggregateFilter($query, $attendances, $filter, 'COUNT(*)', true)) {
                    return;
                }
            }

            return;
        }

        if ($hasConstraint) {
            $query->whereExists((clone $attendances)->selectRaw('1'));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $workerFilters
     * @param  list<array<string, mixed>>  $allFilters
     */
    private function applyWorkerFilters(EloquentBuilder $query, array $workerFilters, array $allFilters): void
    {
        foreach ($workerFilters as $filter) {
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if (($filter['field'] ?? null) === 'worker_assignment_status') {
                $this->applyWorkerAssignmentStatus($query, $value);

                continue;
            }

            if (($filter['field'] ?? null) === 'worker_allocation_status') {
                $this->applyWorkerAllocationStatus($query, $value, $allFilters);
            }
        }
    }

    private function applyWorkerAssignmentStatus(EloquentBuilder $query, string $status): void
    {
        if ($status === 'unassigned') {
            $query->where(function (EloquentBuilder $assignment): void {
                $assignment->whereDoesntHave('guardian')
                    ->orWhereHas('guardian', fn (EloquentBuilder $guardian): EloquentBuilder => $guardian
                        ->whereNull('social_worker_id'));
            });

            return;
        }

        if (! in_array($status, ['assigned_active', 'assigned_inactive'], true)) {
            $this->reject($query);

            return;
        }

        $query->whereHas('guardian', function (EloquentBuilder $guardian) use ($status): void {
            $guardian->whereNotNull('social_worker_id')
                ->whereExists(function (Builder $worker) use ($status): void {
                    $worker->from('social_workers as report_workers')
                        ->selectRaw('1')
                        ->whereColumn('report_workers.id', 'guardians.social_worker_id');

                    if ($status === 'assigned_active') {
                        $worker->where('report_workers.is_active', true)
                            ->whereNull('report_workers.deleted_at');
                    } else {
                        $worker->where(function (Builder $inactive): void {
                            $inactive->where('report_workers.is_active', false)
                                ->orWhereNotNull('report_workers.deleted_at');
                        });
                    }
                });
        });
    }

    /**
     * @param  list<array<string, mixed>>  $allFilters
     */
    private function applyWorkerAllocationStatus(EloquentBuilder $query, string $status, array $allFilters): void
    {
        if (! in_array($status, [
            'has_allocation',
            'pending_allocation',
            'completed_allocation',
            'no_allocation',
        ], true)) {
            $this->reject($query);

            return;
        }

        $allocations = $this->allocationSubquery($allFilters, false);
        if ($allocations === null) {
            $this->reject($query);

            return;
        }

        if ($status === 'has_allocation') {
            $query->whereExists((clone $allocations)->selectRaw('1'));

            return;
        }

        if ($status === 'no_allocation') {
            $query->whereNotExists((clone $allocations)->selectRaw('1'));

            return;
        }

        $pendingAllocations = $this->allocationSubquery($allFilters, true);
        if ($pendingAllocations === null) {
            $this->reject($query);

            return;
        }

        if ($status === 'pending_allocation') {
            $query->whereExists((clone $pendingAllocations)->selectRaw('1'));

            return;
        }

        $query->whereExists((clone $allocations)->selectRaw('1'))
            ->whereNotExists((clone $pendingAllocations)->selectRaw('1'));
    }

    /**
     * @param  list<array<string, mixed>>  $allFilters
     */
    private function allocationSubquery(array $allFilters, bool $pending): ?Builder
    {
        $allocations = DB::table('service_social_worker as report_allocations')
            ->join('guardians as report_allocation_guardians', function ($join): void {
                $join->on(
                    'report_allocation_guardians.social_worker_id',
                    '=',
                    'report_allocations.social_worker_id'
                );
            })
            ->join('services as report_allocation_services', 'report_allocation_services.id', '=', 'report_allocations.service_id')
            ->whereColumn('report_allocation_guardians.id', 'people.guardian_id')
            ->where('report_allocations.allocated_quantity', '>', 0);

        foreach ($allFilters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            if ($field === 'service_id') {
                if (! is_numeric($value)) {
                    return null;
                }

                $allocations->where('report_allocations.service_id', (int) $value);
            } elseif ($field === 'service_category_id') {
                if (! is_numeric($value)) {
                    return null;
                }

                $allocations->where('report_allocations.service_category_id', (int) $value);
            } elseif ($field === 'service_type') {
                $allocations->where('report_allocation_services.service_type', $value);
            } elseif ($field === 'service_status') {
                $allocations->where('report_allocation_services.status', $value);
            } elseif ($field === 'service_priority') {
                $allocations->where('report_allocation_services.priority', $value);
            }
        }

        if ($pending) {
            $allocations->whereRaw(
                '(SELECT COALESCE(SUM(report_allocation_deliveries.delivered_quantity), 0)
                    FROM service_deliveries AS report_allocation_deliveries
                    WHERE report_allocation_deliveries.service_id = report_allocations.service_id
                      AND report_allocation_deliveries.service_category_id = report_allocations.service_category_id
                      AND report_allocation_deliveries.social_worker_id = report_allocations.social_worker_id
                      AND report_allocation_deliveries.deleted_at IS NULL
                ) < report_allocations.allocated_quantity'
            );
        }

        return $allocations;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyAggregateFilter(
        EloquentBuilder $query,
        Builder $base,
        array $filter,
        string $expression,
        bool $integerOnly,
    ): bool {
        $value = $filter['value'] ?? null;
        if ($value === '' || $value === null) {
            return true;
        }

        if (! is_numeric($value) || (float) $value < 0 || ($integerOnly && (float) $value !== (float) (int) $value)) {
            $this->reject($query);

            return false;
        }

        $operator = $this->sqlOperator((string) ($filter['operator'] ?? 'eq'));
        $numericValue = $integerOnly ? (int) $value : (float) $value;
        $aggregate = (clone $base)->selectRaw($expression);

        $query->where($aggregate, $operator, $numericValue);

        return true;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array{bool, bool}
     */
    private function applyDateFilter(
        Builder $query,
        array $filter,
        string $column,
        bool $timestamp = false,
    ): array {
        $mode = (string) ($filter['mode'] ?? 'exact');

        if ($mode === 'exact') {
            $value = trim((string) ($filter['exact'] ?? ''));
            if ($value === '') {
                return [false, true];
            }

            $date = $this->parseJalaliDate($value);
            if ($date === null) {
                return [true, false];
            }

            if ($timestamp) {
                $query->where($column, '>=', $date->copy()->startOfDay())
                    ->where($column, '<', $date->copy()->addDay()->startOfDay());
            } else {
                $query->where($column, $date->toDateString());
            }

            return [true, true];
        }

        if ($mode !== 'range') {
            return [true, false];
        }

        $fromValue = trim((string) ($filter['from'] ?? ''));
        $toValue = trim((string) ($filter['to'] ?? ''));
        if ($fromValue === '' && $toValue === '') {
            return [false, true];
        }

        $from = $fromValue === '' ? null : $this->parseJalaliDate($fromValue);
        $to = $toValue === '' ? null : $this->parseJalaliDate($toValue);

        if (($fromValue !== '' && $from === null)
            || ($toValue !== '' && $to === null)
            || ($from !== null && $to !== null && $from->gt($to))) {
            return [true, false];
        }

        if ($from !== null) {
            $query->where($column, '>=', $timestamp ? $from->copy()->startOfDay() : $from->toDateString());
        }

        if ($to !== null) {
            $query->where(
                $column,
                $timestamp ? '<' : '<=',
                $timestamp ? $to->copy()->addDay()->startOfDay() : $to->toDateString()
            );
        }

        return [true, true];
    }

    private function parseJalaliDate(string $value): ?Carbon
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

        return (new Jalalian($year, $month, $day))->toCarbon();
    }

    private function sqlOperator(string $operator): string
    {
        return [
            'eq' => '=',
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
        ][$operator] ?? '=';
    }

    private function reject(EloquentBuilder $query): void
    {
        $query->whereRaw('1 = 0');
    }
}
