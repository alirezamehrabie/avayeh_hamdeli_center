<?php

namespace Tests\Unit;

use App\Services\Ai\BeneficiaryCaseMetricsBuilder;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BeneficiaryCaseMetricsBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_builds_counts_values_and_latest_event_ages_from_deterministic_data(): void
    {
        CarbonImmutable::setTestNow('2026-07-15 12:00:00');

        $services = collect([
            (object) ['delivered_at' => CarbonImmutable::parse('2026-07-01')],
            (object) ['delivered_at' => CarbonImmutable::parse('2026-07-10')],
        ]);
        $activities = collect([
            (object) ['checked_in_at' => CarbonImmutable::parse('2026-07-12 09:00:00')],
        ]);
        $records = collect([
            (object) ['recorded_at' => CarbonImmutable::parse('2026-07-05')],
            (object) ['recorded_at' => CarbonImmutable::parse('2026-07-14')],
        ]);

        $metrics = (new BeneficiaryCaseMetricsBuilder)->build(
            $services,
            $activities,
            $records,
            [
                'direct_services_count' => 40,
                'direct_services_value' => 12000000,
                'family_services_count' => 15,
                'family_services_value' => 3500000,
                'activity_attendances_count' => 22,
                'manual_records_count' => 8,
                'manual_records_amount' => 900000,
            ],
        );

        $this->assertSame(40, $metrics['services']['direct_count']);
        $this->assertSame(15, $metrics['services']['household_count']);
        $this->assertSame(55, $metrics['services']['total_count']);
        $this->assertSame(15500000, $metrics['services']['total_value_rials']);
        $this->assertSame('2026-07-10', $metrics['services']['latest_date']);
        $this->assertSame(5, $metrics['services']['days_since_latest']);
        $this->assertSame(22, $metrics['activities']['attendance_count']);
        $this->assertSame('2026-07-12', $metrics['activities']['latest_date']);
        $this->assertSame(3, $metrics['activities']['days_since_latest']);
        $this->assertSame(8, $metrics['case_records']['count']);
        $this->assertSame(900000, $metrics['case_records']['total_amount_rials']);
        $this->assertSame('2026-07-14', $metrics['case_records']['latest_date']);
        $this->assertSame(1, $metrics['case_records']['days_since_latest']);
    }

    public function test_it_returns_null_dates_when_an_event_group_is_empty(): void
    {
        $metrics = (new BeneficiaryCaseMetricsBuilder)->build(
            collect(),
            collect(),
            collect(),
            [],
        );

        $this->assertSame(0, $metrics['services']['total_count']);
        $this->assertNull($metrics['services']['latest_date']);
        $this->assertNull($metrics['services']['days_since_latest']);
        $this->assertNull($metrics['activities']['latest_date']);
        $this->assertNull($metrics['case_records']['latest_date']);
    }
}
