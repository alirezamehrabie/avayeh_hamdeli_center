<?php

namespace App\Exports;

use App\Exports\Concerns\ResolvesGateDeliveryMethod;
use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GateExitReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
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
            'مقدار',
            'ارزش تحویل (ریال)',
        ], $this->deliveryMethodHeadings(), [
            'اپراتور خروج',
            'زمان خروج',
            'یادداشت',
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
            $row->serviceCategory?->name ?: '-',
            number_format((float) $row->delivered_quantity, 2),
            number_format((int) $row->delivered_total_value),
        ], $this->deliveryMethodColumns($this->service, $row), [
            $row->creator?->full_name ?: $row->creator?->name ?: '-',
            $row->delivered_at ? Jalalian::fromDateTime($row->delivered_at)->format('Y/m/d') : '-',
            $row->notes ?: '-',
        ]);
    }

    public function title(): string
    {
        return 'گزارش گیت خروج';
    }
}
