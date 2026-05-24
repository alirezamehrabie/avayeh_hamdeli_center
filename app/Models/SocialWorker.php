<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function scopeAutocompleteSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $workerQuery) use ($term) {
            $workerQuery->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('worker_code', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$term}%"])
                ->orWhereRaw("CONCAT_WS(' ', last_name, first_name) like ?", ["%{$term}%"]);
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

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
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
        $coveredDetails = $this->getCoveredPeopleDetails();
        $baseCount = count($coveredDetails);

        // ۲. دریافت اطلاعات سرپرستان (کد ملی + وضعیت فرزندان طلاق در منزل)
        $guardiansData = $this->guardians()
            ->get(['national_code', 'divorced_child_at_home']);

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

    public function getCoveredPeopleDetails(): array
    {
        $people = $this->people()
            ->with([
                'harmTypes' => fn($q) => $q->select('harm_types.id'),
                'guardian:id,national_code,first_name,last_name',
                'familyStatus:person_id,guardian_relation_type_id',
            ])
            ->get([
                'people.id',
                'people.first_name',
                'people.last_name',
                'people.person_code',
                'people.national_id',
                'people.father_name',
                'people.father_national_id',
                'people.mother_national_id',
                'people.guardian_id',
            ]);

        $detailsByNationalId = [];
        $excludedNationalIds = collect();
        $parentNationalIds = collect();

        foreach ($people as $person) {
            $beneficiaryNationalId = $this->normalizeNationalId($person->national_id);
            $fatherNationalId = $this->normalizeNationalId($person->father_national_id);
            $motherNationalId = $this->normalizeNationalId($person->mother_national_id);
            $guardianNationalId = $this->normalizeNationalId($person->guardian?->national_code);

            $beneficiaryFullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: '-';
            $guardianFullName = trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? '')) ?: '-';
            $sourceLabel = $beneficiaryFullName . ($person->person_code ? " ({$person->person_code})" : '');
            $guardianLabel = trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? ''));
            if ($guardianLabel === '') {
                $guardianLabel = $guardianNationalId ? "سرپرست {$guardianNationalId}" : 'سرپرست نامشخص';
            } elseif ($guardianNationalId) {
                $guardianLabel .= " ({$guardianNationalId})";
            }

            $harmTypeIds = $person->harmTypes->pluck('id')->toArray();
            $guardianRelationTypeId = (int) ($person->familyStatus?->guardian_relation_type_id ?? 0);
            $hasDivorceHarmType = in_array(7, $harmTypeIds);

            $shouldExcludeFather = in_array(1, $harmTypeIds)
                || in_array(5, $harmTypeIds)
                || ($hasDivorceHarmType && $guardianRelationTypeId !== 1);
            $shouldExcludeMother = in_array(2, $harmTypeIds) || in_array(5, $harmTypeIds);

            if ($beneficiaryNationalId) {
                $this->addCoveredDetail($detailsByNationalId, $beneficiaryNationalId, 'beneficiary', $beneficiaryFullName, $sourceLabel, $guardianLabel);
            }

            if ($fatherNationalId) {
                $parentNationalIds->push($fatherNationalId);
                if (!$shouldExcludeFather) {
                    $fatherName = trim((string)$person->father_name) ?: '-';
                    $this->addCoveredDetail(
                        $detailsByNationalId,
                        $fatherNationalId,
                        $this->resolveParentRelationshipRole('father', $hasDivorceHarmType, $guardianRelationTypeId),
                        $fatherName,
                        $sourceLabel,
                        $guardianLabel
                    );
                } else {
                    $excludedNationalIds->push($fatherNationalId);
                }
            }

            if ($motherNationalId) {
                $parentNationalIds->push($motherNationalId);
                if (!$shouldExcludeMother) {
                    $this->addCoveredDetail(
                        $detailsByNationalId,
                        $motherNationalId,
                        $this->resolveParentRelationshipRole('mother', $hasDivorceHarmType, $guardianRelationTypeId),
                        '-',
                        $sourceLabel,
                        $guardianLabel
                    );
                } else {
                    $excludedNationalIds->push($motherNationalId);
                }
            }

            if (!$guardianNationalId) {
                continue;
            }

            $fatherIncluded = $fatherNationalId && !$shouldExcludeFather;
            $motherIncluded = $motherNationalId && !$shouldExcludeMother;

            if ($fatherIncluded && $guardianNationalId === $fatherNationalId) {
                $this->addCoveredDetail($detailsByNationalId, $guardianNationalId, 'guardian', $guardianFullName, $sourceLabel, $guardianLabel);
                continue;
            }

            if ($motherIncluded && $guardianNationalId === $motherNationalId) {
                $this->addCoveredDetail($detailsByNationalId, $guardianNationalId, 'guardian', $guardianFullName, $sourceLabel, $guardianLabel);
                continue;
            }

            if ($excludedNationalIds->contains($guardianNationalId) || $parentNationalIds->contains($guardianNationalId)) {
                continue;
            }

            $this->addCoveredDetail($detailsByNationalId, $guardianNationalId, 'guardian', $guardianFullName, $sourceLabel, $guardianLabel);
        }

        return collect($detailsByNationalId)
            ->map(function (array $detail) {
                $detail['roles'] = collect($detail['roles'])->unique()->values()->all();
                $detail['sources'] = collect($detail['sources'])->unique()->values()->all();
                $detail['guardians'] = collect($detail['guardians'])->unique()->values()->all();
                $detail['guardian_group'] = $detail['guardians'][0] ?? '-';
                $detail['role_label'] = $this->buildRoleLabel($detail['roles']);
                return $detail;
            })
            ->sortBy([
                ['guardian_group', 'asc'],
                ['national_id', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function addCoveredDetail(
        array &$detailsByNationalId,
        string $nationalId,
        string $role,
        string $name,
        string $sourceLabel,
        string $guardianLabel
    ): void
    {
        if (!isset($detailsByNationalId[$nationalId])) {
            $detailsByNationalId[$nationalId] = [
                'national_id' => $nationalId,
                'name' => $name ?: '-',
                'roles' => [$role],
                'sources' => [$sourceLabel],
                'guardians' => [$guardianLabel],
            ];
            return;
        }

        $detailsByNationalId[$nationalId]['roles'][] = $role;
        $detailsByNationalId[$nationalId]['sources'][] = $sourceLabel;
        $detailsByNationalId[$nationalId]['guardians'][] = $guardianLabel;

        if (($detailsByNationalId[$nationalId]['name'] === '-' || empty($detailsByNationalId[$nationalId]['name'])) && $name && $name !== '-') {
            $detailsByNationalId[$nationalId]['name'] = $name;
        }
    }

    private function buildRoleLabel(array $roles): string
    {
        $rolesCollection = collect($roles)->unique();

        if ($rolesCollection->contains('father') && $rolesCollection->contains('guardian')) {
            return 'پدر/سرپرست';
        }

        if ($rolesCollection->contains('mother') && $rolesCollection->contains('guardian')) {
            return 'مادر/سرپرست';
        }

        if ($rolesCollection->contains('beneficiary') && $rolesCollection->contains('guardian')) {
            return 'مددجو/سرپرست';
        }

        $labels = [
            'beneficiary' => 'مددجو',
            'father' => 'پدر',
            'mother' => 'مادر',
            'father_divorced' => 'پدر / طلاق',
            'mother_divorced' => 'مادر / طلاق',
            'guardian' => 'سرپرست',
        ];

        return $rolesCollection
            ->map(fn(string $role) => $labels[$role] ?? $role)
            ->implode('/');
    }

    private function resolveParentRelationshipRole(string $parentRole, bool $isChildOfDivorce, int $guardianRelationTypeId): string
    {
        if ($isChildOfDivorce && $guardianRelationTypeId === 1 && $parentRole === 'mother') {
            return 'mother_divorced';
        }

        if ($isChildOfDivorce && $guardianRelationTypeId === 2 && $parentRole === 'father') {
            return 'father_divorced';
        }

        return $parentRole;
    }

    private function normalizeNationalId(?string $nationalId): ?string
    {
        if (!$nationalId) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', trim($nationalId));

        if (!$normalized || strlen($normalized) !== 10) {
            return null;
        }

        return $normalized;
    }


    /**
     * متد اختصاصی برای بروزرسانی آمار
     */
    public function refreshStatistics(): void
    {
        $this->updateStatistics();
    }

}
