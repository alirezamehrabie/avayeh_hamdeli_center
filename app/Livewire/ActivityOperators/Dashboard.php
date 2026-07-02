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

        $assignedActivitiesCount = $user->assignedActivities()->count();
        $ongoingActivitiesCount = $user->assignedActivities()
            ->where('status', 'ongoing')
            ->count();
        $totalAttendancesRecorded = ActivityAttendance::whereHas('activity', function ($query) use ($user) {
            $query->whereHas('operators', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->count();

        $upcomingActivities = $user->assignedActivities()
            ->where('status', '!=', 'closed')
            ->orderBy('starts_at', 'asc')
            ->limit(5)
            ->get();

        return view('livewire.activity-operators.dashboard', [
            'assignedActivitiesCount' => $assignedActivitiesCount,
            'ongoingActivitiesCount' => $ongoingActivitiesCount,
            'totalAttendancesRecorded' => $totalAttendancesRecorded,
            'upcomingActivities' => $upcomingActivities,
        ]);
    }
}
