<?php

namespace App\Livewire\DistributionOperators\Concerns;

/**
 * Shared quantity formatting for distribution-operator allocation components.
 *
 * Centralizes how service quantities render for a given unit: weight-based
 * units keep two decimals, countable units render as whole numbers. Keeping the
 * unit list and rounding rules here avoids drift between the batch creator and
 * the allocation editor.
 */
trait FormatsServiceQuantities
{
    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    protected function formatQuantityForUnit(string|int|float|null $value, string $unit): string
    {
        $number = (float) ($value ?? 0);

        return number_format($number, $this->isDecimalQuantityUnit($unit) ? 2 : 0, '.', '');
    }

    public function isDecimalQuantityUnit(string $unit): bool
    {
        return in_array($unit, ['kilogram', 'gram', 'kg', 'g'], true);
    }
}
