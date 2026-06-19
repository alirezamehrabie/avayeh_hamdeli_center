<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\On;
use Livewire\Component;

class DashboardSidebar extends Component
{
    public string $activeSection = 'overview';

    public function mount(string $activeSection = 'overview'): void
    {
        $this->activeSection = $activeSection;
    }

    #[On('open-dashboard-section')]
    public function syncRequestedSection(string $section, ?int $id = null): void
    {
        $this->activeSection = $section;
    }

    #[On('dashboard-section-changed')]
    public function syncActiveSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function render()
    {
        return view('livewire.admin.dashboard-sidebar');
    }
}
