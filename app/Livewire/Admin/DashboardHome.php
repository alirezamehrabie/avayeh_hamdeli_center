<?php

namespace App\Livewire\Admin;

use AllowDynamicProperties;
use App\Models\DashboardReminder;
use App\Models\District;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\SponsorProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[AllowDynamicProperties]
#[Layout('layouts.admin')] // متصل کردن به لایوت ساخته شده
class DashboardHome extends Component
{
    #[Url(as: 'section', history: true)]
    public string $activeSection = 'overview';

    #[Url(as: 'id', history: true)]
    public ?int $sectionContextId = null;

    public ?int $editingPersonId = null;

    public ?int $editingSocialWorkerId = null;

    public ?int $editingGuardianId = null;

    public ?int $editingSponsorId = null;

    public ?int $editingServiceId = null;

    public ?int $editingActivityId = null;

    public ?int $scanningActivityId = null;

    #[Url(as: 'activity', history: true)]
    public ?int $activityContextId = null;

    public ?int $serviceReportServiceId = null;

    public ?int $caseFilePersonId = null;

    public bool $showDeletedUsers = false;

    public string $newReminderTitle = '';

    public string $newReminderCategory = 'today_tasks';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        if (! request()->has('section') && request()->routeIs('admin.user-list*')) {
            $this->activeSection = 'system-settings-user-list';
            $this->showDeletedUsers = request()->routeIs('admin.user-list.deleted');
        } elseif (! request()->has('section') && (request()->routeIs('admin.user-definition') || request()->routeIs('admin.user-management*'))) {
            $this->activeSection = 'system-settings-user-definition';
        } elseif (! request()->has('section') && request()->routeIs('admin.user-account')) {
            $this->activeSection = 'system-settings-user-account';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-definition')) {
            $this->activeSection = 'service-definition';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-management')) {
            $this->activeSection = 'service-management';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-archive')) {
            $this->activeSection = 'service-archive';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-list')) {
            $this->activeSection = 'service-list';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-delivery')) {
            $this->activeSection = 'service-delivery';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-reports')) {
            $this->activeSection = 'advanced-service-report';
        } elseif (! request()->has('section') && request()->routeIs('admin.activity-definition')) {
            $this->activeSection = 'activity-definition';
        } elseif (! request()->has('section') && request()->routeIs('admin.activity-list')) {
            $this->activeSection = 'activity-list';
        } elseif (! request()->has('section') && request()->routeIs('admin.activity-operator-assignments')) {
            $this->activeSection = 'activity-operator-assignments';
        } elseif (! request()->has('section') && request()->routeIs('admin.special-features.id-card-scanner')) {
            $this->activeSection = 'special-features-id-card-scanner';
        } elseif (! request()->has('section') && request()->routeIs('admin.people.case-file', 'people.case-file')) {
            $this->activeSection = 'beneficiary-case-file';
        }
        $this->normalizeActiveSection();
        $this->syncSectionContext();
        $this->syncActivityContext();
    }

    #[On('open-dashboard-section')]
    public function selectSection(string $section, ?int $id = null): void
    {
        $this->activeSection = $section;
        $this->normalizeActiveSection();
        $this->sectionContextId = $this->sectionUsesContextId($this->activeSection) ? $id : null;
        $this->syncSectionContext();
        $this->editingActivityId = $this->activeSection === 'activity-definition' ? $id : null;
        $this->scanningActivityId = $this->activeSection === 'activity-scanner' ? $id : null;
        $this->activityContextId = in_array($this->activeSection, ['activity-definition', 'activity-scanner'], true) ? $id : null;
        $this->showDeletedUsers = false;
        $this->dispatchDashboardSectionChanged();
    }

    public function updatedActiveSection(): void
    {
        $this->normalizeActiveSection();
        $this->syncSectionContext();
        $this->syncActivityContext();
        $this->dispatchDashboardSectionChanged();
    }

    public function updatedSectionContextId(): void
    {
        $this->syncSectionContext();
    }

    public function updatedActivityContextId(): void
    {
        $this->syncActivityContext();
    }

    private function syncActivityContext(): void
    {
        if ($this->activeSection === 'activity-definition') {
            $this->editingActivityId = $this->activityContextId;
            $this->scanningActivityId = null;

            return;
        }

        if ($this->activeSection === 'activity-scanner') {
            $this->scanningActivityId = $this->activityContextId;
            $this->editingActivityId = null;

            return;
        }

        $this->activityContextId = null;
        $this->editingActivityId = null;
        $this->scanningActivityId = null;
    }

    private function syncSectionContext(): void
    {
        if (! $this->sectionUsesContextId($this->activeSection)) {
            $this->sectionContextId = null;
        }

        $id = $this->sectionContextId;

        $this->editingPersonId = in_array($this->activeSection, ['person-edit', 'people-fast-create'], true) ? $id : null;
        $this->editingSocialWorkerId = $this->activeSection === 'social-worker-edit' ? $id : null;
        $this->editingGuardianId = $this->activeSection === 'guardian-edit' ? $id : null;
        $this->editingSponsorId = $this->activeSection === 'child-supporter-sponsor-edit' ? $id : null;
        $this->editingServiceId = $this->activeSection === 'service-definition' ? $id : null;
        $this->serviceReportServiceId = $this->activeSection === 'advanced-service-report' ? $id : null;
        $this->caseFilePersonId = $this->activeSection === 'beneficiary-case-file' ? $id : null;
    }

    private function sectionUsesContextId(string $section): bool
    {
        return in_array($section, [
            'person-edit',
            'people-fast-create',
            'social-worker-edit',
            'guardian-edit',
            'child-supporter-sponsor-edit',
            'service-definition',
            'advanced-service-report',
            'beneficiary-case-file',
        ], true);
    }

    private function normalizeActiveSection(): void
    {
        if ($this->activeSection === 'system-settings-user-management') {
            $this->activeSection = 'system-settings-user-definition';
        }

        if ($this->activeSection === 'define-services') {
            $this->activeSection = 'service-definition';
        }

        $user = auth()->user();
        $validSections = ['overview', 'system-settings-user-account'];

        if ($user?->can('manage-people')) {
            $validSections[] = 'people-list';
            $validSections[] = 'people-incomplete-cases';
        }

        if ($user?->can('people-register')) {
            $validSections[] = 'people-fast-create';
            $validSections[] = 'person-create';
        }

        if ($user?->can('people-edit')) {
            $validSections[] = 'person-edit';
        }

        if ($user?->can('people-delete')) {
            $validSections[] = 'people-block-list';
        }

        if ($user?->can('access-admin-panel')) {
            $validSections[] = 'beneficiary-case-file';
            $validSections[] = 'advanced-service-report';
            $validSections[] = 'advanced-operator-report';
            $validSections[] = 'advanced-gate-report';
            $validSections[] = 'child-supporter-sponsor-registration';
            $validSections[] = 'child-supporter-sponsor-edit';
            $validSections[] = 'child-supporter-sponsor-list';
            $validSections[] = 'special-features-id-card-scanner';
            $validSections[] = 'activity-list';
            $validSections[] = 'activity-scanner';
        }

        if ($user?->can('full-access')) {
            array_push(
                $validSections,
                'social-workers-list',
                'social-workers-block-list',
                'social-worker-create',
                'social-worker-edit',
                'guardians-list',
                'guardians-block-list',
                'guardian-edit',
                'advanced-reports',
                'advanced-service-report',
                'advanced-beneficiary-report',
                'advanced-operator-report',
                'advanced-supervisor-report',
                'advanced-social-worker-report',
                'advanced-gate-report',
                'system-settings-user-definition',
                'system-settings-user-list',
                'service-definition',
                'service-list',
                'service-delivery',
                'service-management',
                'service-archive',
                'activity-definition',
                'activity-operator-assignments',
                'child-supporter-sponsor-registration',
                'child-supporter-sponsor-edit',
                'child-supporter-sponsor-list',
                'special-features-id-card-scanner'
            );
        }

        if (! in_array($this->activeSection, $validSections, true)) {
            $this->activeSection = 'overview';
        }
    }

    private function dispatchDashboardSectionChanged(): void
    {
        $this->dispatch('dashboard-section-changed', section: $this->activeSection)->to(DashboardSidebar::class);
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

        $reminder->update(['is_done' => ! $reminder->is_done]);
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
            'totalCenterMembers' => $isOverview ? (int) Guardian::query()->sum('children_in_house') : 0,
            'totalSocialWorkers' => $isOverview ? SocialWorker::count() : 0,
            'maleCount' => $isOverview ? Person::where('gender', 'male')->count() : 0,
            'femaleCount' => $isOverview ? Person::where('gender', 'female')->count() : 0,
            'guardianCount' => $isOverview ? Guardian::count() : 0,
            'coveredRegions' => $isOverview
                ? District::whereNotNull('id')->distinct('id')->count('id')
                : 0,
            'latestPeople' => $isOverview
                ? Person::with(['guardian.socialWorker'])->latest()->take(8)->get()
                : collect(),
            'birthMonthChart' => $birthMonthChart,
            'reminders' => $reminders,
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
            'editingGuardian' => $this->editingGuardianId ? Guardian::find($this->editingGuardianId) : null,
            'editingSponsor' => $this->editingSponsorId ? SponsorProfile::find($this->editingSponsorId) : null,
            'serviceReportServiceId' => $this->serviceReportServiceId,
            'caseFilePersonId' => $this->caseFilePersonId,
        ]);
    }
}
