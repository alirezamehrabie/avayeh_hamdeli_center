<?php

namespace App\Livewire\Admin;

use AllowDynamicProperties;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[AllowDynamicProperties]
#[Layout('layouts.admin')] // متصل کردن به لایوت ساخته شده
class DashboardHome extends Component
{
    public string $activeSection = 'overview';
    public ?int $editingPersonId = null;
    public ?int $editingSocialWorkerId = null;

    #[On('open-dashboard-section')]
    public function selectSection(string $section, ?int $id = null): void
    {
        $this->activeSection = $section;
        $this->editingPersonId = $section === 'person-edit' ? $id : null;
        $this->editingSocialWorkerId = $section === 'social-worker-edit' ? $id : null;
    }

    public function render()
    {
        return view('livewire.admin.dashboard-home', [
            'totalPeople' => Person::count(),
            'totalSocialWorkers' => SocialWorker::count(),
            'maleCount' => Person::where('gender', 'male')->count(),
            'femaleCount' => Person::where('gender', 'female')->count(),
            'guardianCount' => Guardian::count(),
            'latestPeople' => Person::with(['guardian.socialWorker']) // لود کردن زنجیره‌ای روابط
            ->latest()
                ->take(50)
                ->get(),
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
        ]);
    }
}
