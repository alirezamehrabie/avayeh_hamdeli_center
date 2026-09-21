<?php

namespace App\Livewire\Admin\Landing;

use App\Models\LandingBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManageBanners extends Component
{
    public bool $embedded = false;

    public function render()
    {
        $banners = LandingBanner::query()->ordered()->get();

        return view('livewire.admin.landing.manage-banners', [
            'banners' => $banners,
        ]);
    }
}
