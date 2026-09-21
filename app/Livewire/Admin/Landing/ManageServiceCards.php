<?php

namespace App\Livewire\Admin\Landing;

use App\Models\LandingServiceCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManageServiceCards extends Component
{
    public bool $embedded = false;

    public function render()
    {
        $cards = LandingServiceCard::query()->ordered()->get()->groupBy('rail_row');

        return view('livewire.admin.landing.manage-service-cards', [
            'cards' => $cards,
        ]);
    }
}
