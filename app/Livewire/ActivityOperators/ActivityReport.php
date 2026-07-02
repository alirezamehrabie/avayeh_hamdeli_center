<?php

namespace App\Livewire\ActivityOperators;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.activity-operator')]
class ActivityReport extends Component
{
    use WithPagination;

    public ?int $selectedActivityId = null;
    public string $attendanceSearch = '';
    public string $attendanceStatusFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);
    }

    public function selectActivity(int $activityId): void
    {
        $activity = auth()->user()->assignedActivities()->find($activityId);
        abort_unless($activity, 404);

        $this->selectedActivityId = $activityId;
        $this->resetPage();
    }

    public function clearSelectedActivity(): void
    {
        $this->selectedActivityId = null;
        $this->attendanceSearch = '';
        $this->attendanceStatusFilter = 'all';
        $this->resetPage();
    }

    public function updatedAttendanceSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAttendanceStatusFilter(): void
    {
        $this->resetPage();
    }

    public function getSelectedActivityProperty(): ?Activity
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return auth()->user()->assignedActivities()
            ->with('creator')
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
                'attendances as late_attendances_count' => fn ($query) => $query->where('status', 'late'),
                'attendances as absent_attendances_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->find($this->selectedActivityId);
    }

    public function getFilteredAttendancesProperty()
    {
        // Route through selectedActivity (itself scoped to assignedActivities())
        // rather than the raw id, so a tampered selectedActivityId can never pull
        // attendance for an activity that isn't assigned to this operator.
        $activity = $this->selectedActivity;

        if (! $activity) {
            return new LengthAwarePaginator(
                [],
                0,
                15,
                1,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        $search = trim($this->attendanceSearch);
        $status = $this->attendanceStatusFilter;

        $query = ActivityAttendance::query()
            ->with(['person', 'recorder'])
            ->where('activity_id', $activity->id)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($nestedQ) use ($search) {
                    $nestedQ
                        ->whereHas('person', function ($personQ) use ($search) {
                            $personQ
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('person_code', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('checked_in_at')
            ->latest('id');

        return $query->paginate(15);
    }

    public function render()
    {
        $activities = auth()->user()->assignedActivities()
            ->with('creator')
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
            ])
            ->latest('starts_at')
            ->latest()
            ->get();

        return view('livewire.activity-operators.activity-report', [
            'activities' => $activities,
            'selectedActivity' => $this->selectedActivity,
            'filteredAttendances' => $this->filteredAttendances,
            'attendanceStatusOptions' => ActivityAttendance::STATUS_OPTIONS,
        ]);
    }
}
