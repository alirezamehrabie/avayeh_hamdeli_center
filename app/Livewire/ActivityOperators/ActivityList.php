<?php

namespace App\Livewire\ActivityOperators;

use App\Models\Activity;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.activity-operator')]
class ActivityList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $selectedActivityId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);
    }

    public function selectActivity(int $activityId): void
    {
        $activity = auth()->user()->assignedActivities()->find($activityId);
        abort_unless($activity, 404);

        $this->selectedActivityId = $activityId;
    }

    public function clearSelectedActivity(): void
    {
        $this->selectedActivityId = null;
    }

    public function openCheckIn(int $activityId): void
    {
        $activity = auth()->user()->assignedActivities()->find($activityId);
        abort_unless($activity, 404);

        $this->redirect(route('activity-operator.check-in', ['id' => $activityId]));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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
            ])
            ->find($this->selectedActivityId);
    }

    public function render()
    {
        $search = trim($this->search);
        $status = $this->statusFilter === 'all' ? null : $this->statusFilter;

        $query = auth()->user()->assignedActivities()
            ->with('creator')
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($nestedQ) use ($search) {
                    $nestedQ
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('starts_at')
            ->latest()
            ->paginate(12);

        return view('livewire.activity-operators.activity-list', [
            'activities' => $query,
            'selectedActivity' => $this->selectedActivity,
            'statusOptions' => Activity::STATUS_OPTIONS,
        ]);
    }
}
