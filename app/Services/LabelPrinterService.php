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

    private string $layoutMode = 'vertical';

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

        $this->dpi = $overrides['dpi'] ?? $label['dpi'];
        $this->widthMm = $overrides['label_width_mm'] ?? $label['width_mm'];
        $this->heightMm = $overrides['label_height_mm'] ?? $label['height_mm'];
        $this->columns = $overrides['columns'] ?? $label['columns'];
        $this->gapMm = $overrides['gap_mm'] ?? $label['gap_mm'];
        $this->edgeMarginMm = $overrides['edge_margin_mm'] ?? ($label['edge_margin_mm'] ?? 0);
        $this->topMarginMm = $overrides['top_margin_mm'] ?? ($label['top_margin_mm'] ?? 0);
        $this->bottomMarginMm = $overrides['bottom_margin_mm'] ?? ($label['bottom_margin_mm'] ?? 0);
        $this->layoutMode = $overrides['layout_mode'] ?? ($label['layout_mode'] ?? 'vertical');

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

        $zpl = $this->buildBatchZpl($items);

        try {
            $this->sendToPrinter($zpl);

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
     * Test printer connectivity by printing a test label.
     */
    public function printTestLabel(): array
    {
        $zpl = $this->buildTestLabelZpl();

        try {
            $this->sendToPrinter($zpl);

            return ['success' => true, 'message' => 'برچسب تست با موفقیت به پرینتر ارسال شد.'];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => 'خطا در ارتباط با پرینتر: ' . $e->getMessage()];
        }
    }

    /**
     * Build ZPL for a batch of labels arranged in columns.
     * Each physical label contains N client cards side by side.
     */
    private function buildBatchZpl(array $items): string
    {
        $widthDots = $this->mmToDots($this->widthMm);
        $heightDots = $this->mmToDots($this->heightMm);
        $paperWidthDots = $this->paperWidthMm > 0 ? $this->mmToDots($this->paperWidthMm) : $widthDots;
        $labelWidthDots = $this->mmToDots($this->widthMm);
        $gapDots = $this->mmToDots($this->gapMm);
        $edgeMarginDots = $this->mmToDots($this->edgeMarginMm);
        $topMarginDots = $this->mmToDots($this->topMarginMm);
        $bottomMarginDots = $this->mmToDots($this->bottomMarginMm);

        $zpl = '';
        $chunks = array_chunk($items, $this->columns);

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
                $qrY = $topMarginDots;

                if ($this->layoutMode === 'horizontal') {
                    $qrX = $xOffset;
                    $textX = $xOffset + $this->qrSizeDots + $gapDots;
                    $textY = intdiv($heightDots - $this->textFontSize, 2);
                    $maxTextWidth = max(1, $labelWidthDots - $this->qrSizeDots - $gapDots);
                } else {
                    $qrX = $xOffset + intdiv(max(0, $labelWidthDots - $this->qrSizeDots), 2);
                    $textX = $xOffset;
                    $textY = $heightDots - $this->textFontSize - $bottomMarginDots;
                    $maxTextWidth = $labelWidthDots;
                }

                $zpl .= "^FO{$qrX},{$qrY}^BQN,2,{$effectiveMagnification},{$ecChar}\n";
                $zpl .= "^FDQA,{$item['public_code']}^FS\n";

                $personCode = mb_strimwidth($item['person_code'], 0, 20);
                $zpl .= "^FO{$textX},{$textY}^A0N,{$this->textFontSize},{$this->textFontSize}^FB{$maxTextWidth},1,0,C\n";
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
            $qrY = $topMarginDots;

            if ($this->layoutMode === 'horizontal') {
                $qrX = $xOffset;
                $textX = $xOffset + $this->qrSizeDots + $gapDots;
                $textY = intdiv($heightDots - $this->textFontSize, 2);
                $maxTextWidth = max(1, $labelWidthDots - $this->qrSizeDots - $gapDots);
            } else {
                $qrX = $xOffset + intdiv(max(0, $labelWidthDots - $this->qrSizeDots), 2);
                $textX = $xOffset;
                $textY = $heightDots - $this->textFontSize - $bottomMarginDots;
                $maxTextWidth = $labelWidthDots;
            }

            $zpl .= "^FO{$qrX},{$qrY}^BQN,2,{$effectiveMagnification},{$ecChar}\n";
            $zpl .= "^FDQA,{$item['public_code']}^FS\n";

            $zpl .= "^FO{$textX},{$textY}^A0N,{$this->textFontSize},{$this->textFontSize}^FB{$maxTextWidth},1,0,C\n";
            $zpl .= "^FD{$item['person_code']}^FS\n";
        }

        $zpl .= "^XZ\n";

        return $zpl;
    }

    /**
     * Send raw ZPL data to the printer via network or USB.
     */
    private function sendToPrinter(string $zpl): void
    {
        if ($this->connection === 'network') {
            $this->sendViaNetwork($zpl);
        } elseif ($this->connection === 'usb') {
            $this->sendViaUsb($zpl);
        } else {
            throw new RuntimeException("نوع اتصال نامعتبر: {$this->connection}");
        }
    }

    /**
     * Send ZPL to a network printer via TCP socket.
     */
    private function sendViaNetwork(string $zpl): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (! $socket) {
            throw new RuntimeException("عدم امکان اتصال به پرینتر {$this->host}:{$this->port} - {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        $written = fwrite($socket, $zpl);
        fclose($socket);

        if ($written === false || $written === 0) {
            throw new RuntimeException('ارسال داده به پرینتر ناموفق بود.');
        }
    }

    /**
     * Send ZPL to a USB/shared Windows printer.
     */
    private function sendViaUsb(string $zpl): void
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

        $written = fwrite($handle, $zpl);
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
     * Get current printer configuration for display.
     */
    public function getConfigSummary(): array
    {
        return [
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
