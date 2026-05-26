<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Helpers\Morilog\Jalalian;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guardians';

    /**
     * فیلدهای قابل پر شدن
     * ✅ فیلدهای جدید تاریخ تولد شمسی
     */
    protected $fillable = [
        'social_worker_id',
        'guardian_code',
        'national_code',
        'first_name',
        'last_name',

        // فیلدهای جدید تاریخ تولد شمسی
        'guardian_birth_day',
        'guardian_birth_month',
        'guardian_birth_year',
        'guardian_birth_date_full',

        // سایر فیلدها
        'occupation_id',
        'job_type_id',
        'guardian_phone_number',
        'economic_decile',
        'children_count',
        'children_in_house',
        'extra_household_members',
        'insurance_status',
        'insurance_type_id',
        'divorced_child_at_home',
        'average_income',
        'any_family_employed',
        'any_family_employed_description',
        'has_vehicle',
        'vehicle_type_id',
        'vehicle_ownership_type',
        'deletion_reason',
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'guardian_birth_day' => 'integer',
        'guardian_birth_month' => 'integer',
        'guardian_birth_year' => 'integer',
        'economic_decile' => 'integer',
        'children_count' => 'integer',
        'children_in_house' => 'integer',
        'extra_household_members' => 'array',
        'insurance_status' => 'boolean',
        'any_family_employed' => 'boolean',
        'has_vehicle' => 'boolean',
        'average_income' => 'integer',
    ];

    public function people()
    {
        return $this->hasMany(Person::class, 'guardian_id');
    }

    public function socialWorker()
    {
        return $this->belongsTo(SocialWorker::class, 'social_worker_id');
    }

    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function insuranceType()
    {
        return $this->belongsTo(InsuranceType::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function residence()
    {
        return $this->hasOne(Residence::class, 'guardian_id');
    }

    public function contact()
    {
        return $this->hasOne(Contact::class, 'guardian_id');
    }

    public function bankInfo()
    {
        return $this->hasOne(BankInfo::class);
    }


    public static function generateNextGuardianCode(): int
    {
        return DB::transaction(function () {

            $row = DB::table('guardian_code_counter')->lockForUpdate()->first();

            $next = $row->last_code + 1;

            DB::table('guardian_code_counter')->update(['last_code' => $next]);

            return $next;
        });
    }


    // ═══════════════════════════════════════════════════════════════════
    // 🔹 Accessors - محاسبه سن سرپرست
    // ═══════════════════════════════════════════════════════════════════

    /**
     * محاسبه سن سرپرست (فقط بر اساس سال)
     *
     * @return int|null
     */
    public function getGuardianAgeAttribute(): ?int
    {
        if (!$this->guardian_birth_year) {
            return null;
        }

        $currentJalaliYear = (int) Jalalian::now()->getYear();
        return $currentJalaliYear - $this->guardian_birth_year;
    }

    /**
     * محاسبه سن دقیق سرپرست (با در نظر گرفتن روز و ماه)
     *
     * @return int|null
     */
    public function getGuardianExactAgeAttribute(): ?int
    {
        if (!$this->guardian_birth_year || !$this->guardian_birth_month || !$this->guardian_birth_day) {
            return null;
        }

        try {
            $now = Jalalian::now();
            $currentYear = $now->getYear();
            $currentMonth = $now->getMonth();
            $currentDay = $now->getDay();

            $age = $currentYear - $this->guardian_birth_year;

            // اگر هنوز تولدش نرسیده، یک سال کم کن
            if (
                $currentMonth < $this->guardian_birth_month ||
                ($currentMonth == $this->guardian_birth_month && $currentDay < $this->guardian_birth_day)
            ) {
                $age--;
            }

            return max(0, $age);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * نمایش تاریخ تولد فرمت‌شده
     *
     * @return string|null
     */
    public function getGuardianFormattedBirthDateAttribute(): ?string
    {
        if (!$this->guardian_birth_year || !$this->guardian_birth_month || !$this->guardian_birth_day) {
            return null;
        }

        return sprintf(
            '%04d/%02d/%02d',
            $this->guardian_birth_year,
            $this->guardian_birth_month,
            $this->guardian_birth_day
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // 🔹 Scopes - برای فیلتر و گزارش‌گیری
    // ═══════════════════════════════════════════════════════════════════

    /**
     * سرپرستانی که امروز تولدشان است
     */
    public function scopeGuardianBirthdayToday($query)
    {
        $today = Jalalian::now();
        return $query->where('guardian_birth_month', $today->getMonth())
            ->where('guardian_birth_day', $today->getDay());
    }

    /**
     * سرپرستانی که این ماه تولدشان است
     */
    public function scopeGuardianBirthdayThisMonth($query)
    {
        $currentMonth = Jalalian::now()->getMonth();
        return $query->where('guardian_birth_month', $currentMonth);
    }

    /**
     * سرپرستان متولد یک سال خاص
     */
    public function scopeGuardianBornInYear($query, int $year)
    {
        return $query->where('guardian_birth_year', $year);
    }

    /**
     * سرپرستان در بازه سنی خاص
     */
    public function scopeGuardianAgeBetween($query, int $minAge, int $maxAge)
    {
        $currentYear = (int) Jalalian::now()->getYear();
        $minYear = $currentYear - $maxAge;
        $maxYear = $currentYear - $minAge;

        return $query->whereBetween('guardian_birth_year', [$minYear, $maxYear]);
    }

    /**
     * سرپرستان بالای یک سن خاص
     */
    public function scopeGuardianOlderThan($query, int $age)
    {
        $currentYear = (int) Jalalian::now()->getYear();
        $maxYear = $currentYear - $age;

        return $query->where('guardian_birth_year', '<=', $maxYear);
    }

    /**
     * سرپرستان زیر یک سن خاص
     */
    public function scopeGuardianYoungerThan($query, int $age)
    {
        $currentYear = (int) Jalalian::now()->getYear();
        $minYear = $currentYear - $age;

        return $query->where('guardian_birth_year', '>=', $minYear);
    }

    public function refreshChildrenInHouse(): void
    {
        $childrenCount = (int) ($this->children_count ?? 0);
        $extraHouseholdCount = count($this->extra_household_members ?? []);
        $childrenFromPreviousMarriage = 0;

        $people = $this->people()->with(['familyStatus.guardianRelationType'])->get();

        foreach ($people as $person) {
            $familyStatus = $person->familyStatus;
            if (!$familyStatus || !$familyStatus->guardianRelationType) {
                continue;
            }

            $relationTypeTitle = trim((string) ($familyStatus->guardianRelationType->title ?? ''));
            $remarriedParent = strtolower(trim((string) ($familyStatus->remarried_parent ?? '')));

            $isFatherGuardian = in_array($relationTypeTitle, GuardianRelationType::fatherLikeTitles(), true);
            $isMotherGuardian = in_array($relationTypeTitle, GuardianRelationType::motherLikeTitles(), true);
            $isRelevantRemarriage = ($isFatherGuardian && in_array($remarriedParent, ['father', 'both'], true))
                || ($isMotherGuardian && in_array($remarriedParent, ['mother', 'both'], true));

            if (!$isRelevantRemarriage) {
                continue;
            }

            $childrenFromPreviousMarriage = max(
                $childrenFromPreviousMarriage,
                max(0, (int) ($familyStatus->children_from_previous_marriage ?? 0))
            );
        }

        $this->children_in_house = $childrenCount + $extraHouseholdCount + $childrenFromPreviousMarriage;
        $this->saveQuietly();
    }

    public static function refreshAllChildrenInHouse(): void
    {
        static::query()->chunkById(200, function ($guardians): void {
            foreach ($guardians as $guardian) {
                $guardian->refreshChildrenInHouse();
            }
        });
    }

    public function softDeleteFamily(string $reason): bool
    {
        return (bool) DB::transaction(function () use ($reason) {
            foreach ($this->people()->get() as $person) {
                $person->forceFill([
                    'deletion_reason' => $reason,
                ])->saveQuietly();

                $person->delete();
            }

            $this->forceFill([
                'deletion_reason' => $reason,
            ])->saveQuietly();

            return $this->delete();
        });
    }

    public function restoreFamily(): bool
    {
        return (bool) DB::transaction(function () {
            $restored = (bool) $this->restore();

            $this->forceFill([
                'deletion_reason' => null,
            ])->saveQuietly();

            $this->people()
                ->onlyTrashed()
                ->get()
                ->each(function (Person $person): void {
                    $person->restoreSupervision();
                });

            return $restored;
        });
    }
}
