<?php

namespace App\Services;

use RuntimeException;

class LabelPrinterService
{
    private int $dpi;

    private float $widthMm;

    private float $heightMm;

    private int $columns;

    private float $gapMm;

    private float $qrTextGapMm;

    private int $qrTextRotationDeg;

    private float $edgeMarginMm;

    private float $topMarginMm;

    private float $bottomMarginMm;

    private int $qrSizeDots;

    private int $qrMagnification;

    private string $qrErrorCorrection;

    private int $textFontSize;

    private int $textBottomOffset;

    private string $connection;

    private string $host;

    private int $port;

    private string $usbPrinterName;

    private int $timeout;

    private bool $rotate180 = false;

    private float $paperWidthMm = 0;

    private string $layoutMode = 'horizontal';

    private string $language;

    /**
     * @param  array<string, mixed>  $overrides  Runtime overrides for any config value
     */
    public function __construct(array $overrides = [])
    {
        $config = config('label-printer');

        $printer = $config['printer'];
        $label = $config['label'];
        $qr = $config['qr_code'];
        $text = $config['text'];

        $this->language = $overrides['language'] ?? $printer['language'];
        $this->dpi = $overrides['dpi'] ?? $label['dpi'];
        $this->widthMm = $overrides['label_width_mm'] ?? $label['width_mm'];
        $this->heightMm = $overrides['label_height_mm'] ?? $label['height_mm'];
        $this->columns = 2;
        $this->gapMm = $overrides['gap_mm'] ?? $label['gap_mm'];
        $this->qrTextGapMm = $overrides['qr_text_gap_mm'] ?? ($label['qr_text_gap_mm'] ?? 3);
        $this->qrTextRotationDeg = $overrides['qr_text_rotation_deg'] ?? ($label['qr_text_rotation_deg'] ?? 0);
        $this->edgeMarginMm = $overrides['edge_margin_mm'] ?? ($label['edge_margin_mm'] ?? 0);
        $this->topMarginMm = $overrides['top_margin_mm'] ?? ($label['top_margin_mm'] ?? 0);
        $this->bottomMarginMm = $overrides['bottom_margin_mm'] ?? ($label['bottom_margin_mm'] ?? 0);
        $this->layoutMode = $overrides['layout_mode'] ?? ($label['layout_mode'] ?? 'horizontal');

        $this->qrSizeDots = $overrides['qr_size_dots'] ?? $qr['size_dots'];
        $this->qrMagnification = $overrides['qr_magnification'] ?? $qr['magnification'];
        $this->qrErrorCorrection = $overrides['qr_error_correction'] ?? $qr['error_correction'];

        $this->textFontSize = $overrides['text_font_size'] ?? $text['font_size'];
        $this->textBottomOffset = $overrides['text_bottom_offset_dots'] ?? $text['bottom_offset_dots'];

        $this->rotate180 = $overrides['rotate_180'] ?? false;
        $this->paperWidthMm = $overrides['paper_width_mm'] ?? 0;

        $this->connection = $overrides['connection'] ?? $printer['connection'];
        $this->host = $overrides['host'] ?? $printer['host'];
        $this->port = $overrides['port'] ?? $printer['port'];
        $this->usbPrinterName = $overrides['usb_printer_name'] ?? $printer['usb_printer_name'];
        $this->timeout = $overrides['timeout'] ?? $printer['timeout'];
    }

    /**
     * Print a batch of client cards with QR codes in multi-column layout.
     *
     * @param  array<int, array{public_code: string, person_code: string}>  $items
     */
    public function printBatch(array $items): array
    {
        if (empty($items)) {
            return ['success' => false, 'message' => 'هیچ آیتمی برای چاپ وجود ندارد.'];
        }

        $data = $this->buildBatchData($items);

        try {
            $this->sendToPrinter($data);

            return [
                'success' => true,
                'message' => count($items) . ' کارت با موفقیت به پرینتر ارسال شد.',
                'count' => count($items),
            ];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => 'خطا در ارتباط با پرینتر: ' . $e->getMessage()];
        }
    }

    /**
     * Generate raw printer data for a batch without sending it.
     * Used for file download.
     *
     * @param  array<int, array{public_code: string, person_code: string}>  $items
     */
    public function generateBatchData(array $items): string
    {
        return $this->buildBatchData($items);
    }

    /**
     * Get the file extension appropriate for the current printer language.
     */
    public function getFileExtension(): string
    {
        return $this->language === 'tspl' ? 'prn' : 'zpl';
    }

    /**
     * Get the MIME type for the current printer language output.
     */
    public function getMimeType(): string
    {
        return 'application/octet-stream';
    }

    /**
     * Test printer connectivity by printing a test label.
     */
    public function printTestLabel(): array
    {
        $data = $this->language === 'tspl'
            ? $this->buildTestLabelTspl()
            : $this->buildTestLabelZpl();

        try {
            $this->sendToPrinter($data);

            return ['success' => true, 'message' => 'برچسب تست با موفقیت به پرینتر ارسال شد.'];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => 'خطا در ارتباط با پرینتر: ' . $e->getMessage()];
        }
    }

    /**
     * Build printer data for a batch, dispatching by language.
     *
     * @param  array<int, array{public_code: string, person_code: string}>  $items
     */
    private function buildBatchData(array $items): string
    {
        return $this->language === 'tspl'
            ? $this->buildBatchTspl($items)
            : $this->buildBatchZpl($items);
    }

    /**
     * Build ZPL for a batch of labels arranged in columns.
     * Emits all labels in a single stream so the roll feeds continuously.
     */
    private function buildBatchZpl(array $items): string
    {
        $widthDots = $this->mmToDots($this->widthMm);
        $heightDots = $this->mmToDots($this->heightMm);
        $paperWidthDots = $this->paperWidthMm > 0 ? $this->mmToDots($this->paperWidthMm) : $widthDots;
        $labelWidthDots = $this->mmToDots($this->widthMm);
        $gapDots = $this->mmToDots($this->gapMm);
        $qrTextGapDots = $this->mmToDots($this->qrTextGapMm);
        $edgeMarginDots = $this->mmToDots($this->edgeMarginMm);
        $topMarginDots = $this->mmToDots($this->topMarginMm);
        $bottomMarginDots = $this->mmToDots($this->bottomMarginMm);

        $chunks = array_chunk($items, $this->columns);
        $zpl = '';

        foreach ($chunks as $chunk) {
            $zpl .= "^XA\n";
            $zpl .= "^PW{$paperWidthDots}\n";
            $zpl .= "^LL{$heightDots}\n";
            $zpl .= "^CI28\n";

            if ($this->rotate180) {
                $zpl .= "^POI\n";
            }

            foreach ($chunk as $colIndex => $item) {
                $xOffset = $edgeMarginDots + ($colIndex * ($labelWidthDots + $gapDots));
                $ecChar = strtoupper(substr($this->qrErrorCorrection, 0, 1));
                $effectiveMagnification = max(1, min(10, (int) round($this->qrSizeDots / 25)));
                $layout = $this->buildLayoutPositions($xOffset, $labelWidthDots, $heightDots, $gapDots, $qrTextGapDots, $topMarginDots, $bottomMarginDots);

                $zpl .= "^FO{$layout['qr_x']},{$layout['qr_y']}^BQN,2,{$effectiveMagnification},{$ecChar}\n";
                $zpl .= "^FDQA,{$item['public_code']}^FS\n";

                $personCode = mb_strimwidth($item['person_code'], 0, 20);
                $zpl .= "^FO{$layout['text_x']},{$layout['text_y']}^A0{$this->zplTextOrientation()},{$this->textFontSize},{$this->textFontSize}^FB{$layout['max_text_width']},1,0,C\n";
                $zpl .= "^FD{$personCode}^FS\n";
            }

            $zpl .= "^XZ\n";
        }

        return $zpl;
    }

    /**
     * Build a simple test label ZPL.
     */
    private function buildTestLabelZpl(): string
    {
        $widthDots = $this->mmToDots($this->widthMm);
        $heightDots = $this->mmToDots($this->heightMm);
        $paperWidthDots = $this->paperWidthMm > 0 ? $this->mmToDots($this->paperWidthMm) : $widthDots;
        $labelWidthDots = $this->mmToDots($this->widthMm);
        $gapDots = $this->mmToDots($this->gapMm);
        $qrTextGapDots = $this->mmToDots($this->qrTextGapMm);
        $edgeMarginDots = $this->mmToDots($this->edgeMarginMm);
        $topMarginDots = $this->mmToDots($this->topMarginMm);
        $bottomMarginDots = $this->mmToDots($this->bottomMarginMm);

        $zpl = "^XA\n";
        $zpl .= "^PW{$paperWidthDots}\n";
        $zpl .= "^LL{$heightDots}\n";
        $zpl .= "^CI28\n";

        if ($this->rotate180) {
            $zpl .= "^POI\n";
        }

        $testItems = [
            ['public_code' => 'TEST-001', 'person_code' => 'تست-۱'],
            ['public_code' => 'TEST-002', 'person_code' => 'تست-۲'],
        ];

        $testItems = array_slice($testItems, 0, $this->columns);

        foreach ($testItems as $colIndex => $item) {
            $xOffset = $edgeMarginDots + ($colIndex * ($labelWidthDots + $gapDots));
            $ecChar = strtoupper(substr($this->qrErrorCorrection, 0, 1));
            $effectiveMagnification = max(1, min(10, (int) round($this->qrSizeDots / 25)));
            $layout = $this->buildLayoutPositions($xOffset, $labelWidthDots, $heightDots, $gapDots, $qrTextGapDots, $topMarginDots, $bottomMarginDots);

            $zpl .= "^FO{$layout['qr_x']},{$layout['qr_y']}^BQN,2,{$effectiveMagnification},{$ecChar}\n";
            $zpl .= "^FDQA,{$item['public_code']}^FS\n";

            $zpl .= "^FO{$layout['text_x']},{$layout['text_y']}^A0{$this->zplTextOrientation()},{$this->textFontSize},{$this->textFontSize}^FB{$layout['max_text_width']},1,0,C\n";
            $zpl .= "^FD{$item['person_code']}^FS\n";
        }

        $zpl .= "^XZ\n";

        return $zpl;
    }

    /**
     * Build TSPL for a batch of labels arranged in columns.
     * Emits a single continuous print job so the roll feeds without blank labels.
     */
    private function buildBatchTspl(array $items): string
    {
        $heightDots = $this->mmToDots($this->heightMm);
        $labelWidthDots = $this->mmToDots($this->widthMm);
        $gapDots = $this->mmToDots($this->gapMm);
        $qrTextGapDots = $this->mmToDots($this->qrTextGapMm);
        $edgeMarginDots = $this->mmToDots($this->edgeMarginMm);
        $topMarginDots = $this->mmToDots($this->topMarginMm);
        $bottomMarginDots = $this->mmToDots($this->bottomMarginMm);

        $chunks = array_chunk($items, $this->columns);

        $tspl = 'SIZE ' . $this->formatMm($this->paperWidthMm) . ' mm,' . $this->formatMm($this->heightMm) . " mm\r\n";
        $tspl .= 'GAP ' . $this->formatMm($this->gapMm) . " mm,0\r\n";
        $tspl .= "REFERENCE 0,0\r\n";

        if ($this->rotate180) {
            $tspl .= "DIRECTION 1\r\n";
        }

        foreach ($chunks as $chunk) {
            $tspl .= "CLS\r\n";

            foreach ($chunk as $colIndex => $item) {
                $xOffset = $edgeMarginDots + ($colIndex * ($labelWidthDots + $gapDots));
                $ecChar = strtoupper(substr($this->qrErrorCorrection, 0, 1));
                $effectiveMagnification = max(1, min(10, (int) round($this->qrSizeDots / 25)));
                $layout = $this->buildLayoutPositions($xOffset, $labelWidthDots, $heightDots, $gapDots, $qrTextGapDots, $topMarginDots, $bottomMarginDots);

                $tspl .= "QRCODE {$layout['qr_x']},{$layout['qr_y']},L,{$effectiveMagnification},A,0,M{$ecChar},\"{$item['public_code']}\"\r\n";

                $personCode = mb_strimwidth($item['person_code'], 0, 20);
                $textX = $layout['text_x'] + intdiv($layout['max_text_width'], 2);
                $tspl .= "TEXT {$textX},{$layout['text_y']},\"3\",{$this->tsplTextRotation()},1,1,\"{$personCode}\"\r\n";
            }

            $tspl .= "PRINT 1\r\n";
        }

        return $tspl;
    }

    /**
     * Build a simple test label in TSPL.
     */
    private function buildTestLabelTspl(): string
    {
        $heightDots = $this->mmToDots($this->heightMm);
        $labelWidthDots = $this->mmToDots($this->widthMm);
        $gapDots = $this->mmToDots($this->gapMm);
        $qrTextGapDots = $this->mmToDots($this->qrTextGapMm);
        $edgeMarginDots = $this->mmToDots($this->edgeMarginMm);
        $topMarginDots = $this->mmToDots($this->topMarginMm);
        $bottomMarginDots = $this->mmToDots($this->bottomMarginMm);

        $tspl = 'SIZE ' . $this->formatMm($this->paperWidthMm) . ' mm,' . $this->formatMm($this->heightMm) . " mm\r\n";
        $tspl .= 'GAP ' . $this->formatMm($this->gapMm) . " mm,0\r\n";
        $tspl .= "REFERENCE 0,0\r\n";
        $tspl .= "CLS\r\n";

        if ($this->rotate180) {
            $tspl .= "DIRECTION 1\r\n";
        }

        $testItems = [
            ['public_code' => 'TEST-001', 'person_code' => 'تست-۱'],
            ['public_code' => 'TEST-002', 'person_code' => 'تست-۲'],
        ];

        $testItems = array_slice($testItems, 0, $this->columns);

        foreach ($testItems as $colIndex => $item) {
            $xOffset = $edgeMarginDots + ($colIndex * ($labelWidthDots + $gapDots));
            $ecChar = strtoupper(substr($this->qrErrorCorrection, 0, 1));
            $effectiveMagnification = max(1, min(10, (int) round($this->qrSizeDots / 25)));
            $layout = $this->buildLayoutPositions($xOffset, $labelWidthDots, $heightDots, $gapDots, $qrTextGapDots, $topMarginDots, $bottomMarginDots);

            $tspl .= "QRCODE {$layout['qr_x']},{$layout['qr_y']},L,{$effectiveMagnification},A,0,M{$ecChar},\"{$item['public_code']}\"\r\n";

            $textX = $layout['text_x'] + intdiv($layout['max_text_width'], 2);
            $tspl .= "TEXT {$textX},{$layout['text_y']},\"3\",{$this->tsplTextRotation()},1,1,\"{$item['person_code']}\"\r\n";
        }

        $tspl .= "PRINT 1\r\n";

        return $tspl;
    }

    /**
     * Send raw printer data to the printer via network or USB.
     */
    private function sendToPrinter(string $data): void
    {
        if ($this->connection === 'network') {
            $this->sendViaNetwork($data);
        } elseif ($this->connection === 'usb') {
            $this->sendViaUsb($data);
        } else {
            throw new RuntimeException("نوع اتصال نامعتبر: {$this->connection}");
        }
    }

    /**
     * Send raw data to a network printer via TCP socket.
     */
    private function sendViaNetwork(string $data): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (! $socket) {
            throw new RuntimeException("عدم امکان اتصال به پرینتر {$this->host}:{$this->port} - {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        $written = fwrite($socket, $data);
        fclose($socket);

        if ($written === false || $written === 0) {
            throw new RuntimeException('ارسال داده به پرینتر ناموفق بود.');
        }
    }

    /**
     * Send raw data to a USB/shared Windows printer.
     */
    private function sendViaUsb(string $data): void
    {
        if (empty($this->usbPrinterName)) {
            throw new RuntimeException('نام پرینتر USB تنظیم نشده است. لطفاً در تنظیمات، نام پرینتر را وارد کنید.');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            throw new RuntimeException('چاپ USB فقط در ویندوز پشتیبانی می‌شود.');
        }

        $printerPath = $this->usbPrinterName;
        if (! str_starts_with($printerPath, '\\\\')) {
            $printerPath = '\\\\localhost\\' . $printerPath;
        }

        $handle = @fopen($printerPath, 'w');

        if (! $handle) {
            throw new RuntimeException("عدم امکان اتصال به پرینتر USB: {$this->usbPrinterName}");
        }

        $written = fwrite($handle, $data);
        fclose($handle);

        if ($written === false || $written === 0) {
            throw new RuntimeException('ارسال داده به پرینتر USB ناموفق بود.');
        }
    }

    /**
     * Convert millimeters to printer dots based on DPI.
     */
    private function mmToDots(float $mm): int
    {
        return (int) round($mm * $this->dpi / 25.4);
    }

    /**
     * Format a millimeter value for TSPL commands.
     */
    private function formatMm(float $mm): string
    {
        $formatted = rtrim(rtrim(number_format($mm, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    /**
     * Normalize the configured text rotation.
     */
    private function normalizeRotation(): int
    {
        $rotation = $this->qrTextRotationDeg % 360;

        if ($rotation < 0) {
            $rotation += 360;
        }

        return in_array($rotation, [0, 90, 180, 270], true) ? $rotation : 0;
    }

    /**
     * Map rotation to a ZPL field orientation.
     */
    private function zplTextOrientation(): string
    {
        return match ($this->normalizeRotation()) {
            90 => 'R',
            180 => 'I',
            270 => 'B',
            default => 'N',
        };
    }

    /**
     * Map rotation to a TSPL rotation value.
     */
    private function tsplTextRotation(): int
    {
        return $this->normalizeRotation();
    }

    /**
     * Keep the QR and client code aligned as a single layout unit.
     *
     * @return array{qr_x: int, qr_y: int, text_x: int, text_y: int, max_text_width: int}
     */
    private function buildLayoutPositions(
        int $xOffset,
        int $labelWidthDots,
        int $heightDots,
        int $gapDots,
        int $qrTextGapDots,
        int $topMarginDots,
        int $bottomMarginDots
    ): array {
        if ($this->layoutMode === 'horizontal') {
            return [
                'qr_x' => $xOffset,
                'qr_y' => $topMarginDots,
                'text_x' => $xOffset + $this->qrSizeDots + $qrTextGapDots,
                'text_y' => intdiv($heightDots - $this->textFontSize, 2),
                'max_text_width' => max(1, $labelWidthDots - $this->qrSizeDots - $qrTextGapDots),
            ];
        }

        return [
            'qr_x' => $xOffset + intdiv(max(0, $labelWidthDots - $this->qrSizeDots), 2),
            'qr_y' => $topMarginDots,
            'text_x' => $xOffset,
            'text_y' => $heightDots - $this->textFontSize - $bottomMarginDots,
            'max_text_width' => $labelWidthDots,
        ];
    }

    /**
     * Get current printer configuration for display.
     */
    public function getConfigSummary(): array
    {
        return [
            'language' => $this->language,
            'connection' => $this->connection,
            'host' => $this->host,
            'port' => $this->port,
            'usb_printer_name' => $this->usbPrinterName,
            'label_width_mm' => $this->widthMm,
            'label_height_mm' => $this->heightMm,
            'paper_width_mm' => $this->paperWidthMm,
            'dpi' => $this->dpi,
            'columns' => $this->columns,
            'gap_mm' => $this->gapMm,
            'qr_text_gap_mm' => $this->qrTextGapMm,
            'qr_text_rotation_deg' => $this->qrTextRotationDeg,
            'edge_margin_mm' => $this->edgeMarginMm,
            'top_margin_mm' => $this->topMarginMm,
            'bottom_margin_mm' => $this->bottomMarginMm,
            'qr_size_dots' => $this->qrSizeDots,
            'qr_magnification' => $this->qrMagnification,
            'qr_error_correction' => $this->qrErrorCorrection,
            'text_font_size' => $this->textFontSize,
            'text_bottom_offset_dots' => $this->textBottomOffset,
            'rotate_180' => $this->rotate180,
            'layout_mode' => $this->layoutMode,
        ];
    }
}
