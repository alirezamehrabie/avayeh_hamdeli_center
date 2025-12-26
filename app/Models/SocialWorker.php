<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations;

class SocialWorker extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'worker_code',
        'first_name',
        'last_name',
        'full_name',
        // 'full_name', // اگر مجازی باشد در fillable نیازی نیست، اما اگر معمولی باشد اضافه شود
        'birth_day',
        'birth_month',
        'birth_year',
        'birth_date_full',
        'national_id',
        'id_number',
        'family_members_count',
        'mobile',
        'photo_path',
        'start_day',
        'start_month',
        'start_year',
        'start_date_full',
        'district_id',
        'occupation_id',
        'academic_level_id',
        'covered_people_count',
        'covered_households_count',
        'covered_children_count',
        'substitute_first_name',
        'substitute_last_name',
        'substitute_mobile',
    ];


    protected $casts = [
        'worker_code' => 'integer',
        'birth_day' => 'integer',
        'birth_month' => 'integer',
        'birth_year' => 'integer',
        'start_day' => 'integer',
        'start_month' => 'integer',
        'start_year' => 'integer',
        'family_members_count' => 'integer',
        'covered_people_count' => 'integer',
        'covered_households_count' => 'integer',
        'covered_children_count' => 'integer',
    ];

    /**
     * محاسبه کد بعدی مددکار (شروع از ۱۰)
     */
    public static function generateNextWorkerCode(): int
    {
        // پیدا کردن بزرگترین کد موجود در دیتابیس
        $maxCode = self::max('worker_code');

        // اگر کدی وجود داشت، یکی به آن اضافه کن؛ در غیر این صورت از ۱۰ شروع کن
        return $maxCode ? ($maxCode + 1) : 10;
    }


    /**
     * دریافت نام کامل (در صورتی که ستون مجازی در دیتابیس ساخته نشده باشد)
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    /**
     * مددجویان تحت پوشش این مددکار
     */
//    public function people(): HasMany
//    {
//        return $this->hasMany(Person::class, 'social_worker_id');
//    }


    public function people()
    {
        // استفاده از hasManyThrough برای دسترسی مستقیم به مددجویان از طریق سرپرست
        return $this->hasManyThrough(
            Person::class,
            Guardian::class,
            'social_worker_id', // کلید خارجی در جدول guardians
            'guardian_id',      // کلید خارجی در جدول people
            'id',               // کلید محلی در جدول social_workers
            'id'                // کلید محلی در جدول guardians
        );
    }

    public function updateStatistics()
    {
        // تعداد خانوارها
        $householdsCount = $this->guardians()->count();

        // تعداد مددجویان (با رعایت Soft Delete اگر در مدل Person فعال باشد)
        $childrenCount = $this->people()->count();

        $this->update([
            'covered_households_count' => $householdsCount,
            'covered_children_count' => $childrenCount,
        ]);
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class, 'social_worker_id');
    }


    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }

    public function academicLevel() {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id');
    }

    /**
     * متد اختصاصی برای بروزرسانی آمار
     */
    public function refreshStatistics()
    {
        $this->update([
            'covered_households_count' => $this->guardians()->count(),
            'covered_children_count' => $this->people()->count(),
        ]);
    }

}
