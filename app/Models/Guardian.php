<?php

namespace App\Models;

use App\Helpers\Morilog\Jalalian;
use App\Models\Concerns\InvalidatesIncompleteCasesQueueCache;
use App\Services\Guardians\SoftDeleteGuardianFamily;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Guardian extends Model
{
    use HasFactory, InvalidatesIncompleteCasesQueueCache, SoftDeletes;

    public const DIVORCED_CHILD_NONE = 'none';

    public const DIVORCED_CHILD_BOY = 'boy';

    public const DIVORCED_CHILD_GIRL = 'girl';

    public const DIVORCED_CHILD_BOTH = 'both';

    public const DIVORCED_CHILD_VALUES = [
        self::DIVORCED_CHILD_NONE,
        self::DIVORCED_CHILD_BOY,
        self::DIVORCED_CHILD_GIRL,
        self::DIVORCED_CHILD_BOTH,
    ];

    private const MOTHER_DECEASED_HARM_TYPE_ID = 2;

    private const DIVORCE_SEPARATION_HARM_TYPE_ID = 7;

    protected $table = 'guardians';


    protected $fillable = [
        'social_worker_id',
        'guardian_code',
        'national_code',
        'first_name',
        'last_name',


        'guardian_birth_day',
        'guardian_birth_month',
        'guardian_birth_year',
        'guardian_birth_date_full',


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

    public function serviceDeliveries()
    {
        return $this->hasMany(ServiceDelivery::class);
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

    public static function generateNextGuardianCode(): int
    {
        return DB::transaction(function () {

            $row = DB::table('guardian_code_counter')->lockForUpdate()->first();

            $next = $row->last_code + 1;

            DB::table('guardian_code_counter')->update(['last_code' => $next]);

            return $next;
        });
    }

    public static function normalizeDivorcedChildAtHome(mixed $value): string
    {
        return match (strtolower(trim((string) $value))) {
            self::DIVORCED_CHILD_BOY, 'son', '1' => self::DIVORCED_CHILD_BOY,
            self::DIVORCED_CHILD_GIRL, 'daughter', '2' => self::DIVORCED_CHILD_GIRL,
            self::DIVORCED_CHILD_BOTH, '3', 'more' => self::DIVORCED_CHILD_BOTH,
            default => self::DIVORCED_CHILD_NONE,
        };
    }

    public static function divorcedChildAtHomeAliases(string $value): array
    {
        return match (self::normalizeDivorcedChildAtHome($value)) {
            self::DIVORCED_CHILD_BOY => [self::DIVORCED_CHILD_BOY, 'son', '1'],
            self::DIVORCED_CHILD_GIRL => [self::DIVORCED_CHILD_GIRL, 'daughter', '2'],
            self::DIVORCED_CHILD_BOTH => [self::DIVORCED_CHILD_BOTH, '3', 'more'],
            default => [self::DIVORCED_CHILD_NONE, null, ''],
        };
    }


    public function getGuardianAgeAttribute(): ?int
    {
        if (! $this->guardian_birth_year) {
            return null;
        }

        $currentJalaliYear = (int) Jalalian::now()->getYear();

        return $currentJalaliYear - $this->guardian_birth_year;
    }


    public function getGuardianExactAgeAttribute(): ?int
    {
        if (! $this->guardian_birth_year || ! $this->guardian_birth_month || ! $this->guardian_birth_day) {
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
     */
    public function getGuardianFormattedBirthDateAttribute(): ?string
    {
        if (! $this->guardian_birth_year || ! $this->guardian_birth_month || ! $this->guardian_birth_day) {
            return null;
        }

        return sprintf(
            '%04d/%02d/%02d',
            $this->guardian_birth_year,
            $this->guardian_birth_month,
            $this->guardian_birth_day
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getBeneficiaryWithCodeAttribute(): string
    {
        $fullName = $this->full_name;
        $guardianCode = $this->guardian_code ? 'کد خانوار: '.$this->guardian_code : null;

        return implode(' - ', array_filter([$fullName, $guardianCode])) ?: '-';
    }

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
        $guardianCount = 1;

        $people = $this->people()->with(['familyStatus.guardianRelationType'])->get();

        foreach ($people as $person) {
            $familyStatus = $person->familyStatus;
            if (! $familyStatus || ! $familyStatus->guardianRelationType) {
                continue;
            }

            $relationTypeTitle = trim((string) ($familyStatus->guardianRelationType->title ?? ''));
            $remarriedParent = strtolower(trim((string) ($familyStatus->remarried_parent ?? '')));

            $isFatherGuardian = in_array($relationTypeTitle, GuardianRelationType::fatherLikeTitles(), true);
            $isMotherGuardian = in_array($relationTypeTitle, GuardianRelationType::motherLikeTitles(), true);
            $isRelevantRemarriage = ($isFatherGuardian && in_array($remarriedParent, ['father', 'both'], true))
                || ($isMotherGuardian && in_array($remarriedParent, ['mother', 'both'], true));

            if (! $isRelevantRemarriage) {
                continue;
            }

            $childrenFromPreviousMarriage = max(
                $childrenFromPreviousMarriage,
                max(0, (int) ($familyStatus->children_from_previous_marriage ?? 0))
            );
        }

        $motherResidentCount = $this->getMotherResidentCount($people);

        $this->children_in_house = $childrenCount
            + $extraHouseholdCount
            + $childrenFromPreviousMarriage
            + $guardianCount
            + ($this->isGuardianAlsoMother($people) ? 0 : $motherResidentCount);
        $this->saveQuietly();
    }

    public function getMotherResidentCountAttribute(): int
    {
        return $this->getMotherResidentCount();
    }

    public function getHouseholdCompositionFormulaAttribute(): array
    {
        $childrenCount = (int) ($this->children_count ?? 0);
        $extraHouseholdCount = count($this->extra_household_members ?? []);
        $childrenFromPreviousMarriage = 0;
        $guardianCount = 1;
        $people = $this->relationLoaded('people')
            ? $this->people
            : $this->people()->with(['familyStatus.guardianRelationType', 'harmTypes:id'])->get();

        foreach ($people as $person) {
            $familyStatus = $person->familyStatus;
            if (! $familyStatus || ! $familyStatus->guardianRelationType) {
                continue;
            }

            $relationTypeTitle = trim((string) ($familyStatus->guardianRelationType->title ?? ''));
            $remarriedParent = strtolower(trim((string) ($familyStatus->remarried_parent ?? '')));

            $isFatherGuardian = in_array($relationTypeTitle, GuardianRelationType::fatherLikeTitles(), true);
            $isMotherGuardian = in_array($relationTypeTitle, GuardianRelationType::motherLikeTitles(), true);
            $isRelevantRemarriage = ($isFatherGuardian && in_array($remarriedParent, ['father', 'both'], true))
                || ($isMotherGuardian && in_array($remarriedParent, ['mother', 'both'], true));

            if (! $isRelevantRemarriage) {
                continue;
            }

            $childrenFromPreviousMarriage = max(
                $childrenFromPreviousMarriage,
                max(0, (int) ($familyStatus->children_from_previous_marriage ?? 0))
            );
        }

        $motherResidentCount = $this->getMotherResidentCount($people);

        return [
            'beneficiaries' => $childrenCount,
            'previous_marriage_members' => $childrenFromPreviousMarriage,
            'non_beneficiaries' => $extraHouseholdCount,
            'guardian' => $guardianCount,
            'mother' => $motherResidentCount,
            'mother_counted_separately' => $this->isGuardianAlsoMother($people) ? 0 : $motherResidentCount,
            'final_residents' => $childrenCount + $childrenFromPreviousMarriage + $extraHouseholdCount + $guardianCount + ($this->isGuardianAlsoMother($people) ? 0 : $motherResidentCount),
        ];
    }

    public function getMotherResidentCount($people = null): int
    {
        $people ??= $this->relationLoaded('people')
            ? $this->people
            : $this->people()->with(['familyStatus.guardianRelationType', 'harmTypes:id,title'])->get();

        if ($people->isEmpty()) {
            return 0;
        }

        $hasMotherDeceased = $people->contains(function ($person) {
            return $person->harmTypes->contains(function ($harmType) {
                $title = trim((string) ($harmType->title ?? ''));

                return (int) $harmType->id === self::MOTHER_DECEASED_HARM_TYPE_ID || $title === 'فوت مادر';
            });
        });

        if ($hasMotherDeceased) {
            return 0;
        }

        $hasDivorceOrSeparation = $people->contains(function ($person) {
            if ((bool) ($person->familyStatus?->mother_left_home ?? false)) {
                return true;
            }

            return $person->harmTypes->contains(function ($harmType) {
                $title = trim((string) ($harmType->title ?? ''));

                return (int) $harmType->id === self::DIVORCE_SEPARATION_HARM_TYPE_ID || str_contains($title, 'طلاق');
            });
        });

        if ($hasDivorceOrSeparation) {
            return 0;
        }

        $hasMotherGuardian = $people->contains(function ($person) {
            $relationTypeTitle = trim((string) ($person->familyStatus?->guardianRelationType?->title ?? ''));

            return in_array($relationTypeTitle, GuardianRelationType::motherLikeTitles(), true);
        });

        if ($hasMotherGuardian) {
            return 1;
        }

        $hasMotherIdentity = $people->contains(function ($person) {
            return trim((string) $person->mother_national_id) !== '';
        });

        return $hasMotherIdentity ? 1 : 0;
    }

    private function isGuardianAlsoMother($people): bool
    {
        $guardianNationalId = preg_replace('/\D+/', '', trim((string) $this->national_code));

        if ($guardianNationalId === '') {
            return false;
        }

        $motherNationalId = $people
            ->map(fn ($person) => preg_replace('/\D+/', '', trim((string) $person->mother_national_id)))
            ->first(fn ($nationalId) => $nationalId !== '');

        return $motherNationalId !== null && $motherNationalId === $guardianNationalId;
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
        return app(SoftDeleteGuardianFamily::class)->handle($this, $reason);
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
