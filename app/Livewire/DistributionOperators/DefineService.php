<?php

namespace App\Livewire\DistributionOperators;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class DefineService extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);
    }

    public function render()
    {
        return view('livewire.distribution-operators.define-service');
    }
}
