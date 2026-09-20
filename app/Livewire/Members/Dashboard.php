<?php

namespace App\Livewire\Members;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('پنل اعضا | آوای همدلی')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.members.dashboard', [
            'person' => Auth::guard('member')->user(),
        ]);
    }
}
