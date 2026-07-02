<!-- Import the activity scanner from admin panel - it's identical and reusable -->
@include('livewire.activities.activity-scanner', [
    'activity' => $activity,
    'manualCandidates' => $manualCandidates,
    'selectedPerson' => $selectedPerson,
    'recentAttendances' => $recentAttendances,
])
