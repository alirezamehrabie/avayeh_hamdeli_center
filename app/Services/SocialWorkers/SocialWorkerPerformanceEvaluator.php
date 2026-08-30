<?php

namespace App\Services\SocialWorkers;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ارزیابی عملکرد مددکار بر پایه داده‌های واقعی تخصیص و تحویل خدمت.
 *
 * سنجهٔ اصلی، فاصلهٔ زمانی میان لحظه‌ای است که اپراتور یک تخصیص توزیع می‌سازد
 * (service_social_worker.created_at) و لحظه‌ای که مددکار نخستین تحویل همان
 * تخصیص را در پنل خود ثبت می‌کند (service_deliveries.created_at).
 */
class SocialWorkerPerformanceEvaluator
{
    /** بازه‌های پاسخ‌دهی؛ امتیاز هر بازه ثابت و برای مدیر قابل توضیح است. */
    public const RESPONSE_BUCKETS = [
        ['key' => 'within_day', 'label' => 'کمتر از ۲۴ ساعت', 'max_hours' => 24, 'score' => 100, 'accent' => 'emerald'],
        ['key' => 'within_three_days', 'label' => '۱ تا ۳ روز', 'max_hours' => 72, 'score' => 85, 'accent' => 'teal'],
        ['key' => 'within_week', 'label' => '۳ تا ۷ روز', 'max_hours' => 168, 'score' => 65, 'accent' => 'amber'],
        ['key' => 'within_two_weeks', 'label' => '۷ تا ۱۴ روز', 'max_hours' => 336, 'score' => 40, 'accent' => 'orange'],
        ['key' => 'over_two_weeks', 'label' => 'بیش از ۱۴ روز', 'max_hours' => null, 'score' => 15, 'accent' => 'rose'],
    ];

    /** تخصیص تازه‌تر از این مدت هنوز «سرِ موعد» نرسیده و در میانگین پاسخ‌دهی اثر ندارد. */
    public const GRACE_HOURS = 24;

    /** تخصیص باز و کهنه‌تر از این مدت، امتیاز پاسخ‌دهی صفر می‌گیرد. */
    public const ABANDONED_HOURS = 336;

    public const WEIGHTS = [
        'response' => 30,
        'fulfillment' => 30,
        'coverage' => 15,
        'punctuality' => 15,
        'consistency' => 10,
    ];

    public const CHART_MONTHS = 6;

    public function evaluate(SocialWorker $worker): array
    {
        $workerId = (int) $worker->getKey();

        $allocations = $this->allocationRows($workerId);
        $deliverySummary = $this->deliverySummary($workerId);

        if ($allocations->isEmpty() && (int) $deliverySummary['count'] === 0) {
            return $this->emptyResult();
        }

        $response = $this->responseMetric($allocations);
        $fulfillment = $this->fulfillmentMetric($allocations);
        $coverage = $this->coverageMetric($allocations);
        $punctuality = $this->punctualityMetric($workerId);
        $monthly = $this->monthlyActivity($workerId);
        $consistency = $this->consistencyMetric($monthly);

        $metrics = [
            'response' => $response,
            'fulfillment' => $fulfillment,
            'coverage' => $coverage,
            'punctuality' => $punctuality,
            'consistency' => $consistency,
        ];

        $totalScore = $this->weightedScore($metrics);

        return [
            'has_data' => true,
            'score' => $totalScore,
            'stars' => $this->stars($totalScore),
            'grade' => $this->grade($totalScore),
            'metrics' => $metrics,
            'response_distribution' => $response['distribution'],
            'monthly_activity' => $monthly,
            'delivery_summary' => $deliverySummary,
            'channel_breakdown' => $this->channelBreakdown($workerId),
            'open_allocations' => $this->openAllocations($allocations),
            'weights' => self::WEIGHTS,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'has_data' => false,
            'score' => 0.0,
            'stars' => 0.0,
            'grade' => ['label' => 'بدون داده', 'accent' => 'slate'],
            'metrics' => [],
            'response_distribution' => [],
            'monthly_activity' => [],
            'delivery_summary' => [
                'count' => 0,
                'value' => 0,
                'first_delivered_at' => null,
                'last_delivered_at' => null,
            ],
            'channel_breakdown' => [],
            'open_allocations' => [],
            'weights' => self::WEIGHTS,
        ];
    }

    /**
     * هر ردیف یک تخصیص (خدمت + دسته‌بندی) با زمان ساخت آن توسط اپراتور و
     * نخستین زمان ثبت تحویل توسط مددکار است.
     */
    protected function allocationRows(int $workerId): Collection
    {
        $allocations = ServiceWorkerAllocation::query()
            ->where('service_social_worker.social_worker_id', $workerId)
            ->join('services', 'services.id', '=', 'service_social_worker.service_id')
            ->whereNull('services.deleted_at')
            ->select([
                'service_social_worker.id',
                'service_social_worker.service_id',
                'service_social_worker.service_category_id',
                'service_social_worker.allocated_quantity',
                'service_social_worker.created_at',
            ])
            ->orderBy('service_social_worker.created_at')
            ->get();

        if ($allocations->isEmpty()) {
            return collect();
        }

        $deliveryStats = ServiceDelivery::query()
            ->where('social_worker_id', $workerId)
            ->whereIn('service_id', $allocations->pluck('service_id')->unique()->all())
            ->selectRaw('service_id, service_category_id')
            ->selectRaw('count(*) as deliveries_count')
            ->selectRaw('coalesce(sum(delivered_quantity), 0) as delivered_quantity')
            ->selectRaw('min(created_at) as first_registered_at')
            ->selectRaw('max(delivered_at) as last_delivered_at')
            ->groupBy('service_id', 'service_category_id')
            ->get()
            ->keyBy(fn ($row): string => $row->service_id.':'.$row->service_category_id);

        $serviceNames = Service::query()
            ->withTrashed()
            ->with('serviceName:id,name')
            ->whereIn('id', $allocations->pluck('service_id')->unique()->all())
            ->get(['id', 'name', 'service_name_id'])
            ->keyBy('id');

        $categoryNames = ServiceCategory::query()
            ->withTrashed()
            ->whereIn('id', $allocations->pluck('service_category_id')->unique()->all())
            ->pluck('name', 'id');

        $now = Carbon::now();

        return $allocations->map(function (ServiceWorkerAllocation $allocation) use ($deliveryStats, $serviceNames, $categoryNames, $now): array {
            $stats = $deliveryStats->get($allocation->service_id.':'.$allocation->service_category_id);
            $assignedAt = $allocation->created_at ? Carbon::parse($allocation->created_at) : null;
            $registeredAt = $stats?->first_registered_at ? Carbon::parse($stats->first_registered_at) : null;

            return [
                'service_id' => (int) $allocation->service_id,
                'service_category_id' => (int) $allocation->service_category_id,
                'service_label' => (string) ($serviceNames->get($allocation->service_id)?->name ?: 'خدمت حذف‌شده'),
                'category_label' => (string) ($categoryNames->get($allocation->service_category_id) ?: '-'),
                'allocated_quantity' => (float) $allocation->allocated_quantity,
                'delivered_quantity' => (float) ($stats->delivered_quantity ?? 0),
                'deliveries_count' => (int) ($stats->deliveries_count ?? 0),
                'assigned_at' => $assignedAt,
                'registered_at' => $registeredAt,
                'response_hours' => $registeredAt && $assignedAt
                    ? max(0.0, round($assignedAt->diffInRealSeconds($registeredAt, false) / 3600, 2))
                    : null,
                'pending_hours' => $registeredAt === null && $assignedAt
                    ? max(0.0, round($assignedAt->diffInRealSeconds($now, false) / 3600, 2))
                    : null,
            ];
        });
    }

    /**
     * سرعت واکنش: از لحظهٔ ساخت تخصیص توسط اپراتور تا نخستین ثبت تحویل توسط مددکار.
     * تخصیص‌های بی‌پاسخ که از مهلت گذشته‌اند با امتیاز صفر وارد میانگین می‌شوند تا
     * بی‌توجهی، امتیاز را بالا نگه ندارد.
     */
    protected function responseMetric(Collection $allocations): array
    {
        $distribution = collect(self::RESPONSE_BUCKETS)
            ->mapWithKeys(fn (array $bucket): array => [$bucket['key'] => 0])
            ->all();

        $scores = [];
        $answeredHours = [];
        $sameDayCount = 0;
        $pendingOverdue = 0;
        $pendingInGrace = 0;

        foreach ($allocations as $allocation) {
            if ($allocation['assigned_at'] === null) {
                continue;
            }

            if ($allocation['response_hours'] !== null) {
                $bucket = $this->bucketFor($allocation['response_hours']);
                $distribution[$bucket['key']]++;
                $scores[] = $bucket['score'];
                $answeredHours[] = $allocation['response_hours'];

                if ($allocation['response_hours'] <= 24) {
                    $sameDayCount++;
                }

                continue;
            }

            if (($allocation['pending_hours'] ?? 0) >= self::ABANDONED_HOURS) {
                $scores[] = 0;
                $pendingOverdue++;

                continue;
            }

            if (($allocation['pending_hours'] ?? 0) > self::GRACE_HOURS) {
                $scores[] = 20;
                $pendingOverdue++;

                continue;
            }

            $pendingInGrace++;
        }

        $answeredCount = count($answeredHours);
        $allocationsInScope = count($scores);

        return [
            'key' => 'response',
            'label' => 'سرعت واکنش به تخصیص',
            'description' => 'فاصلهٔ ساخت تخصیص توسط اپراتور تا نخستین ثبت تحویل توسط مددکار',
            'icon' => 'bi-lightning-charge',
            'accent' => 'amber',
            'score' => $allocationsInScope > 0 ? round(array_sum($scores) / $allocationsInScope, 1) : 0.0,
            'weight' => self::WEIGHTS['response'],
            'distribution' => collect(self::RESPONSE_BUCKETS)
                ->map(fn (array $bucket): array => [
                    'key' => $bucket['key'],
                    'label' => $bucket['label'],
                    'accent' => $bucket['accent'],
                    'count' => $distribution[$bucket['key']],
                    'share' => $answeredCount > 0
                        ? round($distribution[$bucket['key']] / $answeredCount * 100, 1)
                        : 0.0,
                ])
                ->values()
                ->all(),
            'stats' => [
                'answered' => $answeredCount,
                'median_hours' => $answeredCount > 0 ? $this->median($answeredHours) : null,
                'average_hours' => $answeredCount > 0 ? round(array_sum($answeredHours) / $answeredCount, 1) : null,
                'fastest_hours' => $answeredCount > 0 ? round(min($answeredHours), 1) : null,
                'same_day_share' => $answeredCount > 0 ? round($sameDayCount / $answeredCount * 100, 1) : 0.0,
                'pending_overdue' => $pendingOverdue,
                'pending_in_grace' => $pendingInGrace,
            ],
        ];
    }

    /**
     * ایفای تعهد: چه سهمی از مقدار تخصیص‌یافته واقعاً تحویل و ثبت شده است.
     */
    protected function fulfillmentMetric(Collection $allocations): array
    {
        $allocated = round($allocations->sum('allocated_quantity'), 2);
        $delivered = round($allocations->sum('delivered_quantity'), 2);
        $completed = $allocations
            ->filter(fn (array $row): bool => $row['allocated_quantity'] > 0
                && $row['allocated_quantity'] <= $row['delivered_quantity'] + 0.001)
            ->count();
        $untouched = $allocations->where('deliveries_count', 0)->count();

        $ratio = $allocated > 0 ? min(1.0, $delivered / $allocated) : 0.0;

        return [
            'key' => 'fulfillment',
            'label' => 'ایفای تعهد تحویل',
            'description' => 'نسبت مقدار تحویل‌شده به مقدار تخصیص‌یافته',
            'icon' => 'bi-check2-square',
            'accent' => 'emerald',
            'score' => round($ratio * 100, 1),
            'weight' => self::WEIGHTS['fulfillment'],
            'stats' => [
                'allocated_quantity' => $allocated,
                'delivered_quantity' => $delivered,
                'remaining_quantity' => round(max(0, $allocated - $delivered), 2),
                'completed_allocations' => $completed,
                'total_allocations' => $allocations->count(),
                'untouched_allocations' => $untouched,
            ],
        ];
    }

    /**
     * پوشش تخصیص: چه سهمی از تخصیص‌ها دست‌کم یک تحویل ثبت‌شده دارند.
     */
    protected function coverageMetric(Collection $allocations): array
    {
        $total = $allocations->count();
        $started = $allocations->where('deliveries_count', '>', 0)->count();

        return [
            'key' => 'coverage',
            'label' => 'پوشش تخصیص‌ها',
            'description' => 'سهم تخصیص‌هایی که مددکار حداقل یک تحویل برای آن‌ها ثبت کرده است',
            'icon' => 'bi-diagram-3',
            'accent' => 'sky',
            'score' => $total > 0 ? round($started / $total * 100, 1) : 0.0,
            'weight' => self::WEIGHTS['coverage'],
            'stats' => [
                'started_allocations' => $started,
                'total_allocations' => $total,
                'idle_allocations' => $total - $started,
            ],
        ];
    }

    /**
     * وقت‌شناسی: چه سهمی از تحویل‌ها در بازهٔ توزیع تعریف‌شدهٔ خدمت انجام شده است.
     */
    protected function punctualityMetric(int $workerId): array
    {
        $row = ServiceDelivery::query()
            ->where('service_deliveries.social_worker_id', $workerId)
            ->join('services', 'services.id', '=', 'service_deliveries.service_id')
            ->whereNotNull('services.distribution_end_date')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when service_deliveries.delivered_at <= services.distribution_end_date then 1 else 0 end) as on_time')
            ->selectRaw('sum(case when service_deliveries.delivered_at < services.distribution_start_date then 1 else 0 end) as early')
            ->first();

        $total = (int) ($row->total ?? 0);
        $onTime = (int) ($row->on_time ?? 0);

        return [
            'key' => 'punctuality',
            'label' => 'وقت‌شناسی در بازهٔ توزیع',
            'description' => 'سهم تحویل‌هایی که تا پایان بازهٔ توزیع خدمت ثبت شده‌اند',
            'icon' => 'bi-calendar-check',
            'accent' => 'indigo',
            // بدون بازهٔ پایان مشخص، معیاری برای دیرکرد وجود ندارد و امتیاز خنثی می‌ماند.
            'score' => $total > 0 ? round($onTime / $total * 100, 1) : 60.0,
            'weight' => self::WEIGHTS['punctuality'],
            'stats' => [
                'evaluated_deliveries' => $total,
                'on_time' => $onTime,
                'late' => $total - $onTime,
                'before_window' => (int) ($row->early ?? 0),
                'is_estimated' => $total === 0,
            ],
        ];
    }

    /**
     * تداوم فعالیت: در چند ماه از {@see self::CHART_MONTHS} ماه گذشته تحویل ثبت شده است.
     */
    protected function consistencyMetric(array $monthlyActivity): array
    {
        $months = count($monthlyActivity);
        $activeMonths = collect($monthlyActivity)->where('count', '>', 0)->count();

        return [
            'key' => 'consistency',
            'label' => 'تداوم فعالیت',
            'description' => 'تعداد ماه‌های فعال از '.$months.' ماه گذشته',
            'icon' => 'bi-activity',
            'accent' => 'violet',
            'score' => $months > 0 ? round($activeMonths / $months * 100, 1) : 0.0,
            'weight' => self::WEIGHTS['consistency'],
            'stats' => [
                'active_months' => $activeMonths,
                'total_months' => $months,
            ],
        ];
    }

    /**
     * فعالیت ماه‌به‌ماه بر پایهٔ تقویم شمسی برای نمودار میله‌ای مدیر.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function monthlyActivity(int $workerId): array
    {
        $windowStart = Jalalian::now()
            ->getFirstDayOfMonth()
            ->subMonths(self::CHART_MONTHS - 1)
            ->toCarbon()
            ->startOfDay();

        $deliveries = ServiceDelivery::query()
            ->where('social_worker_id', $workerId)
            ->where('delivered_at', '>=', $windowStart->toDateString())
            ->get(['delivered_at', 'delivered_total_value']);

        $buckets = [];
        $cursor = Jalalian::now()->getFirstDayOfMonth()->subMonths(self::CHART_MONTHS - 1);

        for ($index = 0; $index < self::CHART_MONTHS; $index++) {
            $key = $cursor->getYear().'-'.$cursor->getMonth();
            $buckets[$key] = [
                'label' => CalendarUtils::IRANIAN_MONTHS_NAME[$cursor->getMonth() - 1],
                'year' => $cursor->getYear(),
                'count' => 0,
                'value' => 0,
            ];

            $cursor = $cursor->addMonths(1);
        }

        foreach ($deliveries as $delivery) {
            if (! $delivery->delivered_at) {
                continue;
            }

            $jalali = Jalalian::fromDateTime($delivery->delivered_at);
            $key = $jalali->getYear().'-'.$jalali->getMonth();

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['count']++;
            $buckets[$key]['value'] += (int) $delivery->delivered_total_value;
        }

        return array_values($buckets);
    }

    protected function deliverySummary(int $workerId): array
    {
        $row = ServiceDelivery::query()
            ->where('social_worker_id', $workerId)
            ->selectRaw('count(*) as deliveries_count')
            ->selectRaw('coalesce(sum(delivered_total_value), 0) as delivered_value')
            ->selectRaw('min(delivered_at) as first_delivered_at')
            ->selectRaw('max(delivered_at) as last_delivered_at')
            ->first();

        return [
            'count' => (int) ($row->deliveries_count ?? 0),
            'value' => (int) ($row->delivered_value ?? 0),
            'first_delivered_at' => $row?->first_delivered_at
                ? Jalalian::fromDateTime($row->first_delivered_at)->format('Y/m/d')
                : null,
            'last_delivered_at' => $row?->last_delivered_at
                ? Jalalian::fromDateTime($row->last_delivered_at)->format('Y/m/d')
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function channelBreakdown(int $workerId): array
    {
        $counts = ServiceDelivery::query()
            ->where('social_worker_id', $workerId)
            ->selectRaw('delivery_channel, count(*) as deliveries_count')
            ->groupBy('delivery_channel')
            ->pluck('deliveries_count', 'delivery_channel');

        $total = (int) $counts->sum();

        if ($total === 0) {
            return [];
        }

        return collect(Service::DELIVERY_CHANNEL_OPTIONS)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
                'share' => round((int) ($counts[$key] ?? 0) / $total * 100, 1),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * تخصیص‌های بی‌پاسخ یا نیمه‌کاره برای اقدام مدیر.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function openAllocations(Collection $allocations): array
    {
        return $allocations
            ->filter(fn (array $row): bool => $row['registered_at'] === null
                || $row['allocated_quantity'] > $row['delivered_quantity'] + 0.001)
            ->sortByDesc(fn (array $row): float => $row['pending_hours'] ?? 0.0)
            ->take(8)
            ->map(fn (array $row): array => [
                'service_label' => $row['service_label'],
                'category_label' => $row['category_label'],
                'allocated_quantity' => $row['allocated_quantity'],
                'delivered_quantity' => $row['delivered_quantity'],
                'assigned_at' => $row['assigned_at']
                    ? Jalalian::fromDateTime($row['assigned_at'])->format('Y/m/d H:i')
                    : '-',
                'pending_days' => $row['pending_hours'] !== null
                    ? (int) floor($row['pending_hours'] / 24)
                    : null,
                'response_label' => $row['response_hours'] !== null
                    ? $this->humanizeHours($row['response_hours'])
                    : null,
            ])
            ->values()
            ->all();
    }

    protected function weightedScore(array $metrics): float
    {
        $weightSum = array_sum(self::WEIGHTS);
        $weighted = 0.0;

        foreach ($metrics as $metric) {
            $weighted += $metric['score'] * $metric['weight'];
        }

        return round($weighted / max($weightSum, 1), 1);
    }

    /** ستاره با گام نیم‌ستاره تا نمایش گرافیکی با امتیاز عددی هم‌خوان باشد. */
    protected function stars(float $score): float
    {
        return round($score / 100 * 5 * 2) / 2;
    }

    protected function grade(float $score): array
    {
        return match (true) {
            $score >= 85 => ['label' => 'عملکرد ممتاز', 'accent' => 'emerald'],
            $score >= 70 => ['label' => 'عملکرد خوب', 'accent' => 'teal'],
            $score >= 55 => ['label' => 'قابل قبول', 'accent' => 'amber'],
            $score >= 35 => ['label' => 'نیازمند پیگیری', 'accent' => 'orange'],
            default => ['label' => 'نیازمند مداخلهٔ مدیر', 'accent' => 'rose'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function bucketFor(float $hours): array
    {
        foreach (self::RESPONSE_BUCKETS as $bucket) {
            if ($bucket['max_hours'] === null || $hours <= $bucket['max_hours']) {
                return $bucket;
            }
        }

        return self::RESPONSE_BUCKETS[array_key_last(self::RESPONSE_BUCKETS)];
    }

    protected function humanizeHours(float $hours): string
    {
        if ($hours < 1) {
            return 'کمتر از یک ساعت';
        }

        if ($hours < 24) {
            return number_format(round($hours)).' ساعت';
        }

        return number_format(round($hours / 24, 1)).' روز';
    }

    /**
     * @param  array<int, float>  $values
     */
    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = (int) floor(($count - 1) / 2);

        if ($count % 2 === 1) {
            return round($values[$middle], 1);
        }

        return round(($values[$middle] + $values[$middle + 1]) / 2, 1);
    }
}
