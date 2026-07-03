<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use App\Models\Person;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BeneficiaryCaseFileExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    public function __construct(
        protected Person $person,
        protected Collection $timeline,
    ) {}

    public function headings(): array
    {
        return [
            'تاریخ',
            'نوع',
            'عنوان',
            'زیرعنوان/توضیحات',
            'مقدار/روش',
            'ارزش/فاکتور',
            'جزئیات',
            'پیوست‌ها',
        ];
    }

    public function array(): array
    {
        return $this->timeline
            ->map(fn (array $row): array => [
                $this->formatDate($row['date'] ?? null),
                $row['badge'] ?? '-',
                $row['title'] ?? '-',
                $row['subtitle'] ?? '-',
                $row['quantity'] ?? '-',
                $row['value'] ?? '-',
                $this->formatDetails($row['details'] ?? []),
                $this->formatAttachments($row['attachments'] ?? collect()),
            ])
            ->all();
    }

    public function title(): string
    {
        return mb_substr('پرونده مددجو', 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->setRightToLeft(true);

                $sheet->insertNewRowBefore(1, 4);
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                $sheet->setCellValue('A1', 'پرونده مددجو');
                $sheet->setCellValue('A2', $this->personName());
                $sheet->setCellValue('A3', 'کد مددجو: '.($this->person->person_code ?: '-').' | کد ملی: '.($this->person->national_id ?: '-'));

                $sheet->getStyle("A1:{$lastCol}".($lastRow + 4))->applyFromArray([
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

                $sheet->getStyle('A1:A3')->applyFromArray([
                    'font' => [
                        'name' => 'B Zar',
                        'bold' => true,
                        'size' => 13,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
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

                $sheet->getRowDimension(5)->setRowHeight(28);
            },
        ];
    }

    protected function formatDate($date): string
    {
        if (! $date) {
            return '-';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }

    protected function formatDetails(array $details): string
    {
        if ($details === []) {
            return '-';
        }

        return collect($details)
            ->map(fn ($value, string $label): string => $label.': '.$value)
            ->implode(' | ');
    }

    protected function formatAttachments($attachments): string
    {
        $attachments = collect($attachments);

        if ($attachments->isEmpty()) {
            return '-';
        }

        return $attachments
            ->pluck('original_name')
            ->filter()
            ->implode(' | ');
    }

    protected function personName(): string
    {
        return $this->person->full_name ?: trim($this->person->first_name.' '.$this->person->last_name) ?: '-';
    }
}
