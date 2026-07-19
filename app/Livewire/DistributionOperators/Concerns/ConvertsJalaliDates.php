<?php

namespace App\Livewire\DistributionOperators\Concerns;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;

/**
 * Shared Jalali (Persian) date handling for distribution-operator components.
 *
 * Keeps the single-day service distribution date normalization and Jalali →
 * Gregorian conversion in one place so the batch creator and its siblings stay
 * consistent (the same textual date drives both start and end distribution
 * columns).
 */
trait ConvertsJalaliDates
{
    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', $this->normalizeJalaliDate($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
    }

    protected function normalizeJalaliDate(?string $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        return (string) str($normalized)->before(' ')->trim();
    }
}
