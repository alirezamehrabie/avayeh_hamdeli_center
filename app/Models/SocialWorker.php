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


    public static function generateNextWorkerCode(): int
    {
        $maxCode = self::max('worker_code');
        return $maxCode ? ($maxCode + 1) : 10;
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

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
            'covered_households_count' => $this->guardians()->count(),
            'covered_children_count'   => $this->people()->count(),
            'covered_people_count'     => $this->calculateCoveredPeopleCount(),
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


    public function calculateCoveredPeopleCount(): int
    {
        // ۱. دریافت مددجویان به همراه ID نوع آسیب‌ها با بهینه‌ترین حالت کوئری
        // فقط ستون‌های مورد نیاز را انتخاب می‌کنیم تا حافظه RAM کمتر اشغال شود
        $people = $this->people()
            ->with(['harmTypes' => function($query) {
                $query->select('harm_types.id'); // فقط ID آسیب‌ها را نیاز داریم
            }])
            ->get(['people.id', 'people.national_id', 'people.father_national_id', 'people.mother_national_id']);

        $activeNationalIds = collect();

        foreach ($people as $person) {
            // الف) کد ملی خود مددجو
            if ($person->national_id) {
                $activeNationalIds->push($person->national_id);
            }

            // استخراج IDهای آسیب‌های ثبت شده برای این فرد
            $harmTypeIds = $person->harmTypes->pluck('id')->toArray();

            if (!in_array(1, $harmTypeIds) && !in_array(7, $harmTypeIds)) {
                if ($person->father_national_id) {
                    $activeNationalIds->push($person->father_national_id);
                }
            }

            // ج) بررسی وضعیت مادر (اگر ID=2 در لیست آسیب‌ها نباشد، مادر زنده فرض شده و شمارش می‌شود)
            if (!in_array(2, $harmTypeIds)) {
                if ($person->mother_national_id) {
                    $activeNationalIds->push($person->mother_national_id);
                }
            }
        }

        // ۲. دریافت کدهای ملی سرپرستان مرتبط با این مددکار
        $guardianNationalIds = $this->guardians()->pluck('national_code');

        // ۳. ترکیب تمام لیست‌ها، حذف مقادیر خالی و حذف تکراری‌ها
        // استفاده از unique() حیاتی است چون ممکن است چند فرزند، پدر یا مادر یکسانی داشته باشند
        return $activeNationalIds
            ->concat($guardianNationalIds)
            ->filter()
            ->unique()
            ->count();
    }

    /**
     * متد اختصاصی برای بروزرسانی آمار
     */
    public function refreshStatistics(): void
    {
        $this->updateStatistics();
    }

}
