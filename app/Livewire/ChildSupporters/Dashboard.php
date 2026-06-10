<?php

namespace App\Livewire\ChildSupporters;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.child-supporter')]
class Dashboard extends Component
{
    public function mount()
    {
        abort_unless(auth()->check(), 403);

        if (! auth()->user()->can('access-child-supporter-panel')) {
            return redirect()->to(auth()->user()->getPanelRedirectPath());
        }
    }

    public function render()
    {
        return view('livewire.child-supporters.dashboard');
    }
}
