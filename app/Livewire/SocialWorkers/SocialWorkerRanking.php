<?php

namespace App\Livewire\SocialWorkers;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\SocialWorker;
use App\Models\SocialWorkerManagementScore;
use App\Services\SocialWorkers\SocialWorkerPerformanceEvaluator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class SocialWorkerRanking extends Component
{
    /** حداکثر تعداد مددکاران در نمایش رتبه‌بندی */
    public const RANKING_LIMIT = 100;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all'; // all, active, inactive

    #[Url(as: 'year')]
    public int $rankingYear = 0;

    #[Url(as: 'month')]
    public int $rankingMonth = 0;

    #[Url(as: 'mode', except: 'monthly')]
    public string $rankingMode = 'monthly'; // 'monthly' or 'custom'

    public string $rankingDateFrom = '';

    public string $rankingDateTo = '';

    public ?string $rankingSuccessMessage = null;

    public ?string $rankingErrorMessage = null;

    public ?int $selectedWorkerId = null;

    public bool $showWorkerModal = false;

    public string $workerModalTab = 'performance';

    public array $workerModalTabs = [
        'profile' => ['label' => 'اطلاعات پرونده', 'icon' => 'bi-person-vcard'],
        'performance' => ['label' => 'ارزیابی عملکرد', 'icon' => 'bi-speedometer2'],
    ];

    public function mount(): void
    {
        $now = Jalalian::now();

        if (request()->has('mode') && in_array(request('mode'), ['monthly', 'custom'], true)) {
            $this->rankingMode = request('mode');
        }

        if (empty($this->rankingYear)) {
            $this->rankingYear = (int) $now->getYear();
        }
        if (empty($this->rankingMonth)) {
            $this->rankingMonth = (int) $now->getMonth();
        }

        // در صورت ارسال پارامترهای سال یا ماه از طریق کوئری استرینگ
        if (request()->has('year')) {
            $this->rankingYear = (int) request('year');
        }
        if (request()->has('month')) {
            $this->rankingMonth = max(1, min(12, (int) request('month')));
        }

        $this->rankingMonth = max(1, min(12, $this->rankingMonth));

        // محدودسازی پیش‌فرض بازه ارزیابی به ماه جاری شمسی در بارگذاری اولیه
        if ($this->rankingMode === 'monthly') {
            $this->applyMonthToDateRange();
        }
    }

    public function setRankingMode(string $mode): void
    {
        $this->closeWorkerModal();
        if (in_array($mode, ['monthly', 'custom'], true)) {
            $this->rankingMode = $mode;
            if ($mode === 'monthly') {
                $this->applyMonthToDateRange();
            }
            unset($this->ranking);
        }
    }

    public function selectRankingMonth(int $yearOrMonth, ?int $month = null): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'monthly';

        if ($month === null) {
            $this->rankingMonth = max(1, min(12, $yearOrMonth));
        } else {
            $this->rankingYear = $yearOrMonth;
            $this->rankingMonth = max(1, min(12, $month));
        }

        $this->applyMonthToDateRange();
        unset($this->ranking);
    }

    public function updatedRankingYear(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'monthly';
        $this->applyMonthToDateRange();
        unset($this->ranking);
    }

    public function updatedRankingMonth(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'monthly';
        $this->applyMonthToDateRange();
        unset($this->ranking);
    }

    public function updatedRankingDateFrom(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'custom';
        unset($this->ranking);
    }

    public function updatedRankingDateTo(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'custom';
        unset($this->ranking);
    }

    public function updatedSearch(): void
    {
        $this->closeWorkerModal();
        unset($this->ranking);
    }

    public function updatedStatus(): void
    {
        $this->closeWorkerModal();
        unset($this->ranking);
    }

    public function setRankingToCurrentMonth(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'monthly';
        $now = Jalalian::now();
        $this->selectRankingMonth($now->getYear(), $now->getMonth());
    }

    public function setRankingToPreviousMonth(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'monthly';
        $prev = Jalalian::now()->subMonths(1);
        $this->selectRankingMonth($prev->getYear(), $prev->getMonth());
    }

    public function applyMonthToDateRange(): void
    {
        $monthDays = (new Jalalian($this->rankingYear, $this->rankingMonth, 1))->getMonthDays();
        $this->rankingDateFrom = sprintf('%04d/%02d/01', $this->rankingYear, $this->rankingMonth);
        $this->rankingDateTo = sprintf('%04d/%02d/%02d', $this->rankingYear, $this->rankingMonth, $monthDays);
    }

    public function clearRankingRange(): void
    {
        $this->closeWorkerModal();
        $this->rankingMode = 'custom';
        $this->rankingDateFrom = '';
        $this->rankingDateTo = '';
        unset($this->ranking);
    }

    public function updateManagementScore(int $workerId, int|string $score): void
    {
        if (! auth()->user()?->isManager()) {
            abort(403, 'تنها نقش مدیریت مجاز به تغییر ضریب ارزیابی است.');
        }

        if ($this->rankingMode !== 'monthly') {
            $this->rankingErrorMessage = 'تغییر ضریب ارزیابی مدیریت فقط در حالت ارزیابی ماهانه امکان‌پذیر است.';
            $this->rankingSuccessMessage = null;

            return;
        }

        $score = (int) $score;
        if ($score < 1 || $score > 10) {
            $this->rankingErrorMessage = 'ضریب مدیریت باید عددی صحیح بین ۱ تا ۱۰ باشد.';
            $this->rankingSuccessMessage = null;

            return;
        }

        SocialWorker::query()->findOrFail($workerId);

        $record = SocialWorkerManagementScore::firstOrNew([
            'social_worker_id' => $workerId,
            'year' => $this->rankingYear,
            'month' => $this->rankingMonth,
        ]);

        if (! $record->exists) {
            $record->created_by = auth()->id();
        }

        $record->score = $score;
        $record->updated_by = auth()->id();
        $record->save();

        unset($this->ranking);
        $this->rankingErrorMessage = null;
        $this->rankingSuccessMessage = 'ضریب ارزیابی مدیریت با موفقیت بروزرسانی شد.';
    }

    public function showWorkerInfo(int $workerId, ?int $year = null, ?int $month = null): void
    {
        if ($year !== null && $month !== null) {
            $targetYear = (int) $year;
            $targetMonth = max(1, min(12, (int) $month));
            if ($this->rankingYear !== $targetYear || $this->rankingMonth !== $targetMonth) {
                $this->rankingYear = $targetYear;
                $this->rankingMonth = $targetMonth;
                $this->applyMonthToDateRange();
            }
        }

        $this->selectedWorkerId = $workerId;
        $this->showWorkerModal = true;
        $this->workerModalTab = 'performance';

        unset($this->selectedWorker);
        unset($this->workerPerformance);
    }

    public function loadWorkerDetails(int $workerId, ?int $year = null, ?int $month = null): void
    {
        $this->showWorkerInfo($workerId, $year, $month);
    }

    public function setWorkerModalTab(string $tab): void
    {
        if (isset($this->workerModalTabs[$tab])) {
            $this->workerModalTab = $tab;
            unset($this->selectedWorker);
        }
    }

    public function closeWorkerModal(): void
    {
        $this->showWorkerModal = false;
        $this->selectedWorkerId = null;
        $this->workerModalTab = 'performance';
        unset($this->selectedWorker);
        unset($this->workerPerformance);
    }

    #[Computed]
    public function rankingRange(): ?array
    {
        $from = $this->normalizedDateInput($this->rankingDateFrom);
        $to = $this->normalizedDateInput($this->rankingDateTo);

        if ($from === null && $to === null) {
            return null;
        }

        if ($from !== null && $to !== null && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * استخراج لیست ماه‌های جلالی پوشش‌داده‌شده توسط بازه زمانی انتخابی.
     *
     * @return array<array{year: int, month: int}>
     */
    public function getMonthsCoveredByRange(): array
    {
        $range = $this->rankingRange();

        if ($range === null || $range['from'] === null || $range['to'] === null) {
            return [];
        }

        try {
            $fromJalali = Jalalian::fromDateTime($range['from']);
            $toJalali = Jalalian::fromDateTime($range['to']);
        } catch (\Throwable) {
            return [];
        }

        $startYear = (int) $fromJalali->getYear();
        $startMonth = (int) $fromJalali->getMonth();
        $endYear = (int) $toJalali->getYear();
        $endMonth = (int) $toJalali->getMonth();

        if ($startYear > $endYear || ($startYear === $endYear && $startMonth > $endMonth)) {
            return [];
        }

        $months = [];
        $currentYear = $startYear;
        $currentMonth = $startMonth;

        while ($currentYear < $endYear || ($currentYear === $endYear && $currentMonth <= $endMonth)) {
            $months[] = [
                'year' => $currentYear,
                'month' => $currentMonth,
            ];

            $currentMonth++;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }
        }

        return $months;
    }

    #[Computed]
    public function rankingRangeLabel(): ?string
    {
        $range = $this->rankingRange();

        if ($range === null) {
            return null;
        }

        $from = $range['from'] ? Jalalian::fromDateTime($range['from'])->format('Y/m/d') : 'آغاز';
        $to = $range['to'] ? Jalalian::fromDateTime($range['to'])->format('Y/m/d') : 'امروز';

        return "بازهٔ ارزیابی: {$from} تا {$to}";
    }

    protected function normalizedDateInput(string $date): ?Carbon
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date) === 1) {
            [$year, $month, $day] = array_map('intval', explode('/', $date));

            if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
                return null;
            }

            try {
                return Carbon::instance(Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $year, $month, $day))->toCarbon());
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * رتبه‌بندی عملکرد مددکاران همراه با اعمال ضریب مدیریت ماه انتخاب‌شده.
     */
    #[Computed]
    public function ranking(): array
    {
        $query = SocialWorker::query();

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->withoutGlobalScope('active')->where('is_active', false);
        } else {
            $query->withoutGlobalScope('active');
        }

        if (trim($this->search) !== '') {
            $term = trim($this->search);
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('worker_code', 'like', "%{$term}%");
            });
        }

        $workerIds = $query->reorder()->pluck('social_workers.id')->all();
        $total = count($workerIds);

        if ($total === 0) {
            return [
                'rows' => [],
                'evaluated' => 0,
                'active_evaluated' => 0,
                'total' => 0,
                'average_score' => 0.0,
                'truncated' => false,
                'limit' => self::RANKING_LIMIT,
            ];
        }

        if ($this->rankingMode === 'monthly') {
            $managementScores = SocialWorkerManagementScore::getScoresForMany(
                $workerIds,
                $this->rankingYear,
                $this->rankingMonth
            );
        } else {
            $coveredMonths = $this->getMonthsCoveredByRange();
            $managementScores = SocialWorkerManagementScore::getScoresForRange(
                $workerIds,
                $coveredMonths
            );
        }

        $evaluations = app(SocialWorkerPerformanceEvaluator::class)->rankMany(
            $workerIds,
            $this->rankingRange(),
            $managementScores
        );

        $workers = SocialWorker::query()
            ->withoutGlobalScope('active')
            ->withTrashed()
            ->whereIn('id', $workerIds)
            ->get(['id', 'first_name', 'last_name', 'worker_code', 'is_active'])
            ->keyBy('id');

        $rows = collect($workerIds)
            ->map(function (int $workerId) use ($evaluations, $workers, $managementScores): ?array {
                $worker = $workers->get($workerId);

                if (! $worker) {
                    return null;
                }

                $evaluation = $evaluations[$workerId] ?? null;

                return [
                    'id' => $workerId,
                    'full_name' => trim((string) $worker->full_name) ?: 'بدون نام',
                    'worker_code' => $worker->worker_code ?: '-',
                    'is_active' => (bool) $worker->is_active,
                    'has_data' => (bool) ($evaluation['has_data'] ?? false),
                    'base_score' => (float) ($evaluation['base_score'] ?? $evaluation['score'] ?? 0.0),
                    'management_score' => (float) ($evaluation['management_score'] ?? $managementScores[$workerId] ?? 10.0),
                    'score' => (float) ($evaluation['score'] ?? 0.0),
                    'stars' => (float) ($evaluation['stars'] ?? 0.0),
                    'grade' => $evaluation['grade'] ?? ['label' => 'بدون داده', 'accent' => 'slate'],
                    'deliveries' => (int) ($evaluation['delivery_summary']['count'] ?? 0),
                    'allocations' => (int) ($evaluation['allocations_count'] ?? 0),
                    'median_hours' => $evaluation['response_median_hours'] ?? null,
                    'pending_overdue' => (int) ($evaluation['pending_overdue'] ?? 0),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row): array => [$row['has_data'] ? 1 : 0, $row['score'], $row['deliveries']])
            ->values();

        $evaluatedCount = $rows->where('has_data', true)->count();
        $avgScore = $evaluatedCount > 0 ? round($rows->where('has_data', true)->avg('score'), 1) : 0.0;

        return [
            'rows' => $rows->take(self::RANKING_LIMIT)->values()->all(),
            'evaluated' => $rows->count(),
            'active_evaluated' => $evaluatedCount,
            'total' => $total,
            'average_score' => $avgScore,
            'truncated' => $rows->count() > self::RANKING_LIMIT,
            'limit' => self::RANKING_LIMIT,
        ];
    }

    #[Computed]
    public function selectedWorker(): ?SocialWorker
    {
        if (! $this->selectedWorkerId) {
            return null;
        }

        $with = [
            'district:id,name',
            'occupation:id,name',
            'academicLevel:id,title',
        ];

        if ($this->workerModalTab === 'profile') {
            $with['guardians'] = fn ($q) => $q
                ->select('id', 'social_worker_id', 'first_name', 'last_name', 'guardian_code')
                ->withCount('people')
                ->orderByDesc('id')
                ->limit(60);
        }

        return SocialWorker::query()
            ->withoutGlobalScope('active')
            ->withTrashed()
            ->withCount(['guardians', 'people', 'services', 'serviceDeliveries'])
            ->withSum('serviceDeliveries as service_deliveries_sum_delivered_total_value', 'delivered_total_value')
            ->with($with)
            ->find($this->selectedWorkerId);
    }

    #[Computed]
    public function workerPerformance(): ?array
    {
        $worker = $this->selectedWorker;

        if (! $worker) {
            return null;
        }

        if ($this->rankingMode === 'monthly') {
            $managementScore = SocialWorkerManagementScore::getScoreFor($worker->id, $this->rankingYear, $this->rankingMonth);
        } else {
            $coveredMonths = $this->getMonthsCoveredByRange();
            $managementScore = SocialWorkerManagementScore::getScoreForRange($worker->id, $coveredMonths);
        }

        return app(SocialWorkerPerformanceEvaluator::class)->evaluate($worker, $this->rankingRange(), $managementScore);
    }

    #[Computed]
    public function isEndOfJalaliMonth(): bool
    {
        $now = Jalalian::now();
        $totalDays = $now->getMonthDays();
        $day = $now->getDay();

        return ($totalDays - $day) <= 6;
    }

    #[Computed]
    public function currentJalaliMonthName(): string
    {
        return Jalalian::now()->format('%B');
    }

    #[Computed]
    public function rankingMonthName(): string
    {
        $names = $this->jalaliMonths;

        return $names[$this->rankingMonth] ?? (string) $this->rankingMonth;
    }

    #[Computed]
    public function jalaliMonths(): array
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

    #[Computed]
    public function jalaliYears(): array
    {
        $current = Jalalian::now()->getYear();

        return range($current - 2, $current + 1);
    }

    public function render()
    {
        return view('livewire.social-workers.social-worker-ranking');
    }
}
