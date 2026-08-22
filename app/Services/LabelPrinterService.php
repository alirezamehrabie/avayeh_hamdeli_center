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

    public function __construct()
    {
        $config = config('label-printer');

        $printer = $config['printer'];
        $label = $config['label'];
        $qr = $config['qr_code'];
        $text = $config['text'];

        $this->dpi = $label['dpi'];
        $this->widthMm = $label['width_mm'];
        $this->heightMm = $label['height_mm'];
        $this->columns = $label['columns'];
        $this->gapMm = $label['gap_mm'];

        $this->qrSizeDots = $qr['size_dots'];
        $this->qrMagnification = $qr['magnification'];
        $this->qrErrorCorrection = $qr['error_correction'];

        $this->textFontSize = $text['font_size'];
        $this->textBottomOffset = $text['bottom_offset_dots'];

        $this->connection = $printer['connection'];
        $this->host = $printer['host'];
        $this->port = $printer['port'];
        $this->usbPrinterName = $printer['usb_printer_name'];
        $this->timeout = $printer['timeout'];
    }

    /**
     * Print a batch of client cards with QR codes in 2-column layout.
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
     * Build ZPL for a batch of labels arranged in 2 columns.
     * Each physical label contains 2 client cards side by side.
     */
    private function buildBatchZpl(array $items): string
    {
        $widthDots = $this->mmToDots($this->widthMm);
        $heightDots = $this->mmToDots($this->heightMm);
        $columnWidth = intdiv($widthDots, $this->columns);

        $zpl = '';
        $chunks = array_chunk($items, $this->columns);

        foreach ($chunks as $chunk) {
            $zpl .= "^XA\n";
            $zpl .= "^PW{$widthDots}\n";
            $zpl .= "^LL{$heightDots}\n";
            $zpl .= "^CI28\n";

            foreach ($chunk as $colIndex => $item) {
                $xOffset = $colIndex * $columnWidth;

                $qrX = $xOffset + intdiv($columnWidth - $this->qrSizeDots, 2);
                $qrY = 10;

                $zpl .= "^FO{$qrX},{$qrY}^BQN,2,{$this->qrMagnification},Q\n";
                $zpl .= "^FDQA,{$item['public_code']}^FS\n";

                $textY = $heightDots - $this->textFontSize - $this->textBottomOffset;
                $textX = $xOffset + 5;
                $maxTextWidth = $columnWidth - 10;

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
        $columnWidth = intdiv($widthDots, $this->columns);

        $zpl = "^XA\n";
        $zpl .= "^PW{$widthDots}\n";
        $zpl .= "^LL{$heightDots}\n";
        $zpl .= "^CI28\n";

        $testItems = [
            ['public_code' => 'TEST-001', 'person_code' => 'تست-۱'],
            ['public_code' => 'TEST-002', 'person_code' => 'تست-۲'],
        ];

        foreach ($testItems as $colIndex => $item) {
            $xOffset = $colIndex * $columnWidth;

            $qrX = $xOffset + intdiv($columnWidth - $this->qrSizeDots, 2);
            $qrY = 10;

            $zpl .= "^FO{$qrX},{$qrY}^BQN,2,{$this->qrMagnification},Q\n";
            $zpl .= "^FDQA,{$item['public_code']}^FS\n";

            $textY = $heightDots - $this->textFontSize - $this->textBottomOffset;
            $textX = $xOffset + 5;
            $maxTextWidth = $columnWidth - 10;

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
            'dpi' => $this->dpi,
            'columns' => $this->columns,
        ];
    }
}