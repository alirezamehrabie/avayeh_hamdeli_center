<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;

class StatCard extends Component
{
    public $title;
    public $value;
    public $suffix = '';
    public $icon;
    public $color; // مثلا: blue, green, red, yellow
    public array $badges = [];

    public function render()
    {
        return view('livewire.admin.dashboard.stat-card');
    }
}
