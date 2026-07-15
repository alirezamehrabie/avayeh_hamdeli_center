<?php

namespace App\Services\Ai;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BeneficiaryCaseMetricsBuilder
{
    public function build(
        Collection $serviceDeliveries,
        Collection $activityAttendances,
        Collection $caseRecords,
        array $totals,
    ): array {
        $latestServiceDate = $this->latestDate($serviceDeliveries, 'delivered_at');
        $latestActivityDate = $this->latestDate($activityAttendances, 'checked_in_at');
        $latestCaseRecordDate = $this->latestDate($caseRecords, 'recorded_at');

        $directServicesCount = (int) ($totals['direct_services_count'] ?? 0);
        $householdServicesCount = (int) ($totals['family_services_count'] ?? 0);
        $directServicesValue = (int) ($totals['direct_services_value'] ?? 0);
        $householdServicesValue = (int) ($totals['family_services_value'] ?? 0);

        return [
            'services' => [
                'direct_count' => $directServicesCount,
                'household_count' => $householdServicesCount,
                'total_count' => $directServicesCount + $householdServicesCount,
                'direct_value_rials' => $directServicesValue,
                'household_value_rials' => $householdServicesValue,
                'total_value_rials' => $directServicesValue + $householdServicesValue,
                'latest_date' => $latestServiceDate?->toDateString(),
                'days_since_latest' => $this->daysSince($latestServiceDate),
            ],
            'activities' => [
                'attendance_count' => (int) ($totals['activity_attendances_count'] ?? 0),
                'latest_date' => $latestActivityDate?->toDateString(),
                'days_since_latest' => $this->daysSince($latestActivityDate),
            ],
            'case_records' => [
                'count' => (int) ($totals['manual_records_count'] ?? 0),
                'total_amount_rials' => (int) ($totals['manual_records_amount'] ?? 0),
                'latest_date' => $latestCaseRecordDate?->toDateString(),
                'days_since_latest' => $this->daysSince($latestCaseRecordDate),
            ],
        ];
    }

    private function latestDate(Collection $items, string $attribute): ?CarbonInterface
    {
        return $items
            ->pluck($attribute)
            ->filter()
            ->sortDesc()
            ->first();
    }

    private function daysSince(?CarbonInterface $date): ?int
    {
        if (! $date) {
            return null;
        }

        if ($date->isFuture()) {
            return 0;
        }

        return (int) $date->copy()->startOfDay()->diffInDays(CarbonImmutable::today());
    }
}
