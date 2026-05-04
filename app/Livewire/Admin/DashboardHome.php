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
use Illuminate\Http\Request;

#[AllowDynamicProperties]
#[Layout('layouts.admin')] // متصل کردن به لایوت ساخته شده
class DashboardHome extends Component
{
    public string $activeSection = 'overview';
    public ?int $editingPersonId = null;
    public ?int $editingSocialWorkerId = null;

    public function mount(Request $request): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if ($request->query('section') === 'advanced-reports') {
            $this->activeSection = 'advanced-reports';
        }
    }

    #[On('open-dashboard-section')]
    public function selectSection(string $section, ?int $id = null): void
    {
        $this->activeSection = $section;
        $this->editingPersonId = in_array($section, ['person-edit', 'people-fast-create'], true) ? $id : null;
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
            'coveredRegions' => District::whereNotNull('id')
                ->distinct('id')
                ->count('id'),
            'latestPeople' => Person::with(['guardian.socialWorker']) // لود کردن زنجیره‌ای روابط
            ->latest()
                ->take(5)
                ->get(),
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
        ]);
    }
}
