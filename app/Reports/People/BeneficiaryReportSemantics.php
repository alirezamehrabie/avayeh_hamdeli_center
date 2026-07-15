<?php

namespace App\Reports\People;

use App\Helpers\Morilog\CalendarUtils;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class BeneficiaryReportSemantics
{
    public const CURRENT_WORKER_SOURCE = 'guardians.social_worker_id';

    public const SERVICE_RECIPIENT_SCOPE = 'person_and_current_guardian';

    public const MONTHS_WITHOUT_SERVICE_OPERATOR = 'gte';

    /**
     * Worker reporting is based on the household's current assignment.
     * Assignment history is not available in the current schema.
     */
    public function includeHistoricalWorkers(Builder|Relation $query): Builder|Relation
    {
        $query->withoutGlobalScope('active');
        $query->withTrashed();

        return $query;
    }

    public function historicalWorkerQuery(): Builder
    {
        return $this->includeHistoricalWorkers(SocialWorker::query());
    }

    public function workerLabel(?SocialWorker $worker): string
    {
        if (! $worker) {
            return '-';
        }

        $label = trim($worker->full_name);

        if ($worker->trashed() || ! $worker->is_active) {
            $label .= ' (غیرفعال)';
        }

        return $label !== '' ? $label : '-';
    }

    /**
     * "At least N months without service" includes the cutoff date itself.
     * A delivery after this date means the beneficiary is not yet N months stale.
     */
    public function serviceRecencyCutoff(int $months): string
    {
        return now()
            ->startOfDay()
            ->subMonthsNoOverflow($months)
            ->toDateString();
    }

    public function isValidJalaliDate(int $year, int $month, int $day): bool
    {
        return CalendarUtils::checkDate($year, $month, $day);
    }

    /**
     * @param  array{int, int, int}  $date
     */
    public function formatJalaliDate(array $date): string
    {
        return sprintf('%04d/%02d/%02d', $date[0], $date[1], $date[2]);
    }
}
