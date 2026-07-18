<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Excel export for a single service's advanced report.
 *
 * Consumes the already-filtered & grouped delivery collection produced by the
 * ServiceReports Livewire component (getGroupedDeliveriesProperty), so the query
 * logic is never duplicated between the on-screen report and the exported file.
 * Each grouped recipient (person / guardian / manual) is expanded into one row
 * per delivered category, mirroring the grouped structure shown in the UI.
 */
class ServiceReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle, WithEvents
{
    /**
     * @param  Collection  $groupedDeliveries  Output of ServiceReports::getGroupedDeliveriesProperty()
     * @param  array<string, string>  $unitOptions  Map of unit key => Persian label
     */
    public function __construct(
        protected Service $service,
        protected Collection $groupedDeliveries,
        protected array $unitOptions = [],
    ) {
    }

    public function headings(): array
    {
        return [
            'نام گیرنده',
            'نوع گیرنده',
            'کد ملی',
            'کد مددجو/خانوار',
            'موبایل',
            'دسته‌بندی خدمت',
            'مقدار تحویل',
            'واحد',
            'ارزش تحویل (ریال)',
            'مددکار',
            'تاریخ تحویل',
            'توضیحات',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->groupedDeliveries as $group) {
            $personCode = $group->person?->person_code;
            $guardianCode = $group->guardian?->guardian_code;
            $recipientCode = $personCode ?: ($guardianCode ?: '-');

            foreach ($group->deliveries as $delivery) {
                $unitKey = $delivery->serviceCategory?->unit;
                $unitLabel = $unitKey
                    ? ($this->unitOptions[$unitKey] ?? $unitKey)
                    : '-';

                $rows[] = [
                    $group->recipientName ?: '-',
                    $group->recipientType,
                    (string) ($group->recipientNationalId ?: '-'),
                    (string) $recipientCode,
                    (string) ($group->mobile ?: '-'),
                    $delivery->serviceCategory?->name ?: '-',
                    number_format((float) $delivery->delivered_quantity, 2),
                    $unitLabel,
                    number_format((int) $delivery->delivered_total_value),
                    $delivery->socialWorker?->full_name ?: '-',
                    $delivery->delivered_at
                        ? Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
                        : ($delivery->created_at ? Jalalian::fromDateTime($delivery->created_at)->format('Y/m/d') : '-'),
                    $delivery->notes ?: '-',
                ];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        $name = $this->service->serviceName?->name ?: 'خدمت';

        return mb_substr('گزارش '.$name, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();
                $fullRange = "A1:{$lastCol}{$lastRow}";

                $sheet->setRightToLeft(true);

                $sheet->getStyle($fullRange)->applyFromArray([
                    'font' => [
                        'name' => 'B Zar',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'readorder' => 2,
                    ],
                ]);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'name' => 'B Zar',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
