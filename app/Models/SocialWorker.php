<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;

class SocialWorker extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'worker_code',
        'first_name',
        'last_name',
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
        'is_active',
    ];

    protected $guarded = [
        'full_name',
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
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.is_active', true);
        });
    }


    public static function generateNextWorkerCode(): int
    {
        $maxCode = self::withoutGlobalScope('active')->withTrashed()->max('worker_code');
        return $maxCode ? ($maxCode + 1) : 10;
    }


    public function deactivate(): bool
    {
        $this->is_active = false;
        $this->save();

        return (bool) $this->delete();
    }

    public function reactivate(): bool
    {
        $this->restore();
        $this->is_active = true;

        return $this->save();
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
        // ۱. بارگذاری مددجویان با فیلتر ستون‌ها و Eager Loading برای جلوگیری از N+1
        $people = $this->people()
            ->with(['harmTypes' => fn($q) => $q->select('harm_types.id')])
            ->get(['people.id', 'people.national_id', 'people.father_national_id', 'people.mother_national_id']);

        $uniqueNationalIds = collect();

        foreach ($people as $person) {
            // الف) کد ملی خود مددجو
            if ($person->national_id) {
                $uniqueNationalIds->push($person->national_id);
            }

            $harmTypeIds = $person->harmTypes->pluck('id')->toArray();

            $shouldExcludeFather = in_array(1, $harmTypeIds) || in_array(7, $harmTypeIds) || in_array(5, $harmTypeIds);
            // ب) بررسی وضعیت پدر (اگر فوت پدر ID=1 یا فرزند طلاق ID=7 نباشد)
            if (!$shouldExcludeFather) {
                if ($person->father_national_id) {
                    $uniqueNationalIds->push($person->father_national_id);
                }
            }

            $shouldExcludeMother = in_array(2, $harmTypeIds) || in_array(5, $harmTypeIds);
            // ج) بررسی وضعیت مادر (اگر فوت مادر ID=2 نباشد)
            if (!$shouldExcludeMother) {
                if ($person->mother_national_id) {
                    $uniqueNationalIds->push($person->mother_national_id);
                }
            }
        }

        // ۲. دریافت اطلاعات سرپرستان (کد ملی + وضعیت فرزندان طلاق در منزل)
        $guardiansData = $this->guardians()
            ->get(['national_code', 'divorced_child_at_home']);

        // ۳. افزودن کدهای ملی سرپرستان به لیست یکتا
        $guardiansData->pluck('national_code')->each(function ($code) use ($uniqueNationalIds) {
            if ($code) $uniqueNationalIds->push($code);
        });

        // ۴. محاسبه تعداد افراد یکتا (ثبت شده در دیتابیس)
        $baseCount = $uniqueNationalIds->filter()->unique()->count();

        // ۵. محاسبه مقدار افزایشی بابت "فرزندان طلاق در منزل سرپرست"
        // این افراد کد ملی ندارند، پس مستقیماً به عدد نهایی اضافه می‌شوند
        $extraCount = $guardiansData->sum(function ($guardian) {
            return match ($guardian->divorced_child_at_home) {
                'boy'  => 1,
                'girl' => 1,
                'both' => 2,
                default => 0, // شامل 'none' یا null
            };
        });

        // حاصل نهایی: افراد دارای کد ملی + افراد اظهار شده در منزل سرپرست
        return $baseCount + $extraCount;
    }


    /**
     * متد اختصاصی برای بروزرسانی آمار
     */
    public function refreshStatistics(): void
    {
        $this->updateStatistics();
    }

}
