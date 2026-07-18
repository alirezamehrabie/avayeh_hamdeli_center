<?php

namespace App\Helpers;

/**
 * Normalization helpers for Persian/Arabic text and digits, used before
 * searching or comparing user-entered identifiers.
 */
class PersianText
{
    /**
     * Convert Persian (۰-۹) and Arabic (٠-٩) digits to Latin digits.
     */
    public static function normalizeDigits(?string $value): string
    {
        $value = (string) $value;

        $value = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], range('0', '9'), $value);

        return str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], range('0', '9'), $value);
    }

    /**
     * Keep only digits (after Persian/Arabic digit conversion) — for national
     * ids, phone numbers and other numeric identifiers.
     */
    public static function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', self::normalizeDigits($value)) ?? '';
    }

    /**
     * Normalize Arabic characters to their Persian equivalents and collapse
     * whitespace, matching Person::normalizeSearchText().
     */
    public static function normalizeText(?string $value): string
    {
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة'], ['ی', 'ی', 'ک', 'ه', 'ه'], (string) $value);
        $value = str_replace(["\u{200C}", "\u{200D}", "\u{FEFF}", "\u{00A0}"], ' ', $value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }
}
