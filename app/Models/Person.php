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
        'guardian_role',
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
}
