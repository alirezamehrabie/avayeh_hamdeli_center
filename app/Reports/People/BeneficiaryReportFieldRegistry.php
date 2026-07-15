<?php

namespace App\Reports\People;

final class BeneficiaryReportFieldRegistry
{
    public function __construct(
        private readonly BeneficiaryReportSemantics $semantics = new BeneficiaryReportSemantics,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'first_name' => ['label' => 'نام', 'type' => 'text'],
            'last_name' => ['label' => 'نام خانوادگی', 'type' => 'text'],
            'birth_date' => ['label' => 'تاریخ تولد', 'type' => 'date'],
            'age' => ['label' => 'سن (سال)', 'type' => 'number'],
            'national_id' => ['label' => 'کد ملی', 'type' => 'text'],
            'phone_number' => ['label' => 'موبایل', 'type' => 'text'],
            'sadaat_status' => ['label' => 'سادات', 'type' => 'select', 'options' => [
                'sadaat' => 'سادات',
                'general' => 'غیر سادات',
            ]],
            'gender' => ['label' => 'جنسیت', 'type' => 'select', 'options' => [
                'male' => 'پسر',
                'female' => 'دختر',
            ]],
            'skills' => ['label' => 'مهارت‌ها', 'type' => 'select', 'options' => []],
            'harm_types' => ['label' => 'نوع آسیب', 'type' => 'select', 'options' => []],
            'disability_status_type' => ['label' => 'معلولیت و نوع معلولیت', 'type' => 'select', 'options' => []],
            'education_status' => ['label' => 'وضعیت تحصیلی', 'type' => 'select', 'options' => [
                '1' => 'بله',
                '0' => 'خیر',
            ]],
            'education_level' => ['label' => 'مقطع تحصیلی', 'type' => 'select', 'options' => []],
            'works_alongside_study' => ['label' => 'همزمان با تحصیل کار می‌کند', 'type' => 'select', 'options' => [
                '1' => 'بله',
                '0' => 'خیر',
            ]],
            'guardian_relation' => ['label' => 'نسبت با سرپرست', 'type' => 'select', 'options' => []],
            'guardian_national_code' => ['label' => 'کد ملی سرپرست', 'type' => 'text'],
            'has_parent_disability' => ['label' => 'معلولیت والدین', 'type' => 'select', 'options' => [
                '1' => 'بله',
                '0' => 'خیر',
            ]],
            'guardian_full_name' => ['label' => 'نام و نام خانوادگی سرپرست', 'type' => 'text'],
            'guardian_mobile' => ['label' => 'موبایل سرپرست', 'type' => 'text'],
            'caseworker' => ['label' => 'مددکار فعلی خانوار (نام یا کد)', 'type' => 'text'],
            'economic_decile' => ['label' => 'دهک اقتصادی', 'type' => 'select', 'options' => []],
            'guardian_occupation' => ['label' => 'شغل سرپرست', 'type' => 'select', 'options' => []],
            'guardian_job_type' => ['label' => 'نوع شغل سرپرست', 'type' => 'select', 'options' => []],
            'supported_children_count' => ['label' => 'تعداد فرزندان تحت تکفل', 'type' => 'number'],
            'insurance_status_type' => ['label' => 'بیمه و نوع بیمه', 'type' => 'select', 'options' => []],
            'vehicle_status_type' => ['label' => 'وسیله نقلیه و نوع آن', 'type' => 'select', 'options' => []],
            'average_income' => ['label' => 'میانگین درآمد ماهانه', 'type' => 'number'],
            'housing_status' => ['label' => 'وضعیت مسکن', 'type' => 'select', 'options' => []],
            'district' => ['label' => 'منطقه / ناحیه', 'type' => 'select', 'options' => []],
            'is_local_to_city' => ['label' => 'بومی شهر', 'type' => 'select', 'options' => [
                '1' => 'بله',
                '0' => 'خیر',
            ]],
            'support_organization' => ['label' => 'نوع نهاد حمایتی', 'type' => 'select', 'options' => []],
            'need_level' => ['label' => 'سطح نیازمندی', 'type' => 'select', 'options' => []],
            'months_without_service' => ['label' => 'حداقل ماه‌های کامل بدون خدمت مستقیم یا خانوادگی', 'type' => 'number'],
        ];
    }

    public function has(string $field): bool
    {
        return isset($this->definitions()[$field]);
    }

    public function type(string $field): ?string
    {
        return $this->definitions()[$field]['type'] ?? null;
    }

    /**
     * @param  array<int, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function normalizeFilters(array $filters): array
    {
        $normalized = [];

        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $field = (string) ($filter['field'] ?? '');
            $type = $this->type($field);

            if ($type === null) {
                continue;
            }

            $normalizedFilter = match ($type) {
                'text' => $this->normalizeTextFilter($field, $filter),
                'select' => [
                    'field' => $field,
                    'type' => 'select',
                    'value' => $this->scalarString($filter['value'] ?? ''),
                ],
                'date' => [
                    'field' => $field,
                    'type' => 'date',
                    'mode' => in_array(($filter['mode'] ?? null), ['exact', 'range', 'month', 'year'], true)
                        ? $filter['mode']
                        : 'exact',
                    'exact' => $this->scalarString($filter['exact'] ?? ''),
                    'from' => $this->scalarString($filter['from'] ?? ''),
                    'to' => $this->scalarString($filter['to'] ?? ''),
                    'month' => $this->scalarString($filter['month'] ?? ''),
                    'year' => $this->scalarString($filter['year'] ?? ''),
                ],
                'number' => $this->normalizeNumberFilter($field, $filter),
                default => null,
            };

            if ($normalizedFilter !== null) {
                $normalized[] = $normalizedFilter;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function normalizeTextFilter(string $field, array $filter): array
    {
        $normalized = [
            'field' => $field,
            'type' => 'text',
            'operator' => ($filter['operator'] ?? null) === 'exact' ? 'exact' : 'contains',
            'value' => $this->scalarString($filter['value'] ?? ''),
        ];

        if ($field !== 'caseworker') {
            return $normalized;
        }

        $normalized['selected'] = collect($filter['selected'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && isset($item['id']) && is_numeric($item['id']))
            ->map(fn (array $item): array => [
                'id' => (int) $item['id'],
                'name' => $this->scalarString($item['name'] ?? ''),
                'code' => $this->scalarString($item['code'] ?? ''),
            ])
            ->unique('id')
            ->values()
            ->all();

        return $normalized;
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : '';
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function normalizeNumberFilter(string $field, array $filter): array
    {
        $allowedOperators = $field === 'months_without_service'
            ? [$this->semantics::MONTHS_WITHOUT_SERVICE_OPERATOR]
            : ['eq', 'gt', 'gte', 'lt', 'lte'];
        $defaultOperator = $field === 'months_without_service'
            ? $this->semantics::MONTHS_WITHOUT_SERVICE_OPERATOR
            : 'eq';
        $operator = (string) ($filter['operator'] ?? '');

        return [
            'field' => $field,
            'type' => 'number',
            'operator' => in_array($operator, $allowedOperators, true)
                ? $operator
                : $defaultOperator,
            'value' => $this->scalarString($filter['value'] ?? ''),
        ];
    }
}
