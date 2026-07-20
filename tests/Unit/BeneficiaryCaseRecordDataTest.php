<?php

namespace Tests\Unit;

use App\Data\People\BeneficiaryCaseRecordData;
use PHPUnit\Framework\TestCase;

class BeneficiaryCaseRecordDataTest extends TestCase
{
    public function test_record_data_normalizes_optional_text_and_preserves_typed_values(): void
    {
        $data = new BeneficiaryCaseRecordData(
            recordType: 'invoice',
            title: '  Medication invoice  ',
            description: '  ',
            recordedAt: '2026-07-03',
            amount: 2500000,
            referenceNumber: '  INV-100  ',
        );

        $this->assertSame([
            'record_type' => 'invoice',
            'title' => 'Medication invoice',
            'description' => null,
            'recorded_at' => '2026-07-03',
            'amount' => 2500000,
            'reference_number' => 'INV-100',
        ], $data->toAttributes());
    }
}
