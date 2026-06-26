<?php

namespace App\Livewire\DistributionOperators\Gates;

class EntryGate extends DistributionGatePage
{
    protected function gateAbility(): string
    {
        return 'access-distribution-inbound-gate';
    }

    protected function pageTitle(): string
    {
        return 'گیت ورود';
    }

    protected function pageDescription(): string
    {
        return 'صفحه موقت برای عملیات گیت ورود توزیع.';
    }
}
