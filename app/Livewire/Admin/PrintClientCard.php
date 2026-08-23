<?php

namespace App\Livewire\Admin;

use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\SocialWorker;
use App\Services\LabelPrinterService;
use App\Services\QrIdentityService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrintClientCard extends Component
{
    use WithFileUploads;

    public string $search = '';

    public string $searchField = 'all';

    /** @var array<int, array{id: int, person_code: string, full_name: string, national_id: string}> */
    public array $printList = [];

    public bool $showSearchResults = false;

    public string $socialWorkerSearch = '';

    public ?int $selectedSocialWorkerId = null;

    public array $socialWorkerOptions = [];

    public bool $loadingSocialWorkerClients = false;

    // Printer connection settings
    public string $printerConnection = '';

    public string $printerHost = '';

    public int $printerPort = 9100;

    public string $printerUsbName = '';

    /** @var array<int, string> */
    public array $detectedLocalPrinters = [];

    public bool $canDetectLocalPrinters = false;

    public bool $showPrinterSettings = false;

    public bool $printingDirectly = false;

    // Advanced label layout settings
    public float $labelWidthMm = 45;

    public float $labelHeightMm = 30;

    public float $paperWidthMm = 96;

    public float $gapMm = 3;

    public float $qrTextGapMm = 3;

    public int $qrTextRotationDeg = 0;

    public float $edgeMarginMm = 1.5;

    public float $topMarginMm = 3;

    public float $bottomMarginMm = 3;

    public int $dpi = 203;

    public int $columns = 2;

    public int $qrSizeDots = 180;

    public int $qrMagnification = 4;

    public string $qrErrorCorrection = 'M';

    public int $textFontSize = 24;

    public int $textBottomOffsetDots = 10;

    public bool $rotate180 = false;

    public string $layoutMode = 'horizontal';

    public bool $showPreview = false;

    public bool $showAdvancedLayout = false;

    public bool $showVisualEditor = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $layoutImportFile = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $config = config('label-printer');
        $printer = $config['printer'];
        $label = $config['label'];
        $qr = $config['qr_code'];
        $text = $config['text'];

        $this->printerConnection = $printer['connection'] ?? 'network';
        $this->printerHost = $printer['host'] ?? '192.168.1.100';
        $this->printerPort = $printer['port'] ?? 9100;
        $this->printerUsbName = $printer['usb_printer_name'] ?? '';
        $this->canDetectLocalPrinters = $this->isWindowsEnvironment();

        $this->paperWidthMm = $label['paper_width_mm'] ?? 96;
        $this->labelWidthMm = $label['width_mm'] ?? 45;
        $this->labelHeightMm = $label['height_mm'] ?? 30;
        $this->gapMm = $label['gap_mm'] ?? 3;
        $this->qrTextGapMm = $label['qr_text_gap_mm'] ?? 3;
        $this->qrTextRotationDeg = $label['qr_text_rotation_deg'] ?? 0;
        $this->edgeMarginMm = $label['edge_margin_mm'] ?? 1.5;
        $this->topMarginMm = $label['top_margin_mm'] ?? 3;
        $this->bottomMarginMm = $label['bottom_margin_mm'] ?? 3;
        $this->dpi = $label['dpi'] ?? 203;
        $this->columns = $label['columns'] ?? 2;
        $this->layoutMode = $label['layout_mode'] ?? 'horizontal';

        $this->qrSizeDots = $qr['size_dots'] ?? 180;
        $this->qrMagnification = $qr['magnification'] ?? 4;
        $this->qrErrorCorrection = $qr['error_correction'] ?? 'M';

        $this->textFontSize = $text['font_size'] ?? 24;
        $this->textBottomOffsetDots = $text['bottom_offset_dots'] ?? 10;

        $this->loadDetectedLocalPrinters();
    }

    public function updatedSearch(): void
    {
        $this->showSearchResults = mb_strlen(trim($this->search)) >= 2;
    }

    public function updatedColumns(): void
    {
        $this->columns = 2;
    }

    public function getSearchResultsProperty(): array
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $query = Person::query()->select(['id', 'first_name', 'last_name', 'national_id', 'person_code']);

        if ($this->searchField === 'national_id') {
            $query->where('national_id', 'LIKE', "%{$term}%");
        } elseif ($this->searchField === 'full_name') {
            $normalized = Person::normalizeSearchText($term);
            $query->where('normalized_full_name', 'LIKE', "%{$normalized}%");
        } else {
            $normalized = Person::normalizeSearchText($term);
            $query->where(function ($q) use ($term, $normalized) {
                $q->where('normalized_full_name', 'LIKE', "%{$normalized}%")
                    ->orWhere('national_id', 'LIKE', "%{$term}%")
                    ->orWhere('person_code', 'LIKE', "%{$term}%");
            });
        }

        return $query->limit(20)->get()->map(fn (Person $p) => [
            'id' => $p->id,
            'full_name' => $p->full_name ?: trim($p->first_name.' '.$p->last_name) ?: '-',
            'national_id' => $p->national_id ?: '-',
            'person_code' => $p->person_code ?: '-',
        ])->toArray();
    }

    public function addToPrintList(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        foreach ($this->printList as $item) {
            if ($item['id'] === $personId) {
                session()->flash('error', 'این مددجو قبلاً به لیست چاپ اضافه شده است.');

                return;
            }
        }

        $person = Person::query()->find($personId);

        if (! $person) {
            session()->flash('error', 'مددجو یافت نشد.');

            return;
        }

        $this->printList[] = [
            'id' => $person->id,
            'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
            'national_id' => $person->national_id ?: '-',
            'person_code' => $person->person_code ?: '-',
        ];

        $this->search = '';
        $this->showSearchResults = false;
    }

    public function removeFromPrintList(int $index): void
    {
        if (isset($this->printList[$index])) {
            unset($this->printList[$index]);
            $this->printList = array_values($this->printList);
        }
    }

    public function clearPrintList(): void
    {
        $this->printList = [];
    }

    public function printDirectly(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');

            return;
        }

        if ($this->printerConnection === 'browser') {
            $this->printWithBrowserDialog();

            return;
        }

        // The bridge flow never touches the printer from the server: the raw
        // label data is handed to the browser, which forwards it to the local
        // print agent running on the operator's own Windows PC. This is what
        // makes direct printing possible while the site runs on a shared host.
        if ($this->printerConnection === 'bridge') {
            $items = $this->buildPrintItems();
            $printer = app()->makeWith(LabelPrinterService::class, ['overrides' => $this->getPrinterOverrides()]);
            $this->dispatchBridgePrint($printer->generateBatchData($items));

            return;
        }

        if (! $this->showPreview) {
            session()->flash('error', 'ابتدا پیش‌نمایش چاپ را باز کنید.');

            return;
        }

        $this->printingDirectly = true;

        $items = $this->buildPrintItems();

        $printer = app()->makeWith(LabelPrinterService::class, ['overrides' => $this->getPrinterOverrides()]);
        $result = $printer->printBatch($items);

        $this->printingDirectly = false;

        if ($result['success']) {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function printTestLabel(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if ($this->printerConnection === 'browser') {
            $this->printBrowserTestLabel();

            return;
        }

        if ($this->printerConnection === 'bridge') {
            $printer = app()->makeWith(LabelPrinterService::class, ['overrides' => $this->getPrinterOverrides()]);
            $this->dispatchBridgePrint($printer->generateTestLabel());

            return;
        }

        $printer = app()->makeWith(LabelPrinterService::class, ['overrides' => $this->getPrinterOverrides()]);
        $result = $printer->printTestLabel();

        if ($result['success']) {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function togglePrinterSettings(): void
    {
        $this->showPrinterSettings = ! $this->showPrinterSettings;
    }

    public function refreshDetectedLocalPrinters(): void
    {
        $this->loadDetectedLocalPrinters();

        if (empty($this->detectedLocalPrinters)) {
            session()->flash('error', $this->canDetectLocalPrinters
                ? 'هیچ پرینتر ویندوزی روی این سیستم شناسایی نشد.'
                : 'شناسایی خودکار پرینترهای محلی فقط روی ویندوز فعال است.');

            return;
        }

        session()->flash('success', count($this->detectedLocalPrinters).' پرینتر محلی شناسایی شد.');
    }

    public function toggleAdvancedLayout(): void
    {
        $this->showAdvancedLayout = ! $this->showAdvancedLayout;
    }

    public function togglePreview(): void
    {
        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');

            return;
        }

        $this->showPreview = ! $this->showPreview;
    }

    public function toggleVisualEditor(): void
    {
        $this->showVisualEditor = ! $this->showVisualEditor;
    }

    public function resetLayoutDefaults(): void
    {
        $config = config('label-printer');
        $label = $config['label'];
        $qr = $config['qr_code'];
        $text = $config['text'];

        $this->paperWidthMm = $label['paper_width_mm'] ?? 96;
        $this->labelWidthMm = $label['width_mm'] ?? 45;
        $this->labelHeightMm = $label['height_mm'] ?? 30;
        $this->gapMm = $label['gap_mm'] ?? 3;
        $this->qrTextGapMm = $label['qr_text_gap_mm'] ?? 3;
        $this->qrTextRotationDeg = $label['qr_text_rotation_deg'] ?? 0;
        $this->edgeMarginMm = $label['edge_margin_mm'] ?? 1.5;
        $this->topMarginMm = $label['top_margin_mm'] ?? 3;
        $this->bottomMarginMm = $label['bottom_margin_mm'] ?? 3;
        $this->dpi = $label['dpi'] ?? 203;
        $this->columns = $label['columns'] ?? 2;
        $this->layoutMode = $label['layout_mode'] ?? 'horizontal';

        $this->qrSizeDots = $qr['size_dots'] ?? 180;
        $this->qrMagnification = $qr['magnification'] ?? 4;
        $this->qrErrorCorrection = $qr['error_correction'] ?? 'M';

        $this->textFontSize = $text['font_size'] ?? 24;
        $this->textBottomOffsetDots = $text['bottom_offset_dots'] ?? 10;
        $this->rotate180 = false;

        session()->flash('success', 'تنظیمات چیدمان به مقادیر پیش‌فرض بازگشت.');
    }

    public function exportLayoutSettings(): ?\Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $payload = [
            'type' => 'avayeh-hamdeli-label-layout',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'settings' => $this->currentLayoutSettings(),
        ];

        $filename = 'label-layout-settings-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function importLayoutSettings(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (! $this->layoutImportFile) {
            session()->flash('error', 'ابتدا فایل تنظیمات را انتخاب کنید.');

            return;
        }

        $applied = 0;

        try {
            if (strtolower((string) $this->layoutImportFile->getClientOriginalExtension()) !== 'json') {
                session()->flash('error', 'فایل تنظیمات باید با پسوند json. باشد.');

                return;
            }

            $contents = (string) $this->layoutImportFile->get();

            if (strlen($contents) > 512 * 1024) {
                session()->flash('error', 'حجم فایل تنظیمات بیش از حد مجاز است.');

                return;
            }

            $decoded = json_decode($contents, true);

            if (! is_array($decoded)) {
                session()->flash('error', 'محتوای فایل یک JSON معتبر نیست.');

                return;
            }

            $incoming = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded;

            foreach ($this->layoutSettingsMap() as $key => $definition) {
                if (array_key_exists($key, $incoming)) {
                    $raw = $incoming[$key];
                } elseif (array_key_exists($definition['prop'], $incoming)) {
                    $raw = $incoming[$definition['prop']];
                } else {
                    continue;
                }

                $value = $this->sanitizeLayoutSettingValue($definition, $raw);

                if ($value === null) {
                    continue;
                }

                $this->{$definition['prop']} = $value;
                $applied++;
            }
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'خواندن فایل تنظیمات ممکن نشد.');

            return;
        } finally {
            $this->layoutImportFile = null;
        }

        if ($applied === 0) {
            session()->flash('error', 'هیچ مقدار معتبری برای چیدمان برچسب در فایل پیدا نشد.');

            return;
        }

        session()->flash('success', "تنظیمات چیدمان برچسب با موفقیت از فایل بارگذاری شد ({$applied} مورد).");
    }

    /**
     * Canonical map of exportable/importable layout settings with their
     * validation bounds. Numeric values outside the UI input ranges are
     * clamped; unknown enum values are skipped during import.
     *
     * @return array<string, array{prop: string, type: string, min?: float|int, max?: float|int, options?: list<int|string>}>
     */
    private function layoutSettingsMap(): array
    {
        return [
            'label_width_mm' => ['prop' => 'labelWidthMm', 'type' => 'float', 'min' => 10, 'max' => 200],
            'label_height_mm' => ['prop' => 'labelHeightMm', 'type' => 'float', 'min' => 10, 'max' => 200],
            'paper_width_mm' => ['prop' => 'paperWidthMm', 'type' => 'float', 'min' => 10, 'max' => 300],
            'gap_mm' => ['prop' => 'gapMm', 'type' => 'float', 'min' => 0, 'max' => 20],
            'qr_text_gap_mm' => ['prop' => 'qrTextGapMm', 'type' => 'float', 'min' => 0, 'max' => 20],
            'qr_text_rotation_deg' => ['prop' => 'qrTextRotationDeg', 'type' => 'enum', 'options' => [0, 90, 180, 270]],
            'edge_margin_mm' => ['prop' => 'edgeMarginMm', 'type' => 'float', 'min' => 0, 'max' => 20],
            'top_margin_mm' => ['prop' => 'topMarginMm', 'type' => 'float', 'min' => 0, 'max' => 20],
            'bottom_margin_mm' => ['prop' => 'bottomMarginMm', 'type' => 'float', 'min' => 0, 'max' => 20],
            'dpi' => ['prop' => 'dpi', 'type' => 'enum', 'options' => [203, 300, 600]],
            'qr_size_dots' => ['prop' => 'qrSizeDots', 'type' => 'int', 'min' => 50, 'max' => 500],
            'qr_magnification' => ['prop' => 'qrMagnification', 'type' => 'int', 'min' => 1, 'max' => 10],
            'qr_error_correction' => ['prop' => 'qrErrorCorrection', 'type' => 'enum', 'options' => ['L', 'M', 'Q', 'H']],
            'text_font_size' => ['prop' => 'textFontSize', 'type' => 'int', 'min' => 8, 'max' => 120],
            'text_bottom_offset_dots' => ['prop' => 'textBottomOffsetDots', 'type' => 'int', 'min' => 0, 'max' => 500],
            'rotate_180' => ['prop' => 'rotate180', 'type' => 'bool'],
            'layout_mode' => ['prop' => 'layoutMode', 'type' => 'enum', 'options' => ['horizontal', 'vertical']],
        ];
    }

    /**
     * @return array<string, float|int|bool|string>
     */
    private function currentLayoutSettings(): array
    {
        $settings = [];

        foreach ($this->layoutSettingsMap() as $key => $definition) {
            $settings[$key] = $this->{$definition['prop']};
        }

        return $settings;
    }

    /**
     * @param  array{prop: string, type: string, min?: float|int, max?: float|int, options?: list<int|string>}  $definition
     * @return float|int|bool|string|null null when the value is not usable
     */
    private function sanitizeLayoutSettingValue(array $definition, mixed $raw): float|int|bool|string|null
    {
        switch ($definition['type']) {
            case 'float':
                if (! is_numeric($raw)) {
                    return null;
                }

                return round(max($definition['min'], min($definition['max'], (float) $raw)), 2);

            case 'int':
                if (! is_numeric($raw)) {
                    return null;
                }

                return (int) round(max($definition['min'], min($definition['max'], (float) $raw)));

            case 'bool':
                return $this->normalizeBooleanValue($raw);

            case 'enum':
                foreach ($definition['options'] as $option) {
                    if (is_int($option)) {
                        if (is_numeric($raw) && (int) round((float) $raw) === $option) {
                            return $option;
                        }
                    } elseif (is_string($option) && is_scalar($raw)) {
                        if (strtoupper(trim((string) $raw)) === strtoupper($option)) {
                            return $option;
                        }
                    }
                }

                return null;
        }

        return null;
    }

    private function normalizeBooleanValue(mixed $raw): ?bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return (int) $raw === 1;
        }

        if (is_string($raw)) {
            $normalized = strtolower(trim($raw));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return null;
    }

    public function getPreviewItemsProperty(): array
    {
        $service = app(QrIdentityService::class);
        $items = [];

        foreach ($this->printList as $item) {
            $identity = QrIdentity::query()
                ->where('subject_type', QrIdentity::SUBJECT_PERSON)
                ->where('subject_id', $item['id'])
                ->where('status', QrIdentity::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if (! $identity) {
                $issued = $service->ensureActiveFor(
                    Person::query()->find($item['id']),
                    auth()->id()
                );
                $identity = $issued;
            }

            $items[] = [
                'qr_svg' => $identity->qr_svg,
                'person_code' => $item['person_code'],
                'full_name' => $item['full_name'],
            ];
        }

        return $items;
    }

    public function downloadPrintFile(): ?\Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');

            return null;
        }

        $items = $this->buildPrintItems();
        $printer = app()->makeWith(LabelPrinterService::class, ['overrides' => $this->getPrinterOverrides()]);
        $data = $printer->generateBatchData($items);
        $extension = $printer->getFileExtension();
        $filename = 'labels-'.now()->format('Y-m-d-His').'.'.$extension;

        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, $filename, [
            'Content-Type' => $printer->getMimeType(),
        ]);
    }

    public function exportToCsv(): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');

            return redirect()->back();
        }

        $filename = 'client-cards-'.now()->format('Y-m-d-His').'.csv';
        $rows = $this->buildExportRows();

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['QR Code', 'نام کامل', 'کد ملی', 'کد مددجو'], ',');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ',');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportToExcel(): ?\Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');

            return null;
        }

        $rows = $this->buildExportRows();
        $filename = 'client-cards-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new class($rows) implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
        {
            private array $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['QR Code', 'نام کامل', 'کد ملی', 'کد مددجو'];
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
                            'font' => ['name' => 'B Zar', 'size' => 11],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'readorder' => 2,
                            ],
                        ]);

                        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                            'font' => ['name' => 'B Zar', 'bold' => true, 'size' => 12],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        $sheet->getRowDimension(1)->setRowHeight(28);
                    },
                ];
            }
        }, $filename);
    }

    public function updatedSocialWorkerSearch(): void
    {
        $term = trim($this->socialWorkerSearch);

        if (mb_strlen($term) < 2) {
            $this->socialWorkerOptions = [];

            return;
        }

        $normalized = Person::normalizeSearchText($term);

        $this->socialWorkerOptions = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->where(function ($q) use ($term, $normalized) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$normalized}%"])
                    ->orWhere('worker_code', 'LIKE', "%{$term}%");
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(fn (SocialWorker $sw) => [
                'id' => $sw->id,
                'name' => trim($sw->first_name.' '.$sw->last_name),
                'code' => (string) $sw->worker_code,
            ])
            ->toArray();
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedSocialWorkerId = $socialWorkerId;
        $this->socialWorkerSearch = '';
        $this->socialWorkerOptions = [];
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->selectedSocialWorkerId = null;
    }

    public function addSocialWorkerClientsToPrintList(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (! $this->selectedSocialWorkerId) {
            session()->flash('error', 'لطفاً ابتدا یک مددکار انتخاب کنید.');

            return;
        }

        $this->loadingSocialWorkerClients = true;

        $existingIds = collect($this->printList)->pluck('id')->all();

        $people = Person::query()
            ->select(['people.id', 'people.first_name', 'people.last_name', 'people.national_id', 'people.person_code'])
            ->join('guardians', 'people.guardian_id', '=', 'guardians.id')
            ->where('guardians.social_worker_id', $this->selectedSocialWorkerId)
            ->whereNotIn('id', $existingIds)
            ->get();

        $addedCount = 0;

        foreach ($people as $person) {
            $this->printList[] = [
                'id' => $person->id,
                'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
                'national_id' => $person->national_id ?: '-',
                'person_code' => $person->person_code ?: '-',
            ];
            $addedCount++;
        }

        $this->loadingSocialWorkerClients = false;

        if ($addedCount === 0) {
            session()->flash('error', 'تمام مددجویان این مددکار قبلاً به لیست چاپ اضافه شده‌اند.');
        } else {
            session()->flash('success', "{$addedCount} مددجو از مددکار انتخاب‌شده به لیست چاپ اضافه شد.");
        }
    }

    public function getSelectedSocialWorkerProperty(): ?SocialWorker
    {
        if (! $this->selectedSocialWorkerId) {
            return null;
        }

        return SocialWorker::query()->find($this->selectedSocialWorkerId);
    }

    public function render()
    {
        return view('livewire.admin.print-client-card');
    }

    /**
     * Build runtime overrides array from current UI property values.
     */
    private function getPrinterOverrides(): array
    {
        return [
            'connection' => $this->printerConnection,
            'host' => $this->printerHost,
            'port' => $this->printerPort,
            'usb_printer_name' => $this->printerUsbName,
            'label_width_mm' => $this->labelWidthMm,
            'label_height_mm' => $this->labelHeightMm,
            'paper_width_mm' => $this->paperWidthMm,
            'gap_mm' => $this->gapMm,
            'qr_text_gap_mm' => $this->qrTextGapMm,
            'qr_text_rotation_deg' => $this->qrTextRotationDeg,
            'dpi' => $this->dpi,
            'columns' => 2,
            'qr_size_dots' => $this->qrSizeDots,
            'qr_magnification' => $this->qrMagnification,
            'qr_error_correction' => $this->qrErrorCorrection,
            'text_font_size' => $this->textFontSize,
            'text_bottom_offset_dots' => $this->textBottomOffsetDots,
            'rotate_180' => $this->rotate180,
            'edge_margin_mm' => $this->edgeMarginMm,
            'top_margin_mm' => $this->topMarginMm,
            'bottom_margin_mm' => $this->bottomMarginMm,
            'layout_mode' => $this->layoutMode,
        ];
    }

    /**
     * Build items array for direct ZPL printing with QR codes.
     */
    private function buildPrintItems(): array
    {
        $service = app(QrIdentityService::class);
        $items = [];

        foreach ($this->printList as $item) {
            $identity = QrIdentity::query()
                ->where('subject_type', QrIdentity::SUBJECT_PERSON)
                ->where('subject_id', $item['id'])
                ->where('status', QrIdentity::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if (! $identity) {
                $issued = $service->ensureActiveFor(
                    Person::query()->find($item['id']),
                    auth()->id()
                );
                $identity = $issued;
            }

            $items[] = [
                'public_code' => $identity->public_code,
                'person_code' => $item['person_code'],
            ];
        }

        return $items;
    }

    private function buildExportRows(): array
    {
        $service = app(QrIdentityService::class);
        $rows = [];

        foreach ($this->printList as $item) {
            $identity = QrIdentity::query()
                ->where('subject_type', QrIdentity::SUBJECT_PERSON)
                ->where('subject_id', $item['id'])
                ->where('status', QrIdentity::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if (! $identity) {
                $issued = $service->ensureActiveFor(
                    Person::query()->find($item['id']),
                    auth()->id()
                );
                $identity = $issued;
            }

            $rows[] = [
                $identity->public_code,
                $item['full_name'],
                $item['national_id'],
                $item['person_code'],
            ];
        }

        return $rows;
    }

    /**
     * Hand raw label data to the browser so it can be forwarded to the local
     * print bridge agent (see print-bridge/README.md). The printer name and
     * bridge address live on the operator's PC, not in server settings.
     */
    private function dispatchBridgePrint(string $data): void
    {
        $this->dispatch('client-card-bridge-print', payload: base64_encode($data));
    }

    private function printWithBrowserDialog(): void
    {
        $html = view('livewire.admin.print-client-card-document', [
            'items' => $this->getPreviewItemsProperty(),
            'paperWidthMm' => $this->paperWidthMm,
            'labelWidthMm' => $this->labelWidthMm,
            'labelHeightMm' => $this->labelHeightMm,
            'gapMm' => $this->gapMm,
            'qrTextGapMm' => $this->qrTextGapMm,
            'qrTextRotationDeg' => $this->qrTextRotationDeg,
            'edgeMarginMm' => $this->edgeMarginMm,
            'topMarginMm' => $this->topMarginMm,
            'bottomMarginMm' => $this->bottomMarginMm,
            'dpi' => $this->dpi,
            'qrSizeDots' => $this->qrSizeDots,
            'textFontSize' => $this->textFontSize,
            'layoutMode' => $this->layoutMode,
            'rotate180' => $this->rotate180,
            'title' => 'چاپ کارت مددجو',
            'testMode' => false,
        ])->render();

        $this->dispatch('client-card-browser-print', html: $html, title: 'چاپ کارت مددجو');
    }

    private function printBrowserTestLabel(): void
    {
        $qrSvg = (string) QrCode::format('svg')
            ->size(220)
            ->margin(0)
            ->generate('TEST-LOCAL');

        $html = view('livewire.admin.print-client-card-document', [
            'items' => [[
                'qr_svg' => $qrSvg,
                'person_code' => 'تست چاپ',
                'full_name' => 'تست چاپ',
            ]],
            'paperWidthMm' => $this->paperWidthMm,
            'labelWidthMm' => $this->labelWidthMm,
            'labelHeightMm' => $this->labelHeightMm,
            'gapMm' => $this->gapMm,
            'qrTextGapMm' => $this->qrTextGapMm,
            'qrTextRotationDeg' => $this->qrTextRotationDeg,
            'edgeMarginMm' => $this->edgeMarginMm,
            'topMarginMm' => $this->topMarginMm,
            'bottomMarginMm' => $this->bottomMarginMm,
            'dpi' => $this->dpi,
            'qrSizeDots' => $this->qrSizeDots,
            'textFontSize' => $this->textFontSize,
            'layoutMode' => $this->layoutMode,
            'rotate180' => $this->rotate180,
            'title' => 'برچسب تست کارت مددجو',
            'testMode' => true,
        ])->render();

        $this->dispatch('client-card-browser-print', html: $html, title: 'برچسب تست کارت مددجو');
    }

    private function loadDetectedLocalPrinters(): void
    {
        $this->detectedLocalPrinters = $this->canDetectLocalPrinters
            ? $this->detectWindowsPrinters()
            : [];

        if (empty($this->detectedLocalPrinters)) {
            return;
        }

        if ($this->printerConnection === 'usb' && $this->printerUsbName === '') {
            $this->printerUsbName = $this->detectedLocalPrinters[0];
        }
    }

    /**
     * @return array<int, string>
     */
    private function detectWindowsPrinters(): array
    {
        if (! function_exists('shell_exec')) {
            return [];
        }

        $commands = [
            'powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"',
            'wmic printer get name',
        ];

        $printers = [];

        foreach ($commands as $command) {
            $output = @shell_exec($command);

            if (! is_string($output) || trim($output) === '') {
                continue;
            }

            foreach (preg_split('/\r\n|\r|\n/', $output) as $line) {
                $printer = trim($line);

                if ($printer === '' || strcasecmp($printer, 'Name') === 0) {
                    continue;
                }

                $printers[] = $printer;
            }

            if (! empty($printers)) {
                break;
            }
        }

        $printers = array_values(array_unique($printers));
        sort($printers, SORT_NATURAL | SORT_FLAG_CASE);

        return $printers;
    }

    private function isWindowsEnvironment(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
