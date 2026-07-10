<?php

namespace App\Services\People;

class BeneficiaryCompletenessCatalog
{
    /**
     * Canonical field inventory for beneficiary registration completeness.
     *
     * This intentionally covers:
     * - form-required fields from CreatePerson wizard steps
     * - conditional fields whose requirement depends on another answer
     * - management-quality fields the dashboard should still track as defects
     */
    public function sections(): array
    {
        return [
            1 => ['key' => 'personal', 'label' => 'اطلاعات فردی'],
            2 => ['key' => 'skills', 'label' => 'مهارت‌ و استعدادها'],
            3 => ['key' => 'harm_and_disability', 'label' => 'آسیب و معلولیت'],
            4 => ['key' => 'identity_documents', 'label' => 'مدارک شناسایی'],
            5 => ['key' => 'education', 'label' => 'وضعیت تحصیلی'],
            6 => ['key' => 'family_status', 'label' => 'وضعیت خانوادگی'],
            7 => ['key' => 'guardian_and_livelihood', 'label' => 'اطلاعات سرپرست و معیشت'],
            8 => ['key' => 'banking', 'label' => 'اطلاعات بانکی'],
            9 => ['key' => 'residence_and_contact', 'label' => 'سکونت و تماس'],
            10 => ['key' => 'support_and_need', 'label' => 'نیازمندی و پوشش حمایتی'],
        ];
    }

    public function fields(): array
    {
        return [
            $this->field('first_name', 1, 'نام مددجو', 'people.first_name', 'person', 'required', 'critical'),
            $this->field('last_name', 1, 'نام خانوادگی مددجو', 'people.last_name', 'person', 'required', 'critical'),
            $this->field('national_id', 1, 'کد ملی مددجو', 'people.national_id', 'person', 'required', 'critical'),
            $this->field('shenasnameh_serial', 1, 'سریال شناسنامه', 'people.shenasnameh_serial', 'person', 'required', 'high'),
            $this->field('shenasnameh_series_number', 1, 'شماره سری شناسنامه', 'people.shenasnameh_series_number', 'person', 'required', 'high'),
            $this->field('shenasnameh_series_letter', 1, 'حرف سری شناسنامه', 'people.shenasnameh_series_letter', 'person', 'required', 'high'),
            $this->field('birth_day', 1, 'روز تولد', 'people.birth_day', 'person', 'required', 'high'),
            $this->field('birth_month', 1, 'ماه تولد', 'people.birth_month', 'person', 'required', 'high'),
            $this->field('birth_year', 1, 'سال تولد', 'people.birth_year', 'person', 'required', 'high'),
            $this->field('father_name', 1, 'نام پدر', 'people.father_name', 'person', 'required', 'high'),
            $this->field('father_national_id', 1, 'کد ملی پدر', 'people.father_national_id', 'person', 'required', 'high'),
            $this->field('mother_national_id', 1, 'کد ملی مادر', 'people.mother_national_id', 'person', 'required', 'high'),
            $this->field('phone_number', 1, 'شماره همراه مددجو', 'people.phone_number', 'person', 'required', 'high'),
            $this->field('gender', 1, 'جنسیت', 'people.gender', 'person', 'required', 'critical'),
            $this->field('role', 1, 'نقش', 'people.role', 'person', 'required', 'high'),
            $this->field('sadaat_status', 1, 'وضعیت سادات', 'people.sadaat_status', 'person', 'required', 'medium'),
            $this->field(
                'sadaat_relation_id',
                1,
                'نسبت سادات',
                'people.sadaat_relation_id',
                'person',
                'conditional',
                'medium',
                'Only when `sadaat_status = sadaat`.'
            ),

            $this->field('harm_types', 3, 'آسیب‌ها', 'harm_type_person.harm_type_id', 'person', 'required', 'critical'),
            $this->field('has_disability', 3, 'وضعیت معلولیت', 'people.has_disability', 'person', 'required', 'high'),
            $this->field(
                'disability_type_id',
                3,
                'نوع معلولیت',
                'people.disability_type_id',
                'person',
                'conditional',
                'high',
                'Only when `has_disability = 1`.'
            ),
            $this->field(
                'disability_description',
                3,
                'شرح معلولیت',
                'people.disability_description',
                'person',
                'management',
                'medium',
                'Management-quality defect when disability exists but explanatory text is missing.'
            ),

            $this->field(
                'profile_photo',
                4,
                'عکس مددجو',
                'people.profile_photo',
                'person',
                'management',
                'medium',
                'Tracked as a management-quality field even though upload validation is nullable.'
            ),
            $this->field(
                'photo_id_card',
                4,
                'تصویر کارت ملی',
                'people.photo_id_card',
                'person',
                'management',
                'high',
                'Tracked as a management-quality identity document field.'
            ),
            $this->field(
                'photo_birth_certificate',
                4,
                'تصویر شناسنامه',
                'people.photo_birth_certificate',
                'person',
                'management',
                'high',
                'Tracked as a management-quality identity document field.'
            ),

            $this->field('is_studying', 5, 'وضعیت تحصیل', 'educations.is_studying', 'person', 'required', 'high'),
            $this->field(
                'reason_for_not_studying',
                5,
                'دلیل عدم تحصیل',
                'educations.reason_for_not_studying',
                'person',
                'conditional',
                'high',
                'Only when `is_studying = 0`.'
            ),
            $this->field(
                'education_degree',
                5,
                'مقطع/درجه تحصیلی',
                'educations.education_degree',
                'person',
                'conditional',
                'high',
                'Only when the form shows the degree field based on education state.'
            ),

            $this->field('guardian_relation_type_id', 6, 'نسبت با سرپرست', 'family_statuses.guardian_relation_type_id', 'person', 'required', 'critical'),
            $this->field(
                'client_case_history',
                6,
                'شرح وضعیت/تاریخچه پرونده',
                'people.client_case_history',
                'person',
                'management',
                'medium',
                'Tracked as a management-quality defect for case review completeness.'
            ),
            $this->field(
                'has_parent_disability',
                6,
                'وضعیت معلولیت والدین',
                'family_statuses.has_parent_disability',
                'person',
                'management',
                'medium'
            ),
            $this->field(
                'parent_disability_description',
                6,
                'شرح معلولیت والدین',
                'family_statuses.parent_disability_description',
                'person',
                'conditional',
                'medium',
                'Only when `has_parent_disability = 1`.'
            ),

            $this->field('guardian_national_code', 7, 'کد ملی سرپرست', 'guardians.national_code', 'guardian', 'required', 'critical'),
            $this->field(
                'guardian_first_name',
                7,
                'نام سرپرست',
                'guardians.first_name',
                'guardian',
                'conditional',
                'critical',
                'Required when guardian is not already linked from the database.'
            ),
            $this->field(
                'guardian_last_name',
                7,
                'نام خانوادگی سرپرست',
                'guardians.last_name',
                'guardian',
                'conditional',
                'critical',
                'Required when guardian is not already linked from the database.'
            ),
            $this->field('social_worker_id', 7, 'مددکار مسئول', 'guardians.social_worker_id', 'guardian', 'required', 'critical'),
            $this->field('insurance_status', 7, 'وضعیت بیمه', 'guardians.insurance_status', 'guardian', 'required', 'high'),
            $this->field(
                'insurance_type_id',
                7,
                'نوع بیمه',
                'guardians.insurance_type_id',
                'guardian',
                'conditional',
                'high',
                'Only when `insurance_status = 1`.'
            ),
            $this->field(
                'guardian_phone_number',
                7,
                'شماره همراه سرپرست',
                'guardians.guardian_phone_number',
                'guardian',
                'management',
                'medium'
            ),

            $this->field(
                'residence_status_id',
                9,
                'وضعیت سکونت',
                'residences.residence_status_id',
                'household',
                'management',
                'medium',
                'Residence may be owned by `person_id` or `guardian_id` depending on household data entry.'
            ),
            $this->field(
                'district_id',
                9,
                'ناحیه سکونت',
                'residences.district_id',
                'household',
                'management',
                'medium',
                'Residence may be owned by `person_id` or `guardian_id` depending on household data entry.'
            ),
            $this->field(
                'address',
                9,
                'نشانی',
                'residences.address',
                'household',
                'management',
                'medium',
                'Residence may be owned by `person_id` or `guardian_id` depending on household data entry.'
            ),
            $this->field(
                'landline_phone',
                9,
                'تلفن ثابت',
                'contacts.landline_phone',
                'household',
                'management',
                'low',
                'Contact records may be owned by `person_id` or `guardian_id` depending on household data entry.'
            ),
            $this->field(
                'trusted_person_phone',
                9,
                'شماره فرد مورد اعتماد',
                'contacts.trusted_person_phone',
                'household',
                'management',
                'low',
                'Contact records may be owned by `person_id` or `guardian_id` depending on household data entry.'
            ),

            $this->field(
                'support_organization_id',
                10,
                'سازمان حمایتی',
                'support_coverages.support_organization_id',
                'person',
                'management',
                'medium'
            ),
            $this->field(
                'support_organization_description',
                10,
                'شرح پوشش حمایتی',
                'support_coverages.description',
                'person',
                'management',
                'medium'
            ),
            $this->field(
                'other_organization_name',
                10,
                'نام نهاد حمایتی آزاد',
                'support_coverages.other_organization_name',
                'person',
                'conditional',
                'medium',
                'Only when the selected support organization slug is `other`.'
            ),
            $this->field(
                'support_card_image',
                10,
                'تصویر کارت حمایت',
                'support_coverages.support_card_image',
                'person',
                'management',
                'medium'
            ),
            $this->field(
                'need_level_id',
                10,
                'سطح نیازمندی',
                'needs_levels.need_level_id',
                'person',
                'management',
                'high'
            ),
        ];
    }

    public function fieldsByStep(): array
    {
        $grouped = [];

        foreach ($this->fields() as $field) {
            $grouped[$field['step']][] = $field;
        }

        return $grouped;
    }

    public function fieldKeys(): array
    {
        return array_column($this->fields(), 'key');
    }

    public function find(string $key): ?array
    {
        foreach ($this->fields() as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }

        return null;
    }

    private function field(
        string $key,
        int $step,
        string $label,
        string $path,
        string $owner,
        string $requirementType,
        string $severity,
        ?string $note = null
    ): array {
        $section = $this->sections()[$step];

        return [
            'key' => $key,
            'step' => $step,
            'section_key' => $section['key'],
            'section_label' => $section['label'],
            'label' => $label,
            'path' => $path,
            'owner' => $owner,
            'requirement_type' => $requirementType,
            'severity' => $severity,
            'note' => $note,
        ];
    }
}
