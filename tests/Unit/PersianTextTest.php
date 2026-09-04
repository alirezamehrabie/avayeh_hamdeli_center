<?php

namespace Tests\Unit;

use App\Helpers\PersianText;
use App\Models\Person;
use PHPUnit\Framework\TestCase;

class PersianTextTest extends TestCase
{
    public function test_normalize_text_folds_every_alef_variant_to_plain_alef(): void
    {
        $this->assertSame('ارمان', PersianText::normalizeText('آرمان'));
        $this->assertSame('ارمان', PersianText::normalizeText('أرمان'));
        $this->assertSame('ابراهیم', PersianText::normalizeText('إبراهیم'));
        $this->assertSame('ارمان', PersianText::normalizeText('ٱرمان'));
    }

    public function test_normalize_text_folds_arabic_letters_to_persian_equivalents(): void
    {
        $this->assertSame('علی رضایی', PersianText::normalizeText('علي رضايي'));
        $this->assertSame('زهره', PersianText::normalizeText('زهرة'));
        $this->assertSame('کریم', PersianText::normalizeText('كريم'));
    }

    public function test_normalize_text_replaces_joiners_and_collapses_whitespace(): void
    {
        $this->assertSame('محمد حسین', PersianText::normalizeText("محمد\u{200C}حسین"));
        $this->assertSame('محمد حسین', PersianText::normalizeText("محمد\u{00A0}حسین"));
        $this->assertSame('محمد حسین', PersianText::normalizeText('  محمد   حسین '));
    }

    public function test_person_search_normalization_folds_alef_the_same_way(): void
    {
        $this->assertSame('ارمان', Person::normalizeSearchText('آرمان'));
        $this->assertSame('محمدرضا', Person::normalizeCompactSearchText('محمد رضا'));
        $this->assertSame('ارمانرضا', Person::normalizeCompactSearchText('آرمان‌رضا'));
    }

    public function test_digits_only_converts_persian_and_arabic_digits(): void
    {
        $this->assertSame('09136476949', PersianText::digitsOnly('۰۹۱۳۶۴۷۶۹۴۹'));
        $this->assertSame('12345', PersianText::digitsOnly('١٢٣٤٥'));
    }
}
