<?php

namespace App\Livewire\ActivityOperators;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.activity-operator')]
class Dashboard extends Component
{
    public string $filter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'ongoing', 'today'], true)) {
            $this->filter = $filter;
        }
    }

    public function render()
    {
        $user = auth()->user();

        $assignedActivitiesQuery = $user->assignedActivities();
        $assignedActivitiesCount = (clone $assignedActivitiesQuery)->count();
        $ongoingActivitiesCount = (clone $assignedActivitiesQuery)->where('status', 'ongoing')->count();
        $completedActivitiesCount = (clone $assignedActivitiesQuery)->where('status', 'closed')->count();
        $todayActivitiesCount = (clone $assignedActivitiesQuery)
            ->whereDate('starts_at', today())
            ->where('status', '!=', 'closed')
            ->count();
        $activeActivitiesCount = (clone $assignedActivitiesQuery)
            ->where('status', '!=', 'closed')
            ->count();

        $totalAttendancesRecorded = ActivityAttendance::whereHas('activity', function ($query) use ($user) {
            $query->whereHas('operators', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->count();

        $ongoingActivity = (clone $assignedActivitiesQuery)
            ->where('status', 'ongoing')
            ->withCount('attendances')
            ->orderBy('starts_at', 'asc')
            ->first();

        $upcomingQuery = (clone $assignedActivitiesQuery)->withCount('attendances');

        if ($this->filter === 'ongoing') {
            $upcomingQuery->where('status', 'ongoing');
        } elseif ($this->filter === 'today') {
            $upcomingQuery->whereDate('starts_at', today());
        } else {
            $upcomingQuery->where('status', '!=', 'closed');
        }

        $upcomingActivities = $upcomingQuery
            ->orderBy('starts_at', 'asc')
            ->limit(6)
            ->get();

        return view('livewire.activity-operators.dashboard', [
            'assignedActivitiesCount' => $assignedActivitiesCount,
            'ongoingActivitiesCount' => $ongoingActivitiesCount,
            'completedActivitiesCount' => $completedActivitiesCount,
            'todayActivitiesCount' => $todayActivitiesCount,
            'activeActivitiesCount' => $activeActivitiesCount,
            'totalAttendancesRecorded' => $totalAttendancesRecorded,
            'ongoingActivity' => $ongoingActivity,
            'upcomingActivities' => $upcomingActivities,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'typeOptions' => Activity::TYPE_OPTIONS,
        ]);
    }
}
