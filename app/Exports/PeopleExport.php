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

class PeopleExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Builder $query,
        protected array   $visibleColumns,
        protected array   $availableColumns,
    ) {}

    protected function columnMap(): array
    {
        return [
            'person_code'                        => fn ($p) => $p->person_code,
            'full_name'                          => fn ($p) => $p->full_name,
            'guardian_beneficiary_with_code'     => fn ($p) => $p->guardian_beneficiary_with_code,
            'first_name'                         => fn ($p) => $p->first_name,
            'last_name'                          => fn ($p) => $p->last_name,
            'national_id'                        => fn ($p) => $p->national_id,
            'phone_number'                       => fn ($p) => $p->phone_number,
            'gender'                             => fn ($p) => match ($p->gender) {
                'male'   => 'پسر',
                'female' => 'دختر',
                default  => '-',
            },
            'sadaat_status'                      => fn ($p) => match ($p->sadaat_status) {
                'sadaat'  => 'سادات',
                'general' => 'عام',
                default   => '-',
            },
            'birth_date'                         => fn ($p) => implode('/', array_filter([
                $p->birth_year,
                $p->birth_month ? str_pad((string) $p->birth_month, 2, '0', STR_PAD_LEFT) : null,
                $p->birth_day   ? str_pad((string) $p->birth_day,   2, '0', STR_PAD_LEFT) : null,
            ])),
            'birth_year'                         => fn ($p) => $p->birth_year,
            'birth_month'                        => fn ($p) => $p->birth_month,
            'beneficiary_injury_disability_type' => fn ($p) => $p->harmTypes->pluck('title')->implode('، '),
            'related_description'                => fn ($p) => $p->disabilityType?->name ?? '-',
            'responsible_social_worker'          => fn ($p) => $p->guardian?->socialWorker?->full_name ?? '-',
            'created_at'                         => fn ($p) => $p->created_at?->format('Y-m-d'),
        ];
    }

    public function query(): Builder
    {
        return $this->query->with([
            'disabilityType:id,name',
            'harmTypes:id,title',
            'guardian:id,social_worker_id,guardian_code,first_name,last_name',
            'guardian.socialWorker:id,first_name,last_name',
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
                $mapper = $map[$col] ?? fn ($p) => '-';
                return $mapper($row);
            })
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
