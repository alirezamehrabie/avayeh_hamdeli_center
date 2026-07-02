<?php

namespace App\Livewire\Admin;

use App\Models\Activity;
use App\Models\ActivityOperatorAssignment as ActivityOperatorAssignmentModel;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ActivityOperatorAssignment extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $selectedActivityId = null;

    public ?int $lockedActivityId = null;

    public string $operatorSearch = '';

    public string $assignmentNotes = '';

    public function mount(?int $activityId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        if ($activityId) {
            $this->lockedActivityId = $activityId;
            $this->selectedActivityId = $activityId;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectActivity(int $activityId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->selectedActivityId = $activityId;
        $this->operatorSearch = '';
        $this->assignmentNotes = '';
        $this->resetValidation();
    }

    public function clearSelectedActivity(): void
    {
        $this->selectedActivityId = null;
        $this->operatorSearch = '';
        $this->assignmentNotes = '';
    }

    public function assignOperator(int $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless($this->selectedActivityId, 404);

        $activity = Activity::query()->find($this->selectedActivityId);
        abort_unless($activity, 404);

        $operator = User::query()
            ->where('access_level', User::ACCESS_LEVEL_ACTIVITY_OPERATOR)
            ->find($userId);

        if (! $operator) {
            $this->addError('operatorSearch', 'اپراتور انتخاب‌شده معتبر نیست.');

            return;
        }

        $existingAssignment = ActivityOperatorAssignmentModel::withTrashed()
            ->where('activity_id', $activity->id)
            ->where('user_id', $operator->id)
            ->first();

        if ($existingAssignment && ! $existingAssignment->trashed()) {
            session()->flash('success', 'این اپراتور قبلاً به این فعالیت تخصیص داده شده است.');
            $this->operatorSearch = '';

            return;
        }

        if ($existingAssignment) {
            // Restore the previously removed assignment rather than inserting a new
            // row, since the unique(activity_id, user_id) index still blocks inserts
            // while the old soft-deleted row exists.
            $existingAssignment->restore();
            $existingAssignment->update([
                'assigned_by' => auth()->id(),
                'notes' => trim($this->assignmentNotes) ?: null,
                'assigned_at' => now(),
            ]);
        } else {
            ActivityOperatorAssignmentModel::create([
                'activity_id' => $activity->id,
                'user_id' => $operator->id,
                'assigned_by' => auth()->id(),
                'notes' => trim($this->assignmentNotes) ?: null,
                'assigned_at' => now(),
            ]);
        }

        $this->operatorSearch = '';
        $this->assignmentNotes = '';
        session()->flash('success', 'اپراتور با موفقیت به فعالیت تخصیص داده شد.');
    }

    public function removeAssignment(int $assignmentId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $assignment = ActivityOperatorAssignmentModel::query()->find($assignmentId);

        if (! $assignment || $assignment->activity_id !== $this->selectedActivityId) {
            return;
        }

        $assignment->delete();

        session()->flash('success', 'تخصیص اپراتور حذف شد.');
    }

    public function getSelectedActivityProperty(): ?Activity
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return Activity::query()
            ->withCount('attendances')
            ->find($this->selectedActivityId);
    }

    public function getCurrentOperatorAssignmentsProperty()
    {
        if (! $this->selectedActivityId) {
            return collect();
        }

        return ActivityOperatorAssignmentModel::query()
            ->with(['operator', 'assignedBy'])
            ->where('activity_id', $this->selectedActivityId)
            ->whereNull('deleted_at')
            ->latest('assigned_at')
            ->get();
    }

    public function getAssignableOperatorsProperty()
    {
        $search = trim($this->operatorSearch);
        $assignedUserIds = $this->currentOperatorAssignments->pluck('user_id')->all();

        return User::query()
            ->where('access_level', User::ACCESS_LEVEL_ACTIVITY_OPERATOR)
            ->whereNotIn('id', $assignedUserIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        $search = trim($this->search);
        $status = $this->statusFilter === 'all' ? null : $this->statusFilter;

        $activities = Activity::query()
            ->withCount(['operators', 'attendances'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('starts_at')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.activity-operator-assignment', [
            'activities' => $activities,
            'selectedActivity' => $this->selectedActivity,
            'currentOperatorAssignments' => $this->currentOperatorAssignments,
            'assignableOperators' => $this->assignableOperators,
            'statusOptions' => Activity::STATUS_OPTIONS,
        ]);
    }
}
