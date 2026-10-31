<?php

namespace App\Livewire\ActivityOperators;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.activity-operator')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);
    }

    public function render()
    {
        $user = auth()->user();

        $assignedActivitiesQuery = $user->assignedActivities();
        $assignedActivitiesCount = (clone $assignedActivitiesQuery)->count();
        $ongoingActivitiesCount = (clone $assignedActivitiesQuery)->where('status', 'ongoing')->count();
        $completedActivitiesCount = (clone $assignedActivitiesQuery)->where('status', 'closed')->count();

        $totalAttendancesRecorded = ActivityAttendance::whereHas('activity', function ($query) use ($user) {
            $query->whereHas('operators', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->count();

        $ongoingActivity = (clone $assignedActivitiesQuery)
            ->where('status', 'ongoing')
            ->orderBy('starts_at', 'asc')
            ->first();

        $upcomingActivities = (clone $assignedActivitiesQuery)
            ->where('status', '!=', 'closed')
            ->withCount('attendances')
            ->orderBy('starts_at', 'asc')
            ->limit(5)
            ->get();

        return view('livewire.activity-operators.dashboard', [
            'assignedActivitiesCount' => $assignedActivitiesCount,
            'ongoingActivitiesCount' => $ongoingActivitiesCount,
            'completedActivitiesCount' => $completedActivitiesCount,
            'totalAttendancesRecorded' => $totalAttendancesRecorded,
            'ongoingActivity' => $ongoingActivity,
            'upcomingActivities' => $upcomingActivities,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'typeOptions' => Activity::TYPE_OPTIONS,
        ]);
    }
}
