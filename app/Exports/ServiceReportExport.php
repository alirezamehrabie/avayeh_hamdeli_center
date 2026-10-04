<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\Services\ServiceReports;
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
 *
 * Supports dynamic column selection configured via the ServiceReports settings modal.
 */
class ServiceReportExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    /**
     * @var array<string>
     */
    protected array $selectedColumns;

    /**
     * @param  Collection  $groupedDeliveries  Output of ServiceReports::getGroupedDeliveriesProperty()
     * @param  array<string, string>  $unitOptions  Map of unit key => Persian label
     * @param  array<string>|null  $selectedColumns  Keys of columns to export
     */
    public function __construct(
        protected Service $service,
        protected Collection $groupedDeliveries,
        protected array $unitOptions = [],
        ?array $selectedColumns = null,
    ) {
        $available = array_keys(ServiceReports::EXPORT_COLUMNS);

        if ($selectedColumns === null || empty($selectedColumns)) {
            $this->selectedColumns = ServiceReports::DEFAULT_EXPORT_COLUMNS;
        } else {
            // Support legacy column key 'recipient_type' by mapping it to 'entry_type' if needed
            $mapped = array_map(fn ($c) => $c === 'recipient_type' ? 'entry_type' : $c, $selectedColumns);
            $selectedLookup = array_flip($mapped);
            $this->selectedColumns = array_values(array_filter(
                $available,
                fn ($col) => isset($selectedLookup[$col])
            ));

            if (empty($this->selectedColumns)) {
                $this->selectedColumns = ServiceReports::DEFAULT_EXPORT_COLUMNS;
            }
        }
    }

    public function headings(): array
    {
        return array_map(
            fn ($col) => ServiceReports::EXPORT_COLUMNS[$col] ?? $col,
            $this->selectedColumns
        );
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->groupedDeliveries as $group) {
            $personCode = $group->person?->person_code;
            $guardianCode = $group->guardian?->guardian_code;
            $recipientCode = $personCode ?: ($guardianCode ?: '-');

            $entryType = $this->formatEntryType($group);
            $needLevel = $this->resolveNeedLevel($group);
            $supportOrg = $this->resolveSupportOrganization($group);

            foreach ($group->deliveries as $delivery) {
                $unitKey = $delivery->serviceCategory?->unit;
                $unitLabel = $unitKey
                    ? ($this->unitOptions[$unitKey] ?? $unitKey)
                    : '-';

                $channel = $delivery->delivery_channel ?: ($this->service->delivery_channel ?? null);
                $channelLabel = $channel ? (Service::DELIVERY_CHANNEL_OPTIONS[$channel] ?? $channel) : '-';

                $values = [
                    'recipient_name' => (string) ($group->recipientName ?: '-'),
                    'entry_type' => $entryType,
                    'recipient_type' => $entryType,
                    'recipient_national_id' => (string) ($group->recipientNationalId ?: '-'),
                    'recipient_code' => (string) $recipientCode,
                    'mobile' => (string) ($group->mobile ?: '-'),
                    'need_level' => $needLevel,
                    'support_organization' => $supportOrg,
                    'delivery_channel' => $channelLabel,
                    'service_category' => (string) ($delivery->serviceCategory?->name ?: '-'),
                    'delivered_quantity' => number_format((float) $delivery->delivered_quantity, 2),
                    'unit' => $unitLabel,
                    'delivered_total_value' => number_format((int) $delivery->delivered_total_value),
                    'social_worker' => (string) ($delivery->display_social_worker_name ?: '-'),
                    'delivered_at' => $delivery->delivered_at
                        ? Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
                        : ($delivery->created_at ? Jalalian::fromDateTime($delivery->created_at)->format('Y/m/d') : '-'),
                    'notes' => (string) ($delivery->notes ?: '-'),
                    'created_at' => $delivery->created_at
                        ? Jalalian::fromDateTime($delivery->created_at)->format('Y/m/d')
                        : '-',
                    'creator' => (string) ($delivery->creator?->name ?: '-'),
                ];

                $row = [];
                foreach ($this->selectedColumns as $col) {
                    $row[] = $values[$col] ?? '-';
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function formatEntryType(object $group): string
    {
        if ($group->person) {
            return 'شخصی (مددجو)';
        }
        if ($group->guardian) {
            return 'خانوادگی (سرپرست)';
        }

        return 'ثبت دستی';
    }

    protected function resolveNeedLevel(object $group): string
    {
        if ($group->person) {
            return $group->person->needsLevel?->levelType?->title ?: '-';
        }

        if ($group->guardian) {
            $people = $group->guardian->relationLoaded('people')
                ? $group->guardian->people
                : $group->guardian->people()->with(['needsLevel.levelType'])->get();

            $levels = $people
                ->map(fn ($p) => $p->needsLevel?->levelType?->title)
                ->filter()
                ->unique()
                ->values();

            return $levels->isNotEmpty() ? $levels->implode('، ') : '-';
        }

        return '-';
    }

    protected function resolveSupportOrganization(object $group): string
    {
        if ($group->person) {
            $orgName = $group->person->supportCoverage?->organization?->name
                ?: $group->person->supportCoverage?->other_organization_name;

            return $orgName ?: '-';
        }

        if ($group->guardian) {
            $people = $group->guardian->relationLoaded('people')
                ? $group->guardian->people
                : $group->guardian->people()->with(['supportCoverage.organization'])->get();

            $orgs = $people
                ->map(fn ($p) => $p->supportCoverage?->organization?->name ?: $p->supportCoverage?->other_organization_name)
                ->filter()
                ->unique()
                ->values();

            return $orgs->isNotEmpty() ? $orgs->implode('، ') : '-';
        }

        return '-';
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
