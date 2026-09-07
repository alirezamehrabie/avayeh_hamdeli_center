<?php

namespace App\Exports;

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
 * Excel export for the "delivered details by category" modal of the advanced
 * service report.
 *
 * Consumes the already-filtered breakdown collection produced by the
 * ServiceReports Livewire component (getDeliveredCategoryBreakdownProperty),
 * so the exported rows mirror exactly what the modal shows on screen:
 * one row per delivered category with its total quantity and record count.
 */
class ServiceCategoryBreakdownExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    /**
     * @param  Collection  $breakdown  Output of ServiceReports::getDeliveredCategoryBreakdownProperty()
     * @param  string  $serviceName  Used for the sheet title
     */
    public function __construct(
        protected Collection $breakdown,
        protected string $serviceName = 'خدمت',
    ) {}

    public function headings(): array
    {
        return [
            'نام دسته‌بندی',
            'تعداد',
            'واحد',
            'تعداد رکورد تحویل',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->breakdown as $row) {
            $rows[] = [
                $row['category'] ?? '-',
                (float) ($row['totalRaw'] ?? 0),
                $row['unitLabel'] ?? '-',
                (int) ($row['recordCount'] ?? 0),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return mb_substr('جزئیات تحویل '.$this->serviceName, 0, 31);
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
