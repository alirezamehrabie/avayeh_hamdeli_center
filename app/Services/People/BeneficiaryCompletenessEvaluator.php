<?php

namespace App\Services\People;

use App\Models\Contact;
use App\Models\Person;
use Illuminate\Support\Collection;

class BeneficiaryCompletenessEvaluator
{
    public function evaluate(
        Person $person,
        array $fields,
        ?Collection $personContacts = null,
        ?Collection $guardianContacts = null,
    ): array {
        $reasons = [];
        $personContacts ??= collect();
        $guardianContacts ??= collect();

        foreach ($fields as $field) {
            $reason = $this->evaluateFieldDefect(
                $person,
                $field,
                $personContacts,
                $guardianContacts,
            );

            if ($reason !== null) {
                $reasons[$field['key']] = $reason;
            }
        }

        return [
            'reasons' => $reasons,
            'severity' => $this->buildSeverity($reasons),
        ];
    }

    public function nextStep(array $reasons): array
    {
        if ($reasons === []) {
            return [
                'section' => 'پرونده',
                'headline' => 'پرونده کامل است.',
                'supporting_text' => 'در حال حاضر اقدامی برای تکمیل این پرونده نیاز نیست.',
                'primary_action_label' => 'مشاهده پرونده مددجو',
                'focus_label' => null,
            ];
        }

        $sectionPriority = [
            'اطلاعات هویتی مددجو' => 1,
            'مشخصات سرپرست' => 2,
            'اطلاعات سکونت و تماس' => 3,
            'وضعیت تحصیل و خانواده' => 4,
            'پوشش حمایتی و نیازمندی' => 5,
            'شرح پرونده و مدارک' => 6,
        ];

        $severityWeight = [
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        ];

        $sortedReasons = collect($reasons)
            ->sort(function (array $left, array $right) use ($severityWeight, $sectionPriority): int {
                $leftSeverity = $severityWeight[$left['severity']] ?? 0;
                $rightSeverity = $severityWeight[$right['severity']] ?? 0;

                if ($leftSeverity !== $rightSeverity) {
                    return $rightSeverity <=> $leftSeverity;
                }

                $leftSection = $sectionPriority[$left['section']] ?? 999;
                $rightSection = $sectionPriority[$right['section']] ?? 999;

                if ($leftSection !== $rightSection) {
                    return $leftSection <=> $rightSection;
                }

                return strcmp($left['label'], $right['label']);
            })
            ->values();

        $primaryReason = $sortedReasons->first();
        $primarySection = (string) ($primaryReason['section'] ?? 'پرونده');
        $sectionCount = $sortedReasons->where('section', $primarySection)->count();
        $focusLabel = (string) ($primaryReason['label'] ?? '');

        return [
            'section' => $primarySection,
            'headline' => 'ابتدا بخش «'.$primarySection.'» را تکمیل کنید.',
            'supporting_text' => $sectionCount > 1
                ? 'مهم‌ترین مورد این بخش: «'.$focusLabel.'» و '.$sectionCount.' نقص ثبت‌شده در همین بخش.'
                : 'مهم‌ترین مورد برای شروع: «'.$focusLabel.'».',
            'primary_action_label' => 'تکمیل '.$primarySection,
            'focus_label' => $focusLabel,
        ];
    }

    private function evaluateFieldDefect(
        Person $person,
        array $field,
        Collection $personContacts,
        Collection $guardianContacts,
    ): ?array {
        $guardian = $person->guardian;
        $education = $person->education;
        $familyStatus = $person->familyStatus;
        $residence = $person->residenceContact ?: $guardian?->residence;
        $contact = $this->resolveHouseholdContact($person, $personContacts, $guardianContacts);
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
            'other_organization_name' => $supportOrganizationSlug === 'other'
                && $this->isBlankValue($supportCoverage?->other_organization_name),
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

    private function buildFieldBasis(array $field): string
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

    private function buildSeverity(array $reasons): array
    {
        $reasonCount = count($reasons);
        $weights = [
            'critical' => 5,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        ];

        $score = 0;
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;

        foreach ($reasons as $reason) {
            $severity = $reason['severity'];
            $score += $weights[$severity] ?? 1;

            if ($severity === 'critical') {
                $criticalCount++;
            } elseif ($severity === 'high') {
                $highCount++;
            } elseif ($severity === 'medium') {
                $mediumCount++;
            }
        }

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

    private function resolveHouseholdContact(
        Person $person,
        Collection $personContacts,
        Collection $guardianContacts,
    ): ?Contact {
        $personContact = $personContacts->get($person->id);

        if ($personContact instanceof Contact) {
            return $personContact;
        }

        if (! $person->guardian_id) {
            return null;
        }

        $guardianContact = $guardianContacts->get($person->guardian_id);

        return $guardianContact instanceof Contact ? $guardianContact : null;
    }

    private function isBlankValue(mixed $value): bool
    {
        return blank(is_string($value) ? trim($value) : $value);
    }
}
