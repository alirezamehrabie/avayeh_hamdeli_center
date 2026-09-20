<?php

namespace App\Livewire\Admin;

use AllowDynamicProperties;
use App\Models\AttendanceSheetEntry;
use App\Models\DashboardReminder;
use App\Models\District;
use App\Models\GateEntryAssignment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
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
    private const FULL_ACCESS_SECTIONS = [
        'advanced-service-report',
        'advanced-gate-report',
        'advanced-gate-technical-report',
        'activity-definition',
        'activity-list',
        'activity-scanner',
        'activity-operator-assignments',
        'child-supporter-sponsor-registration',
        'child-supporter-sponsor-edit',
        'child-supporter-sponsor-list',
        'people-incomplete-cases',
        'beneficiary-case-file',
        'special-features-print-client-card',
    ];

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

    /**
     * Delivery method picked on the advanced-service-report landing screen.
     * Held here because opening a service remounts the child component via
     * its :key; the URL copy keeps the filtered list alive across remounts,
     * refreshes and browser back/forward.
     */
    #[Url(as: 'channel', history: true)]
    public ?string $serviceReportChannel = null;

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
        } elseif (! request()->has('section') && request()->routeIs('admin.service-delivery.social-worker')) {
            $this->activeSection = 'service-delivery-social-worker';
        } elseif (! request()->has('section') && request()->routeIs('admin.service-delivery.beneficiary')) {
            $this->activeSection = 'service-delivery-beneficiary';
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
        } elseif (! request()->has('section') && request()->routeIs('admin.special-features.print-client-card')) {
            $this->activeSection = 'special-features-print-client-card';
        } elseif (! request()->has('section') && request()->routeIs('admin.people.case-file', 'people.case-file')) {
            $this->activeSection = 'beneficiary-case-file';
        }
        $this->normalizeActiveSection();
        $this->syncSectionContext();
        $this->syncActivityContext();
    }

    #[On('open-dashboard-section')]
    public function selectSection(string $section, ?int $id = null, ?string $channel = null): void
    {
        $this->activeSection = $section;
        $this->normalizeActiveSection();
        $this->serviceReportChannel = $this->activeSection === 'advanced-service-report' ? $channel : null;
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

        if ($this->activeSection !== 'advanced-service-report') {
            $this->serviceReportChannel = null;
        }

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
            'advanced-gate-technical-report',
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

        abort_if(
            in_array($this->activeSection, self::FULL_ACCESS_SECTIONS, true)
            && ! $user?->can('full-access'),
            403
        );

        $validSections = ['overview', 'system-settings-user-account'];

        if ($user?->can('manage-people')) {
            $validSections[] = 'people-list';
        }

        if ($user?->can('people-register')) {
            $validSections[] = 'people-fast-create';
            $validSections[] = 'person-create';
        }

        if ($user?->can('people-edit')) {
            $validSections[] = 'person-edit';
            // «ویرایش فیلد» بر پایۀ همان مجوز ویرایش مددجو باز است، نه دسترسی کامل.
            $validSections[] = 'people-edit-field';
            $validSections[] = 'people-edit-field-need-level';
        }

        if ($user?->can('people-delete')) {
            $validSections[] = 'people-block-list';
        }

        if ($user?->can('access-admin-panel')) {
            $validSections[] = 'advanced-operator-report';
            $validSections[] = 'special-features-id-card-scanner';
        }

        if ($user?->can('full-access')) {
            array_push(
                $validSections,
                'people-incomplete-cases',
                'people-edit-field',
                'people-edit-field-need-level',
                'beneficiary-case-file',
                'social-workers-list',
                'social-workers-block-list',
                'social-worker-create',
                'social-worker-edit',
                'social-worker-attendance-monitor',
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
                'advanced-gate-technical-report',
                'system-settings-user-definition',
                'system-settings-user-list',
                'service-definition',
                'service-list',
                'service-delivery',
                'service-delivery-social-worker',
                'service-delivery-beneficiary',
                'service-management',
                'service-archive',
                'activity-definition',
                'activity-list',
                'activity-scanner',
                'activity-operator-assignments',
                'child-supporter-sponsor-registration',
                'child-supporter-sponsor-edit',
                'child-supporter-sponsor-list',
                'special-features-id-card-scanner',
                'special-features-print-client-card'
            );
        }

        if ($user?->can('manage-notifications')) {
            $validSections[] = 'notifications-center';
            $validSections[] = 'notifications-settings';
        }

        if (! in_array($this->activeSection, $validSections, true)) {
            $this->activeSection = 'overview';
        }
    }

    private function dispatchDashboardSectionChanged(): void
    {
        $this->dispatch('dashboard-section-changed', section: $this->activeSection)->to(DashboardSidebar::class);

        // رویداد حباب‌شونده برای مرورگر: پس از اتمام رندر بخش جدید، حالت بارگذاری
        // سایدبار (Dot Spinner روی گزینه انتخاب‌شده) و اسکلتون محتوا خاموش می‌شود.
        $this->dispatch('dashboard-section-rendered', section: $this->activeSection);
    }

    public function addReminder(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $validated = $this->validate([
            'newReminderTitle' => ['required', 'string', 'max:255'],
            'newReminderCategory' => ['required', 'in:'.implode(',', array_keys(DashboardReminder::$categories))],
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

    /**
     * سنجه‌های زندۀ بخش «نبض عملیات مرکز» در نمای کلی.
     * هر کارت به یکی از بخش‌های full-access لینک می‌شود، بنابراین شمارش‌ها
     * فقط برای کاربری انجام می‌شود که مقصد را باز করতে می‌تواند.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildOpsPulse(): array
    {
        if (! auth()->user()?->can('full-access')) {
            return [];
        }

        $pendingAuthorizations = GateEntryAssignment::query()
            ->where('status', GateEntryAssignment::STATUS_PENDING)
            ->count();

        $deliveriesToday = ServiceDelivery::query()
            ->whereDate('delivered_at', today())
            ->count();

        $deliveriesYesterday = ServiceDelivery::query()
            ->whereDate('delivered_at', today()->subDay())
            ->count();

        [$stockOut, $stockLow] = $this->stockAlertCounts();

        // الگوی «حاضر» همان presentQuery مانیتور حضور است: چک‌این بدون چک‌اوت، شیت زنده.
        $presentNow = AttendanceSheetEntry::query()
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->whereHas('sheet')
            ->count();

        $stockAttention = $stockOut + $stockLow;

        return [
            [
                'label' => 'ورودهای مجازِ تحویل‌نشده',
                'caption' => 'مجوزهای صادرشده در گیت ورود',
                'value' => $pendingAuthorizations,
                'unit' => 'مجوز',
                'color' => 'rose',
                'section' => 'advanced-gate-report',
                'icon' => 'M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'tone' => $pendingAuthorizations > 0 ? 'alert' : 'ok',
                'status' => $pendingAuthorizations > 0 ? 'نیازمند پیگیری' : 'بدون معوقه',
                'badges' => [],
            ],
            [
                'label' => 'تحویل‌های خدمت امروز',
                'caption' => 'رکوردهای تحویل ثبت‌شدۀ امروز',
                'value' => $deliveriesToday,
                'unit' => 'رکورد',
                'color' => 'cyan',
                'section' => 'advanced-service-report',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'tone' => 'neutral',
                'status' => 'گزارش کامل تحویل‌ها',
                'badges' => [
                    ['label' => 'دیروز', 'value' => $deliveriesYesterday, 'dot' => 'slate'],
                ],
            ],
            [
                'label' => 'هشدار موجودی خدمات',
                'caption' => 'دسته‌های خدمات در حال توزیع',
                'value' => $stockAttention,
                'unit' => 'دسته',
                'color' => 'amber',
                'section' => 'service-list',
                'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
                'tone' => $stockAttention > 0 ? 'alert' : 'ok',
                'status' => $stockAttention > 0 ? 'بررسی موجودی' : 'موجودی پایدار',
                'badges' => $stockAttention > 0 ? [
                    ['label' => 'تمام‌شده', 'value' => $stockOut, 'dot' => 'rose'],
                    ['label' => 'نزدیک اتمام', 'value' => $stockLow, 'dot' => 'amber'],
                ] : [],
            ],
            [
                'label' => 'حاضران همین حالا',
                'caption' => 'مددجویان بدون ثبت خروج',
                'value' => $presentNow,
                'unit' => 'نفر',
                'color' => 'violet',
                'section' => 'social-worker-attendance-monitor',
                'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm4.5 0c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
                'tone' => 'live',
                'status' => 'پایش زنده',
                'live' => true,
                'badges' => [],
            ],
        ];
    }

    /**
     * شمارش دسته‌های خدماتِ «در حال توزیع» که موجودی‌شان تمام شده یا
     * به زیر ۱۰٪ رسیده است. مجموع تحویل‌ها با یک کوئری گروهی (الگوی
     * معیار داشبورد مددکار) محاسبه می‌شود تا نمای کلی N+1 نزند.
     *
     * @return array{0: int, 1: int} [تعداد تمام‌شده, تعداد نزدیک اتمام]
     */
    private function stockAlertCounts(): array
    {
        $serviceIds = Service::query()
            ->where('status', 'in_distribution')
            ->pluck('id');

        $categories = $serviceIds->isEmpty()
            ? collect()
            : ServiceCategory::query()
                ->whereIn('service_id', $serviceIds)
                ->get(['id', 'quantity']);

        if ($categories->isEmpty()) {
            return [0, 0];
        }

        $delivered = ServiceDelivery::query()
            ->selectRaw('service_category_id, COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
            ->whereIn('service_category_id', $categories->pluck('id'))
            ->groupBy('service_category_id')
            ->pluck('delivered_quantity', 'service_category_id');

        $out = 0;
        $low = 0;

        foreach ($categories as $category) {
            $quantity = (float) $category->quantity;
            $remaining = $quantity - (float) ($delivered[$category->id] ?? 0);

            if ($remaining <= 0) {
                $out++;
            } elseif ($quantity > 0 && ($remaining / $quantity) <= 0.1) {
                $low++;
            }
        }

        return [$out, $low];
    }

    public function render()
    {
        $isOverview = $this->activeSection === 'overview';
        $birthMonthChart = collect();
        $totalPeople = 0;
        $birthMonthTotal = 0;
        $birthMonthUnknown = 0;

        if ($isOverview) {
            $totalPeople = Person::count();

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

            $birthMonthTotal = (int) $monthCounts->sum();
            $birthMonthUnknown = max(0, $totalPeople - $birthMonthTotal);
        }

        $reminders = $isOverview
            ? DashboardReminder::query()
                ->where('user_id', auth()->id())
                ->orderBy('is_done')
                ->latest()
                ->get()
            : collect();

        return view('livewire.admin.dashboard-home', [
            'totalPeople' => $totalPeople,
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
            'birthMonthTotal' => $birthMonthTotal,
            'birthMonthUnknown' => $birthMonthUnknown,
            'reminders' => $reminders,
            'reminderCategories' => DashboardReminder::$categories,
            'opsPulse' => $isOverview ? $this->buildOpsPulse() : [],
            'editingPerson' => $this->editingPersonId ? Person::find($this->editingPersonId) : null,
            'editingSocialWorker' => $this->editingSocialWorkerId ? SocialWorker::find($this->editingSocialWorkerId) : null,
            'editingGuardian' => $this->editingGuardianId ? Guardian::find($this->editingGuardianId) : null,
            'editingSponsor' => $this->editingSponsorId ? SponsorProfile::find($this->editingSponsorId) : null,
            'serviceReportServiceId' => $this->serviceReportServiceId,
            'caseFilePersonId' => $this->caseFilePersonId,
        ]);
    }
}
