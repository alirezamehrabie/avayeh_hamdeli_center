<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesIncompleteCasesQueueCache;
use App\Traits\HasJalaliBirthDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Morilog\Jalalian;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    use HasFactory, HasJalaliBirthDate, InvalidatesIncompleteCasesQueueCache, SoftDeletes;

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
        'normalized_first_name',
        'normalized_last_name',
        'normalized_full_name',
        'national_id',
        'shenasnameh_serial',
        'shenasnameh_series_number',
        'shenasnameh_series_letter',
        'birth_day',
        'birth_month',
        'birth_year',
        'father_name',
        'father_national_id',
        'mother_national_id',
        'phone_number',
        'gender',
        'role',
        'sadaat_status',
        'sadaat_relation_id',
        'social_worker_id',
        'created_by',
        'updated_by',
        'photo_id_card',
        'photo_birth_certificate',
        'has_disability',
        'disability_type_id',
        'disability_description',
        'person_code',
        'skills_description',
        'client_case_history',
        'profile_photo',
        'deletion_reason',
    ];


    /**
     * ✅ فیلدهایی که فقط خواندنی هستند (ستون‌های مجازی)
     */
    protected $guarded = [
        'full_name', // ستون مجازی - فقط خواندنی
        'birth_date_full', // ستون مجازی - فقط خواندنی
    ];


    protected $casts = [
        'birth_day' => 'integer',
        'birth_month' => 'integer',
        'birth_year' => 'integer',
        'has_disability' => 'boolean',
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
        'gender_label',
        'sadaat_status_label',
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

    public function getGenderLabelAttribute(): ?string
    {
        return match ($this->gender) {
            'male' => 'آقا / پسر',
            'female' => 'دختر / خانم',
            default => null,
        };
    }

    public function getSadaatStatusLabelAttribute(): ?string
    {
        return match ($this->sadaat_status) {
            'sadaat' => 'سادات',
            'general' => 'عام',
            default => null,
        };
    }

    public function getGuardianBeneficiaryWithCodeAttribute(): string
    {
        return $this->guardian?->beneficiary_with_code ?? '-';
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
            $currentYear = self::resolveJalalianNow()->getYear();
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
            $now = self::resolveJalalianNow();

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
        return $this->person_code;
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

    public static function normalizeSearchText(?string $value): string
    {
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة'], ['ی', 'ی', 'ک', 'ه', 'ه'], (string) $value);
        $value = str_replace(["\u{200C}", "\u{200D}"], ' ', $value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
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
            $currentYear = self::resolveJalalianNow()->getYear();
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
            $currentMonth = self::resolveJalalianNow()->getMonth();
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
        return $this->socialWorker ? str_pad($this->socialWorker->worker_code, 2, '0', STR_PAD_LEFT) : null;
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


    /**
     * تولید کد یکتا برای مددجو
     * فرمت: WWNNNN (کد مددکار 2 رقم + شماره ترتیبی 4 رقم)
     */
    public static function generateUniqueCode(): string
    {
        return DB::transaction(function () {
            $lastPersonCode = self::withTrashed()
                ->whereRaw('LENGTH(person_code) = 5')
                ->where('person_code', '>=', '14000')
                ->lockForUpdate()
                ->max('person_code');

            if ($lastPersonCode) {
                return (string) ($lastPersonCode + 1);
            }

            return '14000';
        });
    }



    public function restoreSupervision(): bool
    {
        $restored = (bool) $this->restore();

        $this->forceFill([
            'deletion_reason' => null,
        ])->saveQuietly();

        return $restored;
    }

    private static function resolveJalalianNow()
    {
        if (class_exists(Jalalian::class)) {
            return Jalalian::now();
        }

        $legacyClass = 'Morilog\\Jalali\\Jalalian';
        if (class_exists($legacyClass)) {
            return $legacyClass::now();
        }

        throw new \RuntimeException('No Jalalian implementation is available.');
    }



    // 🔹 BOOT - رویدادهای مدل
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $actorId = Auth::id();
            $model->syncNormalizedSearchFields();

            if (empty($model->person_code)) {
                // دیگر نیازی به ارسال social_worker_id نیست
                $model->person_code = self::generateUniqueCode();
            }

            if (empty($model->created_by) && $actorId) {
                $model->created_by = $actorId;
            }

            if (empty($model->updated_by) && $actorId) {
                $model->updated_by = $actorId;
            }
        });

        static::updating(function ($model) {
            $actorId = Auth::id();
            $model->syncNormalizedSearchFields();

            if ($actorId) {
                $model->updated_by = $actorId;
            }
        });

        static::created(function (self $model) {
            $model->storeAuditLog('created', [], $model->getAttributes());
        });

        static::updated(function (self $model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $beforeValues = [];
            $afterValues = [];

            foreach (array_keys($changes) as $field) {
                $beforeValues[$field] = $model->getOriginal($field);
                $afterValues[$field] = $model->getAttribute($field);
            }

            $model->storeAuditLog('updated', $beforeValues, $afterValues);
        });

        static::deleted(function (self $model) {
            $model->storeAuditLog('deleted', $model->getOriginal(), []);
        });
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

    public function serviceDeliveries()
    {
        return $this->hasMany(ServiceDelivery::class);
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

    public function education()
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(BeneficiaryAuditLog::class)->latest();
    }

    public function caseRecords()
    {
        return $this->hasMany(BeneficiaryCaseRecord::class)->latest('recorded_at')->latest();
    }

    public function activityAttendances()
    {
        return $this->hasMany(ActivityAttendance::class);
    }

    public function sponsorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(SponsorProfile::class, 'person_sponsor_profile')
            ->withTimestamps();
    }

    public function qrCodes(): MorphMany
    {
        return $this->morphMany(QrIdentity::class, 'subject', 'subject_type', 'subject_id');
    }

    public function activeQrCode(): MorphOne
    {
        return $this->morphOne(QrIdentity::class, 'subject', 'subject_type', 'subject_id')
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->latestOfMany();
    }

    private function storeAuditLog(string $action, array $beforeValues, array $afterValues): void
    {
        BeneficiaryAuditLog::create([
            'person_id' => $this->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changed_fields' => array_values(array_unique(array_merge(array_keys($beforeValues), array_keys($afterValues)))),
            'before_values' => empty($beforeValues) ? null : $beforeValues,
            'after_values' => empty($afterValues) ? null : $afterValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    private function syncNormalizedSearchFields(): void
    {
        $firstName = self::normalizeSearchText($this->first_name);
        $lastName = self::normalizeSearchText($this->last_name);

        $this->normalized_first_name = $firstName;
        $this->normalized_last_name = $lastName;
        $this->normalized_full_name = trim($firstName . ' ' . $lastName);
    }

    public static function hasNormalizedSearchColumns(): bool
    {
        return true;
    }
}
