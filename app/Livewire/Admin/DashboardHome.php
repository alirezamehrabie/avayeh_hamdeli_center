<?php

namespace App\Livewire\Admin;

use AllowDynamicProperties;
use App\Models\District;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[AllowDynamicProperties]
#[Layout('layouts.admin')] // متصل کردن به لایوت ساخته شده
class DashboardHome extends Component
{
    #[Url(as: 'section', history: true)]
    public string $activeSection = 'overview';
    public ?int $editingPersonId = null;
    public ?int $editingSocialWorkerId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        $this->normalizeActiveSection();
    }

    #[On('open-dashboard-section')]
    public function selectSection(string $section, ?int $id = null): void
    {
        $this->activeSection = $section;
        $this->normalizeActiveSection();
        $this->editingPersonId = in_array($section, ['person-edit', 'people-fast-create'], true) ? $id : null;
        $this->editingSocialWorkerId = $section === 'social-worker-edit' ? $id : null;
    }

    private function normalizeActiveSection(): void
    {
        $validSections = [
            'overview',
            'people-fast-create',
            'people-list',
            'people-block-list',
            'person-create',
            'person-edit',
            'social-workers-list',
            'social-workers-block-list',
            'social-worker-create',
            'social-worker-edit',
            'guardians-list',
            'advanced-reports',
        ];

        if (!in_array($this->activeSection, $validSections, true)) {
            $this->activeSection = 'overview';
        }
    }

    public function render()
    {
        $isOverview = $this->activeSection === 'overview';

        return view('livewire.admin.dashboard-home', [
            'totalPeople' => $isOverview ? Person::count() : 0,
            'totalSocialWorkers' => $isOverview ? SocialWorker::count() : 0,
            'maleCount' => $isOverview ? Person::where('gender', 'male')->count() : 0,
            'femaleCount' => $isOverview ? Person::where('gender', 'female')->count() : 0,
            'guardianCount' => $isOverview ? Guardian::count() : 0,
            'coveredRegions' => $isOverview
                ? District::whereNotNull('id')->distinct('id')->count('id')
                : 0,
            'latestPeople' => $isOverview
                ? Person::with(['guardian.socialWorker'])->latest()->take(5)->get()
                : collect(),
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
        ]);
    }
}
