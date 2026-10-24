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
}
