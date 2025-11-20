<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'full_name',
        'national_id',
        'birth_day',
        'birth_month',
        'birth_year',
        'father_name',
        'father_national_id',
        'mother_national_id',
        'gender',
        'role',
        'sadaat_status',
        'sadaat_relation_id',
        'social_worker_id',
        'photo_id_card',
        'photo_birth_certificate',
        'has_disability',
        'disability_type',
        'disability_description',
        'person_code',
        'skills_description',
        'photo_live_capture',

    ];

    protected $casts = [
        'skills' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (Person::where('person_code', $code)->exists());

            $model->person_code = $code;
        });

        // === بخش جدید برای ذخیره خودکار نام کامل ===
        // رویداد saving هم هنگام create و هم update اجرا می‌شود
        static::saving(function ($model) {
            $model->full_name = $model->first_name . ' ' . $model->last_name;
        });
    }

    public function sadaatRelation()
    {
        return $this->belongsTo(SadaatRelation::class);
    }

    // اگر لازم است، اکسسوری برای نام نسب سادات:
    public function getSadaatRelationNameAttribute(): ?string
    {
        return $this->sadaatRelation?->name;
    }

    // Relationships
    public function socialWorker()
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function familyStatus()
    {
        return $this->hasOne(FamilyStatus::class);
    }

    public function guardianInfo()
    {
        return $this->hasOne(GuardianInfo::class);
    }

    public function residenceContact()
    {
        return $this->hasOne(ResidenceContact::class);
    }

    public function bankInfo()
    {
        return $this->hasOne(BankInfo::class);
    }

    public function educationInfo()
    {
        return $this->hasOne(EducationInfo::class);
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
            'person_skill',      // نام جدول pivot
            'person_id',         // کلید خارجی مدل جاری
            'skill_id'           // کلید خارجی مدل مرتبط
        );
    }

    // تعریف لیست ماه‌ها به صورت ثابت
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

    // یک Accessor برای اینکه وقتی مدل را می‌گیرید، نام ماه را هم داشته باشید
    public function getBirthMonthNameAttribute(): string
    {
        return self::$months[$this->birth_month] ?? 'نامشخص';
    }
}
