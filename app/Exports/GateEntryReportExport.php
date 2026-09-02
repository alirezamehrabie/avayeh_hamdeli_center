<?php

namespace App\Exports;

use App\Exports\Concerns\ResolvesGateDeliveryMethod;
use App\Helpers\Morilog\Jalalian;
use App\Models\GateEntryAssignment;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GateEntryReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    use ResolvesGateDeliveryMethod;

    protected const STATUS_LABELS = [
        GateEntryAssignment::STATUS_PENDING => 'در انتظار تحویل',
        GateEntryAssignment::STATUS_DELIVERED => 'تحویل شده',
        GateEntryAssignment::STATUS_FINALIZED => 'خروج نهایی',
    ];

    public function __construct(protected Builder $query, protected ?Service $service = null) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return array_merge([
            'نام گیرنده',
            'نوع',
            'کد',
            'کد ملی',
            'موبایل',
            'دسته‌بندی/قلم',
            'وضعیت',
        ], $this->deliveryMethodHeadings(), [
            'اپراتور ورود',
            'زمان ورود',
        ]);
    }

    public function map($row): array
    {
        $isGuardian = (bool) $row->guardian_id;
        $code = $isGuardian ? $row->guardian?->guardian_code : $row->person?->person_code;

        return array_merge([
            $row->recipient_name,
            $isGuardian ? 'خانوادگی' : 'شخصی',
            $code ?: '-',
            $row->national_id ?: '-',
            $row->mobile ?: '-',
            $row->serviceCategory?->name ?: '-',
            self::STATUS_LABELS[$row->status] ?? $row->status,
        ], $this->deliveryMethodColumns($this->service, $row), [
            $row->creator?->full_name ?: $row->creator?->name ?: '-',
            $row->assigned_at ? Jalalian::fromDateTime($row->assigned_at)->format('Y/m/d H:i') : '-',
        ]);
    }

    public function title(): string
    {
        return 'گزارش گیت ورود';
    }
}
