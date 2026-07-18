<?php

namespace App\Livewire\Services;

use Livewire\Component;

/**
 * پوسته اولیه بخش «تحویل خدمت به مددجو / سرپرست».
 * فرآیند کامل تحویل در فاز بعدی و بر اساس نیازمندی‌های تکمیلی پیاده‌سازی می‌شود.
 */
class BeneficiaryServiceDelivery extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function render()
    {
        return view('livewire.services.beneficiary-service-delivery');
    }
}
