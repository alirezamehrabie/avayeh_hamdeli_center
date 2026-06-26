<?php

namespace App\Livewire\DistributionOperators\Gates;

class DeliveryGate extends DistributionGatePage
{
    protected function gateAbility(): string
    {
        return 'access-distribution-delivery-gate';
    }

    protected function pageTitle(): string
    {
        return 'گیت تحویل';
    }

    protected function pageDescription(): string
    {
        return 'صفحه موقت برای عملیات گیت تحویل توزیع.';
    }
}
