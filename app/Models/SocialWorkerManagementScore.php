<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialWorkerManagementScore extends Model
{
    use HasFactory;

    public const DEFAULT_SCORE = 10;

    public const MIN_SCORE = 1;

    public const MAX_SCORE = 10;

    protected $fillable = [
        'social_worker_id',
        'year',
        'month',
        'score',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'social_worker_id' => 'integer',
            'year' => 'integer',
            'month' => 'integer',
            'score' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function socialWorker(): BelongsTo
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * ضریب ارزیابی مدیریت برای یک مددکار در سال و ماه مشخص؛ در صورت عدم ثبت، مقدار پیش‌فرض ۱۰ برمی‌گردد.
     */
    public static function getScoreFor(int $workerId, int $year, int $month): int
    {
        return static::query()
            ->forPeriod($year, $month)
            ->where('social_worker_id', $workerId)
            ->value('score') ?? self::DEFAULT_SCORE;
    }

    /**
     * ضرایب ارزیابی مدیریت برای چند مددکار در یک کوئری تجمیعی؛ موارد ثبت‌نشده با مقدار ۱۰ برمی‌گردند.
     *
     * @param  array<int, int>  $workerIds
     * @return array<int, int> [social_worker_id => score]
     */
    public static function getScoresForMany(array $workerIds, int $year, int $month): array
    {
        if (empty($workerIds)) {
            return [];
        }

        $recorded = static::query()
            ->forPeriod($year, $month)
            ->whereIn('social_worker_id', $workerIds)
            ->pluck('score', 'social_worker_id')
            ->all();

        $result = [];
        foreach ($workerIds as $workerId) {
            $result[$workerId] = (int) ($recorded[$workerId] ?? self::DEFAULT_SCORE);
        }

        return $result;
    }

    /**
     * ضریب ارزیابی مدیریت برای یک مددکار در یک بازه زمانی شامل چند ماه جلالی.
     *
     * @param  array<array{year: int, month: int}>  $yearMonths
     */
    public static function getScoreForRange(int $workerId, array $yearMonths): float
    {
        $scores = static::getScoresForRange([$workerId], $yearMonths);

        return $scores[$workerId] ?? (float) self::DEFAULT_SCORE;
    }

    /**
     * محاسبه ضرایب ارزیابی مدیریت برای چند مددکار در یک بازه ماه‌های جلالی؛
     * در صورت وجود چند ماه، میانگین ضرایب ماه‌های پوشش‌داده‌شده محاسبه می‌شود.
     *
     * @param  array<int, int>  $workerIds
     * @param  array<array{year: int, month: int}>  $yearMonths
     * @return array<int, float> [social_worker_id => score]
     */
    public static function getScoresForRange(array $workerIds, array $yearMonths): array
    {
        if (empty($workerIds)) {
            return [];
        }

        if (count($yearMonths) === 1) {
            $single = $yearMonths[0];
            $scores = static::getScoresForMany($workerIds, $single['year'], $single['month']);

            return array_map('floatval', $scores);
        }

        if (empty($yearMonths)) {
            $recordedAverages = static::query()
                ->whereIn('social_worker_id', $workerIds)
                ->groupBy('social_worker_id')
                ->selectRaw('social_worker_id, AVG(score) as avg_score')
                ->pluck('avg_score', 'social_worker_id')
                ->all();

            $result = [];
            foreach ($workerIds as $workerId) {
                $result[$workerId] = isset($recordedAverages[$workerId])
                    ? round((float) $recordedAverages[$workerId], 1)
                    : (float) self::DEFAULT_SCORE;
            }

            return $result;
        }

        $query = static::query()->whereIn('social_worker_id', $workerIds);

        $query->where(function (Builder $sub) use ($yearMonths) {
            foreach ($yearMonths as $ym) {
                $sub->orWhere(function (Builder $item) use ($ym) {
                    $item->where('year', $ym['year'])->where('month', $ym['month']);
                });
            }
        });

        $records = $query->get(['social_worker_id', 'year', 'month', 'score']);

        $scoresByWorker = [];
        foreach ($records as $record) {
            $key = $record->year.'-'.$record->month;
            $scoresByWorker[$record->social_worker_id][$key] = (int) $record->score;
        }

        $totalMonths = count($yearMonths);
        $result = [];

        foreach ($workerIds as $workerId) {
            $workerScores = $scoresByWorker[$workerId] ?? [];
            $sum = 0;

            foreach ($yearMonths as $ym) {
                $key = $ym['year'].'-'.$ym['month'];
                $sum += $workerScores[$key] ?? self::DEFAULT_SCORE;
            }

            $result[$workerId] = round($sum / $totalMonths, 1);
        }

        return $result;
    }
}
