<?php

namespace Tests\Unit;

use App\Services\LabelPrinterService;
use Tests\TestCase;

class LabelPrinterServiceTest extends TestCase
{
    public function test_batch_tspl_uses_the_physical_45_by_30_layout(): void
    {
        $service = new LabelPrinterService([
            'label_width_mm' => 45,
            'label_height_mm' => 30,
            'paper_width_mm' => 96,
            'gap_mm' => 3,
            'edge_margin_mm' => 1.5,
            'top_margin_mm' => 3,
            'bottom_margin_mm' => 3,
            'dpi' => 203,
            'columns' => 2,
        ]);

        $data = $service->generateBatchData([
            ['public_code' => 'PQR-AAAA1111', 'person_code' => '14001'],
            ['public_code' => 'PQR-BBBB2222', 'person_code' => '14002'],
        ]);

        $this->assertStringContainsString("SIZE 96 mm,30 mm", $data);
        $this->assertStringContainsString("GAP 3 mm,0", $data);
        $this->assertStringContainsString("QRCODE 12,24", $data);
        $this->assertStringContainsString("QRCODE 396,24", $data);
        $this->assertStringContainsString("TEXT 294,108", $data);
        $this->assertStringContainsString("TEXT 678,108", $data);
        $this->assertSame(1, substr_count($data, 'PRINT 1'));
    }
}
