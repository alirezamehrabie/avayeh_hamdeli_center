<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Services\ActivityCheckInService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ActivityScanner extends Component
{
    public int $activityId;
    public string $scanStatus = 'initializing';
    public string $scanMessage = '';
    public ?array $lastScanResult = null;
    public array $recentScans = [];
    public string $manualSearch = '';
    public ?int $selectedPersonId = null;

    public function mount(int $activityId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->activityId = $activityId;
        abort_unless((bool) $this->activity, 404);

        $this->scanStatus = $this->activity?->status === 'ongoing' ? 'ready' : 'paused';
        $this->scanMessage = $this->activity?->status === 'ongoing'
            ? 'دوربین را فعال کنید و QR مددجو را اسکن کنید.'
            : 'ثبت حضور فقط زمانی فعال است که فعالیت در وضعیت آماده برگزاری باشد.';
    }

    public function resolveScannedQr(string $payload): array
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $activity = $this->activity;
        abort_unless($activity, 404);

        $result = app(ActivityCheckInService::class)->checkInByQr($activity, $payload, auth()->user());

        $this->applyResult($result->toArray());

        return $this->scanResponse($result->ok);
    }

    public function manualCheckIn(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $activity = $this->activity;
        abort_unless($activity, 404);

        $person = $this->selectedPersonId ? Person::query()->find($this->selectedPersonId) : null;
        $result = app(ActivityCheckInService::class)->checkInPerson($activity, $person, auth()->user(), 'manual');

        $this->applyResult($result->toArray());
        $this->selectedPersonId = null;
        $this->manualSearch = '';
    }

    public function selectManualPerson(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
    }

    public function resumeScanning(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if ($this->activity?->status !== 'ongoing') {
            $this->scanStatus = 'paused';
            $this->scanMessage = 'فعالیت در وضعیت آماده برگزاری نیست.';
            return;
        }

        $this->scanStatus = 'scanning';
        $this->scanMessage = 'اسکن دوباره فعال شد. QR مددجو را مقابل دوربین نگه دارید.';
        $this->dispatch('id-card-scanner-resume');
    }

    public function backToActivities(): void
    {
        $this->dispatch('open-dashboard-section', section: 'activity-list');
    }

    public function getActivityProperty(): ?Activity
    {
        return Activity::query()
            ->with('creator')
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
            ])
            ->find($this->activityId);
    }

    public function getManualCandidatesProperty(): Collection
    {
        $search = trim($this->manualSearch);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        return Person::query()
            ->where(function ($query) use ($search): void {
                $query->where('person_code', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.activities.activity-scanner', [
            'activity' => $this->activity,
            'manualCandidates' => $this->manualCandidates,
            'selectedPerson' => $this->selectedPersonId ? Person::query()->find($this->selectedPersonId) : null,
            'recentAttendances' => ActivityAttendance::query()
                ->with(['person', 'recorder'])
                ->where('activity_id', $this->activityId)
                ->latest('checked_in_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function applyResult(array $result): void
    {
        $this->lastScanResult = $result;
        $this->scanStatus = $result['ok'] ? 'paused' : 'scan_error';
        $this->scanMessage = (string) ($result['message'] ?? '');

        array_unshift($this->recentScans, [
            'code' => $result['code'] ?? 'unknown',
            'message' => $result['message'] ?? '',
            'person' => $result['person']['name'] ?? null,
            'person_code' => $result['person']['person_code'] ?? null,
            'ok' => (bool) ($result['ok'] ?? false),
            'time' => now()->format('H:i:s'),
        ]);

        $this->recentScans = array_slice($this->recentScans, 0, 8);
    }

    private function scanResponse(bool $ok): array
    {
        return [
            'ok' => $ok,
            'status' => $this->scanStatus,
            'message' => $this->scanMessage,
            'result' => $this->lastScanResult,
        ];
    }
}
