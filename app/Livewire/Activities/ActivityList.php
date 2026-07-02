<?php

namespace App\Livewire\Activities;

use App\Helpers\Morilog\CalendarUtils;
use App\Exports\ActivityAttendancesExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Services\ActivityLifecycleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ActivityList extends Component
{
    use WithPagination;

    private const ACTIVITIES_PER_PAGE = 12;
    private const ATTENDANCES_DISPLAY_LIMIT = 75;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public ?string $startsFrom = null;
    public ?string $startsUntil = null;
    public ?int $selectedActivityId = null;
    public string $transitionNotes = '';
    public string $attendanceSearch = '';
    public string $attendanceMethodFilter = 'all';
    public string $attendanceStatusFilter = 'all';
    public ?int $assigningOperatorActivityId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function createActivity(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->dispatch('open-dashboard-section', section: 'activity-definition');
    }

    public function editActivity(int $activityId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->dispatch('open-dashboard-section', section: 'activity-definition', id: $activityId);
    }

    public function selectActivity(int $activityId): void
    {
        $this->selectedActivityId = $activityId;
        $this->transitionNotes = '';
        $this->resetAttendanceFilters();
        $this->resetValidation();
    }

    public function openScanner(int $activityId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->dispatch('open-dashboard-section', section: 'activity-scanner', id: $activityId);
    }

    public function clearSelectedActivity(): void
    {
        $this->selectedActivityId = null;
        $this->transitionNotes = '';
        $this->resetAttendanceFilters();
        $this->resetValidation();
    }

    public function openOperatorAssignment(int $activityId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->assigningOperatorActivityId = $activityId;
    }

    public function closeOperatorAssignment(): void
    {
        $this->assigningOperatorActivityId = null;
    }

    public function transitionActivity(int $activityId, string $targetStatus): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $activity = Activity::query()->findOrFail($activityId);

        try {
            app(ActivityLifecycleService::class)->transition($activity, $targetStatus, auth()->user(), $this->transitionNotes);
            $this->selectedActivityId = $activityId;
            $this->transitionNotes = '';
            session()->flash('activity-success', 'وضعیت فعالیت با موفقیت تغییر کرد.');
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'startsFrom', 'startsUntil']);
        $this->statusFilter = 'all';
        $this->typeFilter = 'all';
        $this->resetPage();
        $this->resetValidation();
    }

    public function applyDatePreset(string $preset): void
    {
        $today = Jalalian::now();

        match ($preset) {
            'today' => [
                $this->startsFrom = $today->format('Y/m/d'),
                $this->startsUntil = $today->format('Y/m/d'),
            ],
            'week' => [
                $this->startsFrom = $today->subDays(7)->format('Y/m/d'),
                $this->startsUntil = Jalalian::now()->format('Y/m/d'),
            ],
            'month' => [
                $this->startsFrom = $today->subMonth()->format('Y/m/d'),
                $this->startsUntil = Jalalian::now()->format('Y/m/d'),
            ],
            '30days' => [
                $this->startsFrom = $today->subDays(30)->format('Y/m/d'),
                $this->startsUntil = Jalalian::now()->format('Y/m/d'),
            ],
            'all' => [
                $this->startsFrom = null,
                $this->startsUntil = null,
            ],
            default => null,
        };

        $this->resetPage();
        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStartsFrom(): void
    {
        $this->resetPage();
        $this->resetValidation('startsFrom');
    }

    public function updatedStartsUntil(): void
    {
        $this->resetPage();
        $this->resetValidation('startsUntil');
    }

    public function getSelectedActivityProperty(): ?Activity
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return Activity::query()
            ->with('creator')
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
                'attendances as late_attendances_count' => fn ($query) => $query->where('status', 'late'),
                'attendances as absent_attendances_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->find($this->selectedActivityId);
    }


    public function getFilteredAttendancesProperty(): Collection
    {
        if (! $this->selectedActivityId) {
            return collect();
        }

        $search = trim($this->attendanceSearch);
        $method = $this->attendanceMethodFilter;
        $status = $this->attendanceStatusFilter;

        return ActivityAttendance::query()
            ->with(['person', 'recorder'])
            ->where('activity_id', $this->selectedActivityId)
            ->when($method !== 'all', fn ($query) => $query->where('registration_method', $method))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->whereHas('person', function ($personQuery) use ($search): void {
                            $personQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('person_code', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('recorder', function ($recorderQuery) use ($search): void {
                            $recorderQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('checked_in_at')
            ->latest('id')
            ->limit(self::ATTENDANCES_DISPLAY_LIMIT)
            ->get();
    }


    public function exportAttendances(): mixed
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless($this->selectedActivity, 404);

        $activity = $this->selectedActivity;
        $fileName = 'activity-attendances-' . $activity->code . '.xlsx';

        return Excel::download(new ActivityAttendancesExport($activity), $fileName);
    }

    public function allowedTransitionTargets(Activity $activity): array
    {
        return app(ActivityLifecycleService::class)->allowedTargets($activity);
    }

    public function formatJalaliDateTime($dateTime): string
    {
        return $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-';
    }

    public function getMatchedSearchFields(Activity $activity): array
    {
        if (!$this->search) {
            return [];
        }

        $search = strtolower(trim($this->search));
        $matched = [];

        if (str_contains(strtolower($activity->code), $search)) {
            $matched[] = 'کد';
        }
        if (str_contains(strtolower($activity->name), $search)) {
            $matched[] = 'نام';
        }
        if (str_contains(strtolower($activity->location ?? ''), $search)) {
            $matched[] = 'مکان';
        }
        if ($activity->creator && (
            str_contains(strtolower($activity->creator->name ?? ''), $search) ||
            str_contains(strtolower($activity->creator->first_name ?? ''), $search) ||
            str_contains(strtolower($activity->creator->last_name ?? ''), $search)
        )) {
            $matched[] = 'ثبت‌کننده';
        }

        return $matched;
    }

    public function getCapacityInfo(Activity $activity): array
    {
        if (!$activity->capacity) {
            return [
                'percentage' => 0,
                'text' => 'نامحدود',
                'class' => 'text-slate-600',
                'barClass' => 'bg-slate-300',
                'isUnlimited' => true,
            ];
        }

        $percentage = round(($activity->attendances_count / max(1, $activity->capacity)) * 100);
        $text = "{$percentage}% : {$activity->attendances_count}/{$activity->capacity}";

        if ($percentage < 80) {
            $class = 'text-emerald-700';
            $barClass = 'bg-emerald-500';
        } elseif ($percentage < 100) {
            $class = 'text-amber-700';
            $barClass = 'bg-amber-500';
        } else {
            $class = 'text-rose-700';
            $barClass = 'bg-rose-500';
        }

        return [
            'percentage' => $percentage,
            'text' => $text,
            'class' => $class,
            'barClass' => $barClass,
            'isUnlimited' => false,
        ];
    }

    public function render(): mixed
    {
        $search = trim($this->search);
        $status = $this->statusFilter === 'all' ? null : $this->statusFilter;
        $type = $this->typeFilter === 'all' ? null : $this->typeFilter;
        $startsFrom = $this->parseJalaliDateFilter($this->startsFrom, 'startsFrom');
        $startsUntil = $this->parseJalaliDateFilter($this->startsUntil, 'startsUntil');
        $hasInvalidDateFilter = $this->getErrorBag()->has('startsFrom')
            || $this->getErrorBag()->has('startsUntil');

        return view('livewire.activities.activity-list', [
            'activities' => Activity::query()
                ->with('creator')
                ->withCount([
                    'attendances',
                    'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
                    'attendances as late_attendances_count' => fn ($query) => $query->where('status', 'late'),
                    'attendances as absent_attendances_count' => fn ($query) => $query->where('status', 'absent'),
                ])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%")
                            ->orWhereHas('creator', function ($creatorQuery) use ($search): void {
                                $creatorQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', "%{$search}%");
                            });
                    });
                })
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($type, fn ($query) => $query->where('activity_type', $type))
                ->when($hasInvalidDateFilter, fn ($query) => $query->whereRaw('1 = 0'))
                ->when($startsFrom, fn ($query) => $query->where('starts_at', '>=', $startsFrom->startOfDay()))
                ->when($startsUntil, fn ($query) => $query->where('starts_at', '<=', $startsUntil->endOfDay()))
                ->latest('starts_at')
                ->latest()
                ->paginate(self::ACTIVITIES_PER_PAGE),
            'selectedActivity' => $this->selectedActivity,
            'filteredAttendances' => $this->filteredAttendances,
            'attendanceDisplayLimit' => self::ATTENDANCES_DISPLAY_LIMIT,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'typeOptions' => Activity::TYPE_OPTIONS,
            'attendanceStatusOptions' => ActivityAttendance::STATUS_OPTIONS,
            'attendanceMethodOptions' => ActivityAttendance::METHOD_OPTIONS,
            'statusCounts' => $this->getStatusCounts($search, $type, $startsFrom, $startsUntil),
        ]);
    }

    private function getStatusCounts(?string $search, ?string $type, mixed $startsFrom, mixed $startsUntil): array
    {
        $baseQuery = Activity::query();

        // Apply search filter
        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search): void {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($creatorQuery) use ($search): void {
                        $creatorQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere(DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"), 'like', "%{$search}%");
                    });
            });
        }

        // Apply type filter
        if ($type) {
            $baseQuery->where('activity_type', $type);
        }

        // Apply date filters
        if ($startsFrom) {
            $baseQuery->where('starts_at', '>=', $startsFrom->startOfDay());
        }
        if ($startsUntil) {
            $baseQuery->where('starts_at', '<=', $startsUntil->endOfDay());
        }

        // Get counts by status
        $counts = [];
        foreach (array_keys(Activity::STATUS_OPTIONS) as $statusKey) {
            $counts[$statusKey] = (clone $baseQuery)->where('status', $statusKey)->count();
        }

        return $counts;
    }

    public function hasAnyFiltersApplied(): bool
    {
        return $this->search !== '' ||
               $this->statusFilter !== 'all' ||
               $this->typeFilter !== 'all' ||
               $this->startsFrom !== null ||
               $this->startsUntil !== null;
    }

    public function totalActivitiesCount(): int
    {
        return Activity::count();
    }

    private function resetAttendanceFilters(): void
    {
        $this->attendanceSearch = '';
        $this->attendanceMethodFilter = 'all';
        $this->attendanceStatusFilter = 'all';
    }

    protected function parseJalaliDateFilter(?string $date, string $field): mixed
    {
        $this->resetValidation($field);

        if (blank($date)) {
            return null;
        }

        $normalized = $this->normalizeJalaliDateInput($date);

        if ($normalized === null || ! preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $normalized)) {
            $this->addError($field, 'تاریخ فیلتر باید با قالب 1403/01/01 وارد شود.');
            return null;
        }

        $parts = explode('/', $normalized);
        [$year, $month, $day] = array_map('intval', $parts);

        if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
            $this->addError($field, 'تاریخ فیلتر معتبر نیست.');
            return null;
        }

        return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon();
    }

    protected function normalizeJalaliDateInput(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = CalendarUtils::convertNumbers((string) $value, true);
        $normalized = str_replace(["\u{200c}", "\u{200f}", "\u{00a0}"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        if ($normalized === '') {
            return null;
        }

        // Extract only the date part (remove any time information)
        $parts = preg_split('/[\s\/\-]+/', $normalized);
        if (count($parts) >= 3) {
            $year = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
            $normalized = "{$year}/{$month}/{$day}";
        }

        return $normalized;
    }
}
