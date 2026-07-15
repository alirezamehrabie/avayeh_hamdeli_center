<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class SensitiveTextRedactor
{
    public function redact(?string $value, array $knownSensitiveValues = [], ?int $limit = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[ایمیل حذف شد]', $value) ?? $value;
        $value = preg_replace('/\bIR[\d\s-]{20,32}\b/iu', '[شماره شبا حذف شد]', $value) ?? $value;
        $value = preg_replace('/(?<!\p{N})(?:\+?98|0098|0)?9[\p{N}\s-]{9,13}(?!\p{N})/u', '[شماره تماس حذف شد]', $value) ?? $value;
        $value = preg_replace('/(?<!\p{N})(?:\p{N}[\s-]?){10,16}(?!\p{N})/u', '[شناسه حذف شد]', $value) ?? $value;

        $sensitiveValues = collect($knownSensitiveValues)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter(fn (string $item): bool => mb_strlen($item) >= 3)
            ->unique()
            ->sortByDesc(fn (string $item): int => mb_strlen($item))
            ->values()
            ->all();

        if ($sensitiveValues !== []) {
            $value = str_ireplace($sensitiveValues, '[نام یا شناسه حذف شد]', $value);
        }

        return $limit === null ? $value : Str::limit($value, $limit, '...');
    }
}
