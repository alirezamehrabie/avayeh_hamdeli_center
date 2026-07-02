<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use App\Models\GateEntryAssignment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GateDeliveryReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'نام گیرنده',
            'نوع',
            'کد',
            'کد ملی',
            'دسته‌بندی/قلم',
            'وضعیت فعلی',
            'اپراتور تحویل',
            'زمان تحویل',
        ];
    }

    public function map($row): array
    {
        $isGuardian = (bool) $row->guardian_id;
        $code = $isGuardian ? $row->guardian?->guardian_code : $row->person?->person_code;
        $currentState = $row->status === GateEntryAssignment::STATUS_FINALIZED
            ? 'از گیت خروج نهایی شده'
            : 'در انتظار خروج';

        return [
            $row->recipient_name,
            $isGuardian ? 'خانوادگی' : 'شخصی',
            $code ?: '-',
            $row->national_id ?: '-',
            $row->serviceCategory?->name ?: '-',
            $currentState,
            $row->deliveredBy?->full_name ?: $row->deliveredBy?->name ?: '-',
            $row->delivered_at ? Jalalian::fromDateTime($row->delivered_at)->format('Y/m/d H:i') : '-',
        ];
    }

    public function title(): string
    {
        return 'گزارش گیت تحویل';
    }
}
