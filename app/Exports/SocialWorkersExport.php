<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SocialWorkersExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Builder $query,
        protected array   $visibleColumns,
        protected array   $availableColumns,
    ) {}

    protected function columnMap(): array
    {
        return [
            'worker_code'         => fn ($w) => $w->worker_code ?? '-',
            'full_name'           => fn ($w) => $w->full_name,
            'national_id'         => fn ($w) => $w->national_id ?? '-',
            'mobile'              => fn ($w) => $w->mobile ?? '-',
            'district'            => fn ($w) => $w->district?->name ?? '-',
            'occupation'          => fn ($w) => $w->occupation?->name ?? '-',
            'academic_level'      => fn ($w) => $w->academicLevel?->title ?? '-',
            'birth_date'          => fn ($w) => $w->formatted_birth_date ?? '-',
            'age'                 => fn ($w) => $w->age ?? '-',
            'start_date'          => fn ($w) => $w->formatted_start_date ?? '-',
            'households_count'    => fn ($w) => $w->guardians_count ?? 0,
            'beneficiaries_count' => fn ($w) => $w->people_count ?? 0,
            'services_count'      => fn ($w) => $w->services_count ?? 0,
            'deliveries_count'    => fn ($w) => $w->service_deliveries_count ?? 0,
            'delivered_value'     => fn ($w) => number_format((int) ($w->service_deliveries_sum_delivered_total_value ?? 0)),
            'is_active'           => fn ($w) => $w->is_active ? 'فعال' : 'غیرفعال',
            'created_at'          => fn ($w) => $w->created_at?->format('Y-m-d'),
        ];
    }

    public function query(): Builder
    {
        return $this->query
            ->withCount(['guardians', 'people', 'services', 'serviceDeliveries'])
            ->withSum('serviceDeliveries as service_deliveries_sum_delivered_total_value', 'delivered_total_value')
            ->with([
                'district:id,name',
                'occupation:id,name',
                'academicLevel:id,title',
            ]);
    }

    public function headings(): array
    {
        return collect($this->visibleColumns)
            ->map(fn ($col) => $this->availableColumns[$col] ?? $col)
            ->toArray();
    }

    public function map($row): array
    {
        $map = $this->columnMap();

        return collect($this->visibleColumns)
            ->map(function (string $col) use ($row, $map) {
                $mapper = $map[$col] ?? fn ($w) => '-';
                return $mapper($row);
            })
            ->toArray();
    }

    public function title(): string
    {
        return 'مددکاران';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $lastCol   = $sheet->getHighestColumn();
                $lastRow   = $sheet->getHighestRow();
                $fullRange = "A1:{$lastCol}{$lastRow}";

                // راست به چپ
                $sheet->setRightToLeft(true);

                // فونت و تراز روی تمام سلول‌ها
                $sheet->getStyle($fullRange)->applyFromArray([
                    'font'      => [
                        'name' => 'B Zar',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'readorder'  => 2, // RTL read order
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
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // ارتفاع ردیف هدر
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
