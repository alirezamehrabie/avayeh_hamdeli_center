<?php

namespace App\Livewire\Admin;

use AllowDynamicProperties;
use App\Models\DashboardReminder;
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
    public ?int $editingGuardianId = null;
    public string $newReminderTitle = '';
    public string $newReminderCategory = 'today_tasks';

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
        $this->editingGuardianId = $section === 'guardian-edit' ? $id : null;
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
            'guardian-edit',
            'advanced-reports',
            'advanced-beneficiary-report',
            'advanced-supervisor-report',
            'advanced-social-worker-report',
            'system-settings-user-management',
            'system-settings-user-account',
        ];

        if (!in_array($this->activeSection, $validSections, true)) {
            $this->activeSection = 'overview';
        }
    }

    public function addReminder(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $validated = $this->validate([
            'newReminderTitle' => ['required', 'string', 'max:255'],
            'newReminderCategory' => ['required', 'in:today_tasks,pending_approvals,contract_deadlines,required_reports'],
        ]);

        DashboardReminder::create([
            'user_id' => auth()->id(),
            'title' => $validated['newReminderTitle'],
            'category' => $validated['newReminderCategory'],
            'is_done' => false,
        ]);

        $this->newReminderTitle = '';
    }

    public function toggleReminder(int $reminderId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $reminder = DashboardReminder::query()
            ->where('user_id', auth()->id())
            ->findOrFail($reminderId);

        $reminder->update(['is_done' => !$reminder->is_done]);
    }

    public function deleteReminder(int $reminderId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        DashboardReminder::query()
            ->where('user_id', auth()->id())
            ->findOrFail($reminderId)
            ->delete();
    }

    public function render()
    {
        $isOverview = $this->activeSection === 'overview';
        $birthMonthChart = collect();

        if ($isOverview) {
            $monthCounts = Person::query()
                ->selectRaw('birth_month, COUNT(*) as total')
                ->whereBetween('birth_month', [1, 12])
                ->groupBy('birth_month')
                ->pluck('total', 'birth_month');

            $birthMonthChart = collect(Person::$months)
                ->map(function (string $label, int $month) use ($monthCounts) {
                    return [
                        'month' => $month,
                        'label' => $label,
                        'count' => (int) ($monthCounts[$month] ?? 0),
                    ];
                })
                ->values();
        }

        $reminders = $isOverview
            ? DashboardReminder::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
            : collect();

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
            'birthMonthChart' => $birthMonthChart,
            'reminders' => $reminders,
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
            'editingGuardian' => $this->editingGuardianId ? Guardian::find($this->editingGuardianId) : null,
        ]);
    }
}
