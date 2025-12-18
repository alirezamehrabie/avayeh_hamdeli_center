<?php

namespace App\Models;

use App\Traits\HasJalaliBirthDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class Person extends Model
{
    use HasFactory, HasJalaliBirthDate;

    /**
     * لیست ماه‌های شمسی
     * کلید: عدد (ذخیره در دیتابیس)
     * مقدار: نام فارسی (فقط برای نمایش)
     */
    public static array $months = [
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

    protected $fillable = [
        'guardian_id',
        'first_name',
        'last_name',
        'national_id',
        'birth_day',
        'birth_month',
        'birth_year',
        'birth_date_full',
        'father_name',
        'father_national_id',
        'mother_national_id',
        'phone_number',
        'gender',
        'role',
        'sadaat_status',
        'sadaat_relation_id',
        'social_worker_id',
        'photo_id_card',
        'photo_birth_certificate',
        'has_disability',
        'disability_type_id',
        'disability_description',
        'person_code',
        'skills_description',
        'photo_live_capture',
    ];


    /**
     * ✅ فیلدهایی که فقط خواندنی هستند (ستون‌های مجازی)
     */
    protected $guarded = [
        'full_name', // ستون مجازی - فقط خواندنی
    ];


    protected $casts = [
        'birth_day' => 'integer',
        'birth_month' => 'integer',
        'birth_year' => 'integer',
        'has_disability' => 'boolean',
        'skills' => 'array',
    ];

    /**
     * اضافه کردن فیلدهای محاسباتی به خروجی مدل
     */
    protected $appends = [
        'birth_date',
        'birth_month_name',
        'age',
        'exact_age',
        'formatted_birth_date',
        'days_until_birthday',
        'is_birthday_today',
    ];


    // 🔹 ACCESSORS - تاریخ تولد و سن
    /**
     * ✅ Accessor برای دریافت تاریخ تولد کامل
     */

    public function getBirthDateAttribute(): ?string
    {
        if ($this->birth_year && $this->birth_month && $this->birth_day) {
            return sprintf(
                '%04d/%02d/%02d',
                $this->birth_year,
                $this->birth_month,
                $this->birth_day
            );
        }
        return null;
    }

    /**
     * ✅ Accessor برای نام ماه فارسی
     */
    public function getBirthMonthNameAttribute(): ?string
    {
        return self::$months[$this->birth_month] ?? null;
    }


    /**
     * ✅ Accessor برای سن ساده (فقط بر اساس سال)
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_year) {
            return null;
        }

        try {
            $currentYear = Jalalian::now()->getYear();
            return $currentYear - $this->birth_year;
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * ✅ Accessor برای سن دقیق (با در نظر گرفتن ماه و روز تولد)
     */
    public function getExactAgeAttribute(): ?int
    {
        if (!$this->birth_year || !$this->birth_month || !$this->birth_day) {
            return null;
        }

        try {
            $now = Jalalian::now();

            $currentYear = $now->getYear();
            $currentMonth = $now->getMonth();
            $currentDay = $now->getDay();

            $age = $currentYear - $this->birth_year;

            if ($currentMonth < $this->birth_month) {
                $age--;
            } elseif ($currentMonth == $this->birth_month && $currentDay < $this->birth_day) {
                $age--;
            }

            return $age;

        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * ✅ Accessor برای کد مددجو فرمت‌شده (با خط تیره)
     */
    public function getFormattedPersonCodeAttribute(): ?string
    {
        if (!$this->person_code || strlen($this->person_code) !== 6) {
            return $this->person_code;
        }

        return substr($this->person_code, 0, 2) . '-' . substr($this->person_code, 2);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔹 SCOPES - جستجو
    // ═══════════════════════════════════════════════════════════

    /**
     * ✅ Scope برای جستجوی نام کامل
     * حالا می‌توانیم مستقیماً روی ستون مجازی جستجو کنیم!
     */
    public function scopeSearchByFullName($query, string $name)
    {
        return $query->where('full_name', 'LIKE', "%{$name}%");
    }


    /**
     * ✅ Scope برای جستجوی پیشرفته
     */
    public function scopeSearch($query, string $search)
    {
        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'LIKE', "%{$search}%")
                ->orWhere('national_id', 'LIKE', "%{$search}%")
                ->orWhere('person_code', 'LIKE', "%{$search}%");
        });
    }


    /**
     * ✅ Scope برای فیلتر بر اساس سال تولد
     */
    public function scopeBornInYear($query, int $year)
    {
        return $query->where('birth_year', $year);
    }

    /**
     * ✅ Scope برای فیلتر بر اساس ماه تولد
     */
    public function scopeBornInMonth($query, int $month)
    {
        return $query->where('birth_month', $month);
    }

    /**
     * ✅ Scope برای فیلتر بر اساس بازه سنی
     */
    public function scopeAgeRange($query, int $minAge, int $maxAge)
    {
        try {
            $currentYear = Jalalian::now()->getYear();
        } catch (\Exception $e) {
            $currentYear = (int) jdate('Y');
        }

        $maxBirthYear = $currentYear - $minAge;
        $minBirthYear = $currentYear - $maxAge;

        return $query->whereBetween('birth_year', [$minBirthYear, $maxBirthYear]);
    }


    /**
     * ✅ Scope برای فیلتر افرادی که در این ماه متولد شده‌اند
     */
    public function scopeBirthdayThisMonth($query)
    {
        try {
            $currentMonth = Jalalian::now()->getMonth();
        } catch (\Exception $e) {
            $currentMonth = (int) jdate('m');
        }

        return $query->where('birth_month', $currentMonth);
    }


    /**
     * استخراج کد مددکار
     */
    public function getSocialWorkerCodeAttribute(): ?string
    {
        if ($this->person_code && strlen($this->person_code) >= 2) {
            return substr($this->person_code, 0, 2);
        }
        return null;
    }

    /**
     * استخراج شماره ترتیبی مددجو
     */
    public function getPersonSequenceAttribute(): ?int
    {
        if ($this->person_code && strlen($this->person_code) === 6) {
            return (int) substr($this->person_code, 2);
        }
        return null;
    }



    /**
     * تاریخ تولد با نام ماه فارسی
     * خروجی: "15 خرداد 1385"
     */
    public function getBirthDatePersianAttribute(): ?string
    {
        if (!$this->birth_year || !$this->birth_month || !$this->birth_day) {
            return null;
        }

        $monthName = self::$months[$this->birth_month] ?? '';

        return "{$this->birth_day} {$monthName} {$this->birth_year}";
    }



    /**
     * جستجو بر اساس تاریخ کامل
     */
    public function scopeBornOn($query, string $fullDate)
    {
        return $query->where('birth_date_full', $fullDate);
    }



    // 🔹 BOOT - رویدادهای مدل
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // تولید کد مددجو
            if (empty($model->person_code)) {
                $model->person_code = self::generateUniqueCode($model->social_worker_id);
            }
        });

        // ❌ بخش ذخیره full_name کاملاً حذف شد
        // ستون مجازی به صورت خودکار توسط دیتابیس محاسبه می‌شود
    }


    /**
     * تولید کد یکتا برای مددجو
     * فرمت: WWNNNN (کد مددکار 2 رقم + شماره ترتیبی 4 رقم)
     */
    protected static function generateUniqueCode(?int $socialWorkerId): string
    {
        $workerCode = str_pad($socialWorkerId ?? 0, 2, '0', STR_PAD_LEFT);

        $lastPerson = Person::where('person_code', 'LIKE', $workerCode . '%')
            ->orderByRaw('CAST(SUBSTRING(person_code, 3) AS UNSIGNED) DESC')
            ->first();

        if ($lastPerson) {
            $lastSeq = (int) substr($lastPerson->person_code, 2);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1000;
        }

        return $workerCode . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }





    // 🔹 RELATIONSHIPS - روابط

    public function disabilityType()
    {
        return $this->belongsTo(DisabilityType::class);
    }

    public function sadaatRelation()
    {
        return $this->belongsTo(SadaatRelation::class);
    }

    public function getSadaatRelationNameAttribute(): ?string
    {
        return $this->sadaatRelation?->name;
    }

    public function socialWorker()
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function familyStatus()
    {
        return $this->hasOne(FamilyStatus::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function residenceContact()
    {
        return $this->hasOne(Residence::class);
    }

    public function bankInfo()
    {
        return $this->hasOne(BankInfo::class);
    }

    public function educationInfo()
    {
        return $this->hasOne(Education::class);
    }

    public function supportCoverage()
    {
        return $this->hasOne(SupportCoverage::class);
    }

    public function needsLevel()
    {
        return $this->hasOne(NeedsLevel::class);
    }

    public function skills()
    {
        return $this->belongsToMany(
            Skill::class,
            'person_skill',
            'person_id',
            'skill_id'
        );
    }

    public function harmTypes()
    {
        return $this->belongsToMany(HarmType::class, 'harm_type_person');
    }
}
