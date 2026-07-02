<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GateExitReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
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
            'مقدار',
            'ارزش تحویل (ریال)',
            'اپراتور خروج',
            'زمان خروج',
            'یادداشت',
        ];
    }

    public function map($row): array
    {
        $isGuardian = (bool) $row->guardian_id;
        $code = $isGuardian ? $row->guardian?->guardian_code : $row->person?->person_code;

        return [
            $row->recipient_name,
            $isGuardian ? 'خانوادگی' : 'شخصی',
            $code ?: '-',
            $row->national_id ?: '-',
            $row->serviceCategory?->name ?: '-',
            number_format((float) $row->delivered_quantity, 2),
            number_format((int) $row->delivered_total_value),
            $row->creator?->full_name ?: $row->creator?->name ?: '-',
            $row->delivered_at ? Jalalian::fromDateTime($row->delivered_at)->format('Y/m/d') : '-',
            $row->notes ?: '-',
        ];
    }

    public function title(): string
    {
        return 'گزارش گیت خروج';
    }
}
