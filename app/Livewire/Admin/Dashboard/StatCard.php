<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;

class StatCard extends Component
{
    public $title;
    public $value;
    public $suffix = '';
    public $caption = '';
    public $icon;
    public $color; // مثلا: indigo, sky, emerald, violet
    public array $badges = [];

    public function render()
    {
        return view('livewire.admin.dashboard.stat-card');
    }
}
