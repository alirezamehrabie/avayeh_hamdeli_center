<?php

namespace App\Exports;

use App\Reports\People\BeneficiaryReportColumnRegistry;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PeopleExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        protected Builder $query,
        protected array $visibleColumns,
        protected ?BeneficiaryReportColumnRegistry $columnRegistry = null,
    ) {
        $this->columnRegistry ??= app(BeneficiaryReportColumnRegistry::class);
        $this->visibleColumns = $this->columnRegistry->normalizeVisibleColumns($this->visibleColumns);
    }

    public function query(): Builder
    {
        return $this->query->with($this->columnRegistry->eagerLoads());
    }

    public function headings(): array
    {
        return collect($this->visibleColumns)
            ->map(fn ($col) => $this->columnRegistry->labels()[$col] ?? $col)
            ->toArray();
    }

    public function map($row): array
    {
        return collect($this->visibleColumns)
            ->map(fn (string $col) => $this->columnRegistry->value($row, $col))
            ->toArray();
    }

    public function title(): string
    {
        return 'مددجویان';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();
                $fullRange = "A1:{$lastCol}{$lastRow}";

                // راست به چپ
                $sheet->setRightToLeft(true);

                // فونت و تراز روی تمام سلول‌ها
                $sheet->getStyle($fullRange)->applyFromArray([
                    'font' => [
                        'name' => 'B Zar',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'readorder' => 2, // RTL read order
                    ],
                ]);

                // استایل اضافه برای هدر
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

                // ارتفاع ردیف هدر
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
