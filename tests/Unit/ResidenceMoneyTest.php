<?php

namespace Tests\Unit;

use App\Models\Residence;
use PHPUnit\Framework\TestCase;

class ResidenceMoneyTest extends TestCase
{
    public function test_money_amount_conversion_uses_shared_scale(): void
    {
        $this->assertSame(125000, Residence::toStoredMoneyAmount(12500000));
        $this->assertSame(12500000, Residence::fromStoredMoneyAmount(125000));
        $this->assertNull(Residence::toStoredMoneyAmount(null));
        $this->assertNull(Residence::toStoredMoneyAmount(''));
        $this->assertNull(Residence::fromStoredMoneyAmount(null));
        $this->assertNull(Residence::fromStoredMoneyAmount(''));
    }
}
