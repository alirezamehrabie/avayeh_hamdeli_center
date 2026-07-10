<?php

namespace App\Livewire\People;

use App\Models\Contact;
use App\Models\Person;
use App\Services\People\BeneficiaryCompletenessCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class IncompleteCasesQueue extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public int $perPage = 12;

    public string $selectedReason = 'all';

    public string $selectedSeverity = 'all';

    protected ?Collection $peopleCache = null;

    protected array $incompleteStateCache = [];

    protected ?Collection $personContactsCache = null;

    protected ?Collection $guardianContactsCache = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function updatingSelectedReason(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSeverity(): void
    {
        $this->resetPage();
    }

    public function selectReason(string $reason): void
    {
        $this->selectedReason = array_key_exists($reason, $this->reasonOptions()) ? $reason : 'all';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->selectedReason = 'all';
        $this->selectedSeverity = 'all';
        $this->resetPage();
    }

    public function getIncompletePeopleProperty(): LengthAwarePaginator
    {
        $filteredPeople = $this->filteredIncompletePeopleCollection();
        $page = Paginator::resolveCurrentPage() ?: 1;
        $total = $filteredPeople->count();
        $items = $filteredPeople->forPage($page, $this->perPage)->values();

        return new LengthAwarePaginatorInstance(
            $items,
            $total,
            $this->perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function getSummaryProperty(): array
    {
        $reasonCounts = $this->catalogFields()
            ->pluck('key')
            ->mapWithKeys(fn (string $key): array => [$key => 0])
            ->all();

        $severityCounts = array_fill_keys(
            array_diff(array_keys($this->severityOptions()), ['all']),
            0
        );

        foreach ($this->baseIncompletePeopleCollection() as $person) {
            $state = $this->buildIncompleteState($person);

            foreach (array_keys($state['reasons']) as $reasonKey) {
                $reasonCounts[$reasonKey]++;
            }

            $severityCounts[$state['severity']['key']]++;
        }

        return [
            'incomplete_count' => array_sum($severityCounts),
            'filtered_count' => $this->filteredIncompletePeopleCollection()->count(),
            'reason_counts' => $reasonCounts,
            'severity_counts' => $severityCounts,
        ];
    }

    public function reasonsFor(Person $person): Collection
    {
        return collect($this->buildIncompleteState($person)['reasons'])->values();
    }

    public function severityFor(Person $person): array
    {
        return $this->buildIncompleteState($person)['severity'];
    }

    public function reasonOptions(): array
    {
        return ['all' => 'همه موارد'] + $this->catalogFields()
            ->mapWithKeys(fn (array $field): array => [
                $field['key'] => $field['section_label'].' - '.$field['label'],
            ])
            ->all();
    }

    public function severityOptions(): array
    {
        return [
            'all' => 'همه شدت‌ها',
            'critical' => 'بحرانی',
            'high' => 'زیاد',
            'medium' => 'متوسط',
            'low' => 'کم',
        ];
    }

    protected function filteredIncompletePeopleCollection(): Collection
    {
        return $this->baseIncompletePeopleCollection()
            ->filter(function (Person $person): bool {
                $state = $this->buildIncompleteState($person);

                if ($this->selectedReason !== 'all' && ! array_key_exists($this->selectedReason, $state['reasons'])) {
                    return false;
                }

                if ($this->selectedSeverity !== 'all' && $state['severity']['key'] !== $this->selectedSeverity) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    protected function baseIncompletePeopleCollection(): Collection
    {
        return $this->basePeopleCollection()
            ->filter(fn (Person $person): bool => count($this->buildIncompleteState($person)['reasons']) > 0)
            ->values();
    }

    protected function basePeopleCollection(): Collection
    {
        if ($this->peopleCache instanceof Collection) {
            return $this->peopleCache;
        }

        $people = Person::query()
            ->select([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'full_name',
                'guardian_id',
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
                'has_disability',
                'disability_type_id',
                'disability_description',
                'photo_id_card',
                'photo_birth_certificate',
                'profile_photo',
                'client_case_history',
                'created_at',
            ])
            ->with([
                'guardian:id,social_worker_id,national_code,first_name,last_name,guardian_phone_number,insurance_status,insurance_type_id',
                'guardian.socialWorker:id,first_name,last_name',
                'guardian.residence:id,guardian_id,residence_status_id,district_id,address',
                'education:id,person_id,is_studying,reason_for_not_studying,education_degree',
                'familyStatus:id,person_id,guardian_relation_type_id,has_parent_disability,parent_disability_description',
                'harmTypes:id',
                'residenceContact:id,person_id,residence_status_id,district_id,address',
                'supportCoverage:id,person_id,support_organization_id,description,other_organization_name,support_card_image',
                'supportCoverage.organization:id,slug',
                'needsLevel:id,person_id,need_level_id',
            ])
            ->latest('created_at')
            ->latest('id')
            ->get();

        $personIds = $people->pluck('id')->filter()->values();
        $guardianIds = $people->pluck('guardian_id')->filter()->unique()->values();

        $this->personContactsCache = Contact::query()
            ->select(['id', 'person_id', 'guardian_id', 'landline_phone', 'trusted_person_phone'])
            ->whereIn('person_id', $personIds)
            ->get()
            ->keyBy('person_id');

        $this->guardianContactsCache = Contact::query()
            ->select(['id', 'person_id', 'guardian_id', 'landline_phone', 'trusted_person_phone'])
            ->whereIn('guardian_id', $guardianIds)
            ->get()
            ->keyBy('guardian_id');

        return $this->peopleCache = $people;
    }

    protected function buildIncompleteState(Person $person): array
    {
        if (array_key_exists($person->id, $this->incompleteStateCache)) {
            return $this->incompleteStateCache[$person->id];
        }

        $reasons = [];

        foreach ($this->catalogFields() as $field) {
            $reason = $this->evaluateFieldDefect($person, $field);

            if ($reason !== null) {
                $reasons[$field['key']] = $reason;
            }
        }

        return $this->incompleteStateCache[$person->id] = [
            'reasons' => $reasons,
            'severity' => $this->buildSeverity($reasons),
        ];
    }

    protected function evaluateFieldDefect(Person $person, array $field): ?array
    {
        $guardian = $person->guardian;
        $education = $person->education;
        $familyStatus = $person->familyStatus;
        $residence = $this->resolveHouseholdResidence($person);
        $contact = $this->resolveHouseholdContact($person);
        $supportCoverage = $person->supportCoverage;
        $needsLevel = $person->needsLevel;

        $isStudying = $education?->is_studying;
        $hasDisability = $person->has_disability;
        $hasParentDisability = $familyStatus?->has_parent_disability;
        $insuranceStatus = $guardian?->insurance_status;
        $supportOrganizationSlug = trim((string) ($supportCoverage?->organization?->slug ?? ''));

        $isMissing = match ($field['key']) {
            'first_name' => $this->isBlankValue($person->first_name),
            'last_name' => $this->isBlankValue($person->last_name),
            'national_id' => $this->isBlankValue($person->national_id),
            'shenasnameh_serial' => $this->isBlankValue($person->shenasnameh_serial),
            'shenasnameh_series_number' => $this->isBlankValue($person->shenasnameh_series_number),
            'shenasnameh_series_letter' => $this->isBlankValue($person->shenasnameh_series_letter),
            'birth_day' => $person->birth_day === null,
            'birth_month' => $person->birth_month === null,
            'birth_year' => $person->birth_year === null,
            'father_name' => $this->isBlankValue($person->father_name),
            'father_national_id' => $this->isBlankValue($person->father_national_id),
            'mother_national_id' => $this->isBlankValue($person->mother_national_id),
            'phone_number' => $this->isBlankValue($person->phone_number),
            'gender' => $this->isBlankValue($person->gender),
            'role' => $this->isBlankValue($person->role),
            'sadaat_status' => $this->isBlankValue($person->sadaat_status),
            'sadaat_relation_id' => $person->sadaat_status === 'sadaat' && $person->sadaat_relation_id === null,
            'harm_types' => $person->harmTypes->isEmpty(),
            'has_disability' => $hasDisability === null,
            'disability_type_id' => (bool) $hasDisability && $person->disability_type_id === null,
            'disability_description' => (bool) $hasDisability && $this->isBlankValue($person->disability_description),
            'profile_photo' => $this->isBlankValue($person->profile_photo),
            'photo_id_card' => $this->isBlankValue($person->photo_id_card),
            'photo_birth_certificate' => $this->isBlankValue($person->photo_birth_certificate),
            'is_studying' => $isStudying === null,
            'reason_for_not_studying' => $isStudying === false && $this->isBlankValue($education?->reason_for_not_studying),
            'education_degree' => $isStudying === false
                && in_array($education?->reason_for_not_studying, ['graduation', 'dropped_out'], true)
                && $education?->education_degree === null,
            'guardian_relation_type_id' => $familyStatus?->guardian_relation_type_id === null,
            'client_case_history' => $this->isBlankValue($person->client_case_history),
            'has_parent_disability' => $hasParentDisability === null,
            'parent_disability_description' => (bool) $hasParentDisability && $this->isBlankValue($familyStatus?->parent_disability_description),
            'guardian_national_code' => $this->isBlankValue($guardian?->national_code),
            'guardian_first_name' => $this->isBlankValue($guardian?->first_name),
            'guardian_last_name' => $this->isBlankValue($guardian?->last_name),
            'social_worker_id' => $guardian?->social_worker_id === null,
            'insurance_status' => $insuranceStatus === null,
            'insurance_type_id' => (bool) $insuranceStatus && $guardian?->insurance_type_id === null,
            'guardian_phone_number' => $this->isBlankValue($guardian?->guardian_phone_number),
            'residence_status_id' => $residence?->residence_status_id === null,
            'district_id' => $residence?->district_id === null,
            'address' => $this->isBlankValue($residence?->address),
            'landline_phone' => $this->isBlankValue($contact?->landline_phone),
            'trusted_person_phone' => $this->isBlankValue($contact?->trusted_person_phone),
            'support_organization_id' => $supportCoverage?->support_organization_id === null,
            'support_organization_description' => $this->isBlankValue($supportCoverage?->description),
            'other_organization_name' => $supportOrganizationSlug === 'other' && $this->isBlankValue($supportCoverage?->other_organization_name),
            'support_card_image' => $this->isBlankValue($supportCoverage?->support_card_image),
            'need_level_id' => $needsLevel?->need_level_id === null,
            default => false,
        };

        if (! $isMissing) {
            return null;
        }

        return [
            'key' => $field['key'],
            'label' => $field['label'],
            'field' => $field['path'],
            'basis' => $this->buildFieldBasis($field),
            'section' => $field['section_label'],
            'requirement_type' => $field['requirement_type'],
            'severity' => $field['severity'],
        ];
    }

    protected function buildFieldBasis(array $field): string
    {
        return match ($field['key']) {
            'sadaat_relation_id' => 'این فیلد فقط برای مددجویان سادات باید تکمیل شود.',
            'disability_type_id' => 'اگر وضعیت معلولیت فعال باشد، نوع معلولیت باید مشخص شود.',
            'disability_description' => 'اگر مددجو دارای معلولیت باشد، شرح معلولیت باید ثبت شود.',
            'reason_for_not_studying' => 'اگر مددجو در حال تحصیل نیست، دلیل عدم تحصیل باید مشخص شود.',
            'education_degree' => 'اگر دلیل عدم تحصیل «فارغ‌التحصیلی» یا «ترک تحصیل» باشد، مقطع تحصیلی باید ثبت شود.',
            'parent_disability_description' => 'اگر معلولیت والدین ثبت شده باشد، شرح آن نیز باید تکمیل شود.',
            'guardian_first_name', 'guardian_last_name' => 'در صورتی که سرپرست از قبل در سامانه ثبت نشده باشد، مشخصات او باید کامل ثبت شود.',
            'insurance_type_id' => 'اگر سرپرست دارای بیمه باشد، نوع بیمه نیز باید مشخص شود.',
            'other_organization_name' => 'اگر نهاد حمایتی از نوع «سایر» انتخاب شده باشد، نام آن باید نوشته شود.',
            default => '',
        };
    }

    protected function buildSeverity(array $reasons): array
    {
        $reasonCount = count($reasons);
        $weights = [
            'critical' => 5,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        ];

        $score = collect($reasons)->sum(fn (array $reason): int => $weights[$reason['severity']] ?? 1);
        $criticalCount = collect($reasons)->where('severity', 'critical')->count();
        $highCount = collect($reasons)->where('severity', 'high')->count();
        $mediumCount = collect($reasons)->where('severity', 'medium')->count();

        if ($criticalCount > 0 || $score >= 14 || $reasonCount >= 8) {
            return [
                'key' => 'critical',
                'label' => 'بحرانی',
                'classes' => 'border-rose-200 bg-rose-50 text-rose-800',
            ];
        }

        if ($highCount > 0 || $score >= 8 || $reasonCount >= 5) {
            return [
                'key' => 'high',
                'label' => 'زیاد',
                'classes' => 'border-amber-200 bg-amber-50 text-amber-800',
            ];
        }

        if ($mediumCount > 0 || $score >= 3 || $reasonCount >= 2) {
            return [
                'key' => 'medium',
                'label' => 'متوسط',
                'classes' => 'border-sky-200 bg-sky-50 text-sky-800',
            ];
        }

        return [
            'key' => 'low',
            'label' => 'کم',
            'classes' => 'border-slate-200 bg-slate-50 text-slate-700',
        ];
    }

    protected function resolveHouseholdResidence(Person $person): mixed
    {
        return $person->residenceContact ?: $person->guardian?->residence;
    }

    protected function resolveHouseholdContact(Person $person): ?Contact
    {
        $personContact = $this->personContactsCache?->get($person->id);

        if ($personContact instanceof Contact) {
            return $personContact;
        }

        if (! $person->guardian_id) {
            return null;
        }

        $guardianContact = $this->guardianContactsCache?->get($person->guardian_id);

        return $guardianContact instanceof Contact ? $guardianContact : null;
    }

    protected function isBlankValue(mixed $value): bool
    {
        return blank(is_string($value) ? trim($value) : $value);
    }

    protected function catalogFields(): Collection
    {
        return collect(app(BeneficiaryCompletenessCatalog::class)->fields());
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.incomplete-cases-queue');
    }
}
