<?php

namespace App\Livewire\Admin;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\BeneficiaryAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Component;

class OperatorReport extends Component
{
    public string $timeframe = 'month';
    public string $selectedOperatorId = '';
    public int $referenceYear;
    public int $referenceMonth;
    public int $referenceDay;
    public int $referenceHour;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->syncReferenceWithNow();
    }

    public function updatedTimeframe(string $value): void
    {
        if (! in_array($value, ['hour', 'day', 'month', 'year'], true)) {
            $this->timeframe = 'month';
        }

        $this->syncReferenceWithNow();
    }

    public function render()
    {
        $actor = auth()->user();
        $canViewAllOperators = $actor->isManager();
        [$start, $end] = $this->resolveRange();

        $operators = User::query()
            ->when(! $canViewAllOperators, fn ($query) => $query->whereKey($actor->id))
            ->when($canViewAllOperators && filled($this->selectedOperatorId), fn ($query) => $query->whereKey((int) $this->selectedOperatorId))
            ->orderByRaw("case access_level when 'manager' then 1 when 'admin' then 2 else 3 end")
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('name')
            ->get();

        $reports = $this->buildReports($operators, $start, $end);

        return view('livewire.admin.operator-report', [
            'canViewAllOperators' => $canViewAllOperators,
            'reports' => $reports,
            'operatorOptions' => User::query()
                ->when($canViewAllOperators, function ($query) {
                    $query
                        ->orderByRaw("case access_level when 'manager' then 1 when 'admin' then 2 else 3 end")
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->orderBy('name');
                })
                ->when(! $canViewAllOperators, fn ($query) => $query->whereKey($actor->id))
                ->get(['id', 'name', 'first_name', 'last_name', 'access_level']),
            'timeframeOptions' => [
                'hour' => 'ساعتی',
                'day' => 'روزانه',
                'month' => 'ماهانه',
                'year' => 'سالانه',
            ],
            'yearOptions' => $this->yearOptions(),
            'monthOptions' => $this->monthOptions(),
            'dayOptions' => $this->dayOptions(),
            'hourOptions' => range(0, 23),
            'selectedRangeLabel' => $this->rangeLabel($start, $end),
            'totalActivities' => $reports->sum('totalActivities'),
            'totalRegistrations' => $reports->sum('registrations'),
            'totalEdits' => $reports->sum('edits'),
            'totalDeletions' => $reports->sum('deletions'),
        ]);
    }

    private function buildReports(EloquentCollection $operators, Carbon $start, Carbon $end): Collection
    {
        if ($operators->isEmpty()) {
            return collect();
        }

        $logs = BeneficiaryAuditLog::query()
            ->with(['person:id,first_name,last_name,person_code'])
            ->whereIn('user_id', $operators->pluck('id'))
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();

        $logsByUser = $logs->groupBy('user_id');
        $bucketMetadata = $this->buildBuckets($start, $end);

        return $operators->map(function (User $operator) use ($logsByUser, $bucketMetadata) {
            $operatorLogs = $logsByUser->get($operator->id, collect())->values();
            $created = $operatorLogs->where('action', 'created')->count();
            $updated = $operatorLogs->where('action', 'updated')->count();
            $deleted = $operatorLogs->where('action', 'deleted')->count();
            $trendData = $this->buildTrendData($operatorLogs, $bucketMetadata);
            $distribution = $this->buildDistribution($created, $updated, $deleted);
            $formattedLogs = $operatorLogs->map(fn (BeneficiaryAuditLog $log) => $this->formatLog($log));

            return [
                'operator' => $operator,
                'registrations' => $created,
                'edits' => $updated,
                'deletions' => $deleted,
                'totalActivities' => $operatorLogs->count(),
                'latestActivityAt' => optional($operatorLogs->first()?->created_at)?->diffForHumans(),
                'trendData' => $trendData,
                'distribution' => $distribution,
                'logs' => $formattedLogs->take(10)->values(),
                'registrationLogs' => $formattedLogs->where('action', 'created')->take(8)->values(),
                'editLogs' => $formattedLogs->where('action', 'updated')->take(8)->values(),
                'deletionLogs' => $formattedLogs->where('action', 'deleted')->take(8)->values(),
            ];
        });
    }

    private function formatLog(BeneficiaryAuditLog $log): array
    {
        $changedFields = collect($log->changed_fields ?? [])
            ->filter()
            ->values();

        return [
            'id' => $log->id,
            'action' => $log->action,
            'actionLabel' => $this->actionLabel($log->action),
            'actionColor' => $this->actionColor($log->action),
            'personName' => trim(implode(' ', array_filter([
                $log->person?->first_name,
                $log->person?->last_name,
            ]))) ?: 'پرونده بدون نام',
            'personCode' => $log->person?->person_code,
            'createdAt' => $log->created_at ? Jalalian::fromDateTime($log->created_at)->format('Y/m/d H:i') : null,
            'changedFields' => $changedFields->take(4)->all(),
            'changedFieldsCount' => $changedFields->count(),
        ];
    }

    public function updatedSelectedOperatorId(string $value): void
    {
        if (! filled($value)) {
            $this->selectedOperatorId = '';
        }
    }

    public function updatedReferenceYear(): void
    {
        $this->normalizeReferenceDay();
    }

    public function updatedReferenceMonth(): void
    {
        $this->normalizeReferenceDay();
    }

    private function resolveRange(): array
    {
        $reference = $this->referenceCarbon();

        return match ($this->timeframe) {
            'hour' => [$reference->copy()->startOfHour(), $reference->copy()->endOfHour()],
            'day' => [$reference->copy()->startOfDay(), $reference->copy()->endOfDay()],
            'year' => [$reference->copy()->startOfYear(), $reference->copy()->endOfYear()],
            default => [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()],
        };
    }

    private function buildBuckets(Carbon $start, Carbon $end): Collection
    {
        return match ($this->timeframe) {
            'hour' => collect(range(0, 11))->map(function (int $index) use ($start) {
                $bucketStart = $start->copy()->addMinutes($index * 5);
                return [
                    'key' => $bucketStart->format('Y-m-d H:i'),
                    'label' => $bucketStart->format('H:i'),
                    'start' => $bucketStart,
                    'end' => $bucketStart->copy()->addMinutes(4)->endOfMinute(),
                ];
            }),
            'day' => collect(range(0, 23))->map(function (int $hour) use ($start) {
                $bucketStart = $start->copy()->setTime($hour, 0, 0);
                return [
                    'key' => $bucketStart->format('Y-m-d H'),
                    'label' => $bucketStart->format('H:00'),
                    'start' => $bucketStart,
                    'end' => $bucketStart->copy()->endOfHour(),
                ];
            }),
            'year' => collect(range(1, 12))->map(function (int $month) use ($start) {
                $bucketStart = $start->copy()->month($month)->startOfMonth();
                return [
                    'key' => $bucketStart->format('Y-m'),
                    'label' => $this->monthOptions()[$month] ?? (string) $month,
                    'start' => $bucketStart,
                    'end' => $bucketStart->copy()->endOfMonth(),
                ];
            }),
            default => collect(range(1, $end->day))->map(function (int $day) use ($start) {
                $bucketStart = $start->copy()->day($day)->startOfDay();
                return [
                    'key' => $bucketStart->format('Y-m-d'),
                    'label' => (string) $day,
                    'start' => $bucketStart,
                    'end' => $bucketStart->copy()->endOfDay(),
                ];
            }),
        };
    }

    private function buildTrendData(Collection $logs, Collection $bucketMetadata): array
    {
        $counts = $bucketMetadata->map(function (array $bucket) use ($logs) {
            $total = $logs
                ->filter(fn (BeneficiaryAuditLog $log) => $log->created_at?->betweenIncluded($bucket['start'], $bucket['end']))
                ->count();

            return [
                'label' => $bucket['label'],
                'count' => $total,
            ];
        })->values();

        $max = max(1, $counts->max('count'));
        $points = $counts->map(function (array $bucket, int $index) use ($counts, $max) {
            $x = $counts->count() === 1 ? 50 : ($index / ($counts->count() - 1)) * 100;
            $y = 100 - (($bucket['count'] / $max) * 100);

            return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
        })->implode(' ');

        return [
            'buckets' => $counts->all(),
            'max' => $max,
            'points' => $points,
        ];
    }

    private function buildDistribution(int $created, int $updated, int $deleted): array
    {
        $total = max(1, $created + $updated + $deleted);

        return [
            [
                'label' => 'ثبت',
                'count' => $created,
                'width' => round(($created / $total) * 100, 1),
                'class' => 'bg-emerald-500',
            ],
            [
                'label' => 'ویرایش',
                'count' => $updated,
                'width' => round(($updated / $total) * 100, 1),
                'class' => 'bg-amber-500',
            ],
            [
                'label' => 'حذف',
                'count' => $deleted,
                'width' => round(($deleted / $total) * 100, 1),
                'class' => 'bg-rose-500',
            ],
        ];
    }

    private function syncReferenceWithNow(): void
    {
        $now = Jalalian::now();

        $this->referenceYear = $now->getYear();
        $this->referenceMonth = $now->getMonth();
        $this->referenceDay = $now->getDay();
        $this->referenceHour = $now->getHour();
    }

    private function rangeLabel(Carbon $start, Carbon $end): string
    {
        $startJalali = Jalalian::fromDateTime($start);
        $endJalali = Jalalian::fromDateTime($end);

        return match ($this->timeframe) {
            'hour' => $startJalali->format('Y/m/d H:00') . ' تا ' . $endJalali->format('H:59'),
            'day' => $startJalali->format('Y/m/d'),
            'year' => $startJalali->format('Y'),
            default => $startJalali->format('Y') . '/' . str_pad((string) $startJalali->getMonth(), 2, '0', STR_PAD_LEFT),
        };
    }

    private function referenceCarbon(): Carbon
    {
        $month = max(1, min(12, $this->referenceMonth));
        $day = max(1, min(CalendarUtils::jalaliMonthLength($this->referenceYear, $month), $this->referenceDay));
        $hour = max(0, min(23, $this->referenceHour));
        [$year, $gregorianMonth, $gregorianDay] = CalendarUtils::toGregorian($this->referenceYear, $month, $day);

        return Carbon::create($year, $gregorianMonth, $gregorianDay, $hour, 0, 0, config('app.timezone'));
    }

    private function normalizeReferenceDay(): void
    {
        $maxDay = CalendarUtils::jalaliMonthLength($this->referenceYear, $this->referenceMonth);
        $this->referenceDay = max(1, min($maxDay, $this->referenceDay));
    }

    private function yearOptions(): array
    {
        $currentYear = Jalalian::now()->getYear();
        $startYear = max(1300, $currentYear - 10);
        $endYear = $currentYear + 1;

        return array_combine(range($endYear, $startYear), range($endYear, $startYear));
    }

    private function monthOptions(): array
    {
        return [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];
    }

    private function dayOptions(): array
    {
        $maxDay = CalendarUtils::jalaliMonthLength($this->referenceYear, $this->referenceMonth);

        return array_combine(range(1, $maxDay), range(1, $maxDay));
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => 'ثبت پرونده',
            'updated' => 'ویرایش پرونده',
            'deleted' => 'حذف پرونده',
            default => $action,
        };
    }

    private function actionColor(string $action): string
    {
        return match ($action) {
            'created' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'updated' => 'bg-amber-100 text-amber-700 border-amber-200',
            'deleted' => 'bg-rose-100 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
