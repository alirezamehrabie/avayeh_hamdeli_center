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

class GuardiansExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Builder $query,
        protected array   $visibleColumns,
        protected array   $availableColumns,
    ) {}

    protected function columnMap(): array
    {
        return [
            'guardian_code'             => fn ($g) => $g->guardian_code,
            'full_name'                 => fn ($g) => $g->full_name,
            'national_code'             => fn ($g) => $g->national_code,
            'guardian_phone_number'     => fn ($g) => $g->guardian_phone_number,
            'responsible_social_worker' => fn ($g) => $g->socialWorker?->full_name ?? '-',
            'guardian_birth_date'       => fn ($g) => $g->guardian_formatted_birth_date ?? '-',
            'guardian_age'              => fn ($g) => $g->guardian_age ?? '-',
            'occupation'                => fn ($g) => $g->occupation?->name ?? '-',
            'job_type'                  => fn ($g) => $g->jobType?->name ?? '-',
            'economic_decile'           => fn ($g) => $g->economic_decile ? 'دهک ' . $g->economic_decile : '-',
            'average_income'            => fn ($g) => $g->average_income ? number_format((int) $g->average_income) : '-',
            'children_count'            => fn ($g) => $g->children_count,
            'children_in_house'         => fn ($g) => $g->children_in_house,
            'beneficiaries_count'       => fn ($g) => $g->people_count ?? 0,
            'insurance_status'          => fn ($g) => $g->insurance_status ? 'دارد' : 'ندارد',
            'has_vehicle'               => fn ($g) => $g->has_vehicle ? 'دارد' : 'ندارد',
            'district'                  => fn ($g) => $g->residence?->district?->name ?? '-',
            'housing_status'            => fn ($g) => $g->residence?->residenceStatus?->name ?? '-',
            'created_at'                => fn ($g) => $g->created_at?->format('Y-m-d'),
        ];
    }

    public function query(): Builder
    {
        return $this->query
            ->withCount('people')
            ->with([
                'socialWorker:id,first_name,last_name',
                'occupation:id,name',
                'jobType:id,name',
                'residence:id,guardian_id,residence_status_id,district_id',
                'residence.district:id,name',
                'residence.residenceStatus:id,name',
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
                $mapper = $map[$col] ?? fn ($g) => '-';
                return $mapper($row);
            })
            ->toArray();
    }

    public function title(): string
    {
        return 'سرپرستان';
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
