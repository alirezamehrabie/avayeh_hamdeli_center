<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'support_organization_id', // کلید خارجی جدید
        'description',
        'other_organization_name', // فیلد متنی برای "سایر"
        'coverage_start_day',      // ✅ فیلد جدید
        'coverage_start_month',    // ✅ فیلد جدید
        'coverage_start_year',     // ✅ فیلد جدید
        'support_card_image',
        'coverage_start_date',
        'active_status'
    ];

    protected $casts = [
        'coverage_start_day' => 'integer',
        'coverage_start_month' => 'integer',
        'coverage_start_year' => 'integer',
    ];

    /**
     * نام ماه‌های شمسی
     */
    protected static array $persianMonths = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    // ==================== روابط ====================
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    // تعریف رابطه معکوس
    public function organization(): BelongsTo
    {
        return $this->belongsTo(SupportOrganization::class, 'support_organization_id');
    }


    // ==================== Accessors ====================

    /**
     * تاریخ شروع پوشش به فرمت کامل شمسی
     * مثال: "1402/05/15"
     */
    public function getCoverageStartDateFullAttribute(): ?string
    {
        if ($this->coverage_start_year && $this->coverage_start_month && $this->coverage_start_day) {
            return sprintf(
                '%04d/%02d/%02d',
                $this->coverage_start_year,
                $this->coverage_start_month,
                $this->coverage_start_day
            );
        }
        return null;
    }

    /**
     * تاریخ شروع پوشش به فرمت خوانا
     * مثال: "15 مرداد 1402"
     */
    public function getCoverageStartDateReadableAttribute(): ?string
    {
        if ($this->coverage_start_year && $this->coverage_start_month && $this->coverage_start_day) {
            $monthName = self::$persianMonths[$this->coverage_start_month] ?? '';
            return sprintf(
                '%d %s %d',
                $this->coverage_start_day,
                $monthName,
                $this->coverage_start_year
            );
        }
        return null;
    }
}
