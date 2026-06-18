<?php

namespace App\Livewire\Activities;

use App\Helpers\Morilog\CalendarUtils;
use App\Exports\ActivityAttendancesExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
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

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function createActivity(): void
    {
        $this->dispatch('open-dashboard-section', section: 'activity-definition');
    }

    public function editActivity(int $activityId): void
    {
        $this->dispatch('open-dashboard-section', section: 'activity-definition', id: $activityId);
    }

    public function selectActivity(int $activityId): void
    {
        $this->selectedActivityId = $activityId;
        $this->transitionNotes = '';
        $this->attendanceSearch = '';
        $this->attendanceMethodFilter = 'all';
        $this->attendanceStatusFilter = 'all';
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
        $this->resetValidation();
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
    }

    public function updatedStartsUntil(): void
    {
        $this->resetPage();
    }

    public function getSelectedActivityProperty(): ?Activity
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return Activity::query()
            ->with([
                'creator',
                'attendances.person',
                'attendances.recorder',
            ])
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
        $activity = $this->selectedActivity;

        if (! $activity) {
            return collect();
        }

        $search = trim($this->attendanceSearch);
        $method = $this->attendanceMethodFilter;
        $status = $this->attendanceStatusFilter;

        return $activity->attendances
            ->when($method !== 'all', fn ($items) => $items->where('registration_method', $method))
            ->when($status !== 'all', fn ($items) => $items->where('status', $status))
            ->filter(function ($attendance) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $person = $attendance->person;
                $haystack = implode(' ', [
                    $person?->full_name,
                    $person?->first_name,
                    $person?->last_name,
                    $person?->person_code,
                    $person?->national_id,
                    $attendance->recorder?->full_name,
                    $attendance->recorder?->name,
                ]);

                return mb_stripos($haystack, $search) !== false;
            })
            ->sortByDesc('checked_in_at')
            ->values();
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

    public function render(): mixed
    {
        $search = trim($this->search);
        $status = $this->statusFilter === 'all' ? null : $this->statusFilter;
        $type = $this->typeFilter === 'all' ? null : $this->typeFilter;
        $startsFrom = $this->validDateOrNull($this->startsFrom);
        $startsUntil = $this->validDateOrNull($this->startsUntil);

        return view('livewire.activities.activity-list', [
            'activities' => Activity::query()
                ->with('creator')
                ->withCount([
                    'attendances',
                    'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
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
                ->when($startsFrom, fn ($query) => $query->where('starts_at', '>=', $startsFrom->startOfDay()))
                ->when($startsUntil, fn ($query) => $query->where('starts_at', '<=', $startsUntil->endOfDay()))
                ->latest('starts_at')
                ->latest()
                ->paginate(self::ACTIVITIES_PER_PAGE),
            'selectedActivity' => $this->selectedActivity,
            'filteredAttendances' => $this->filteredAttendances,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'typeOptions' => Activity::TYPE_OPTIONS,
            'attendanceStatusOptions' => \App\Models\ActivityAttendance::STATUS_OPTIONS,
            'attendanceMethodOptions' => \App\Models\ActivityAttendance::METHOD_OPTIONS,
        ]);
    }

    protected function validDateOrNull(?string $date): ?\Carbon\Carbon
    {
        if (blank($date)) {
            return null;
        }

        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
            return null;
        }

        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon();
    }
}
