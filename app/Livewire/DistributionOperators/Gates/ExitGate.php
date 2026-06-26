<?php

namespace App\Livewire\DistributionOperators\Gates;

class ExitGate extends DistributionGatePage
{
    protected function gateAbility(): string
    {
        return 'access-distribution-outbound-gate';
    }

    protected function pageTitle(): string
    {
        return 'گیت خروج';
    }

    protected function pageDescription(): string
    {
        return 'صفحه موقت برای عملیات گیت خروج توزیع.';
    }
}
