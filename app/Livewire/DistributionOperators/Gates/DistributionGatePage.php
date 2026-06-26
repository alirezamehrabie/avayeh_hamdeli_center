<?php

namespace App\Livewire\DistributionOperators\Gates;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
abstract class DistributionGatePage extends Component
{
    abstract protected function gateAbility(): string;

    abstract protected function pageTitle(): string;

    abstract protected function pageDescription(): string;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can($this->gateAbility()), 403);
    }

    public function render()
    {
        return view('livewire.distribution-operators.gates.placeholder', [
            'title' => $this->pageTitle(),
            'description' => $this->pageDescription(),
        ]);
    }
}
