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

class GateDeliveryReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    use ResolvesGateDeliveryMethod;

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
            'دسته‌بندی/قلم',
            'وضعیت فعلی',
        ], $this->deliveryMethodHeadings(), [
            'اپراتور تحویل',
            'زمان تحویل',
        ]);
    }

    public function map($row): array
    {
        $isGuardian = (bool) $row->guardian_id;
        $code = $isGuardian ? $row->guardian?->guardian_code : $row->person?->person_code;
        $currentState = $row->status === GateEntryAssignment::STATUS_FINALIZED
            ? 'از گیت خروج نهایی شده'
            : 'در انتظار خروج';

        return array_merge([
            $row->recipient_name,
            $isGuardian ? 'خانوادگی' : 'شخصی',
            $code ?: '-',
            $row->national_id ?: '-',
            $row->serviceCategory?->name ?: '-',
            $currentState,
        ], $this->deliveryMethodColumns($this->service, $row), [
            $row->deliveredBy?->full_name ?: $row->deliveredBy?->name ?: '-',
            $row->delivered_at ? Jalalian::fromDateTime($row->delivered_at)->format('Y/m/d H:i') : '-',
        ]);
    }

    public function title(): string
    {
        return 'گزارش گیت تحویل';
    }
}
