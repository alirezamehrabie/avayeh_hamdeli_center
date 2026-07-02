<?php

namespace App\Livewire\ActivityOperators;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Services\ActivityCheckInService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.activity-operator')]
class ActivityCheckIn extends Component
{
    public int $activityId;
    public string $scanStatus = 'initializing';
    public string $scanMessage = '';
    public ?array $lastScanResult = null;
    public array $recentScans = [];
    public string $manualSearch = '';
    public ?int $selectedPersonId = null;
    public bool $activityUnavailable = false;

    public function mount(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);

        $this->activityId = $id;
        $activity = $this->activity;

        if (! $activity) {
            // Activity doesn't exist or isn't assigned to this operator. Render a
            // friendly Persian explanation instead of a hard 403/404 abort.
            $this->activityUnavailable = true;

            return;
        }

        $this->scanStatus = $activity->status === 'ongoing' ? 'ready' : 'paused';
        $this->scanMessage = $activity->status === 'ongoing'
            ? 'دوربین را فعال کنید و QR مددجو را اسکن کنید.'
            : 'ثبت حضور فقط زمانی فعال است که فعالیت در وضعیت آماده برگزاری باشد.';
    }

    public function resolveScannedQr(string $payload): array
    {
        $activity = $this->activity;
        abort_unless($activity, 404);
        abort_unless(auth()->user()->canAccessActivity($activity), 403);

        $result = app(ActivityCheckInService::class)->checkInByQr($activity, $payload, auth()->user());
        $this->applyResult($result->toArray());

        return $this->scanResponse($result->ok);
    }

    public function manualCheckIn(): void
    {
        $activity = $this->activity;
        abort_unless($activity, 404);
        abort_unless(auth()->user()->canAccessActivity($activity), 403);

        $person = $this->selectedPersonId ? Person::query()->find($this->selectedPersonId) : null;
        $result = app(ActivityCheckInService::class)->checkInPerson($activity, $person, auth()->user(), 'manual');

        $this->applyResult($result->toArray());
        $this->selectedPersonId = null;
        $this->manualSearch = '';
    }

    public function selectManualPerson(int $personId): void
    {
        $this->selectedPersonId = $personId;
    }

    public function resumeScanning(): void
    {
        $activity = $this->activity;
        abort_unless($activity, 404);

        if ($activity?->status !== 'ongoing') {
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
        $this->redirect(route('activity-operator.activity-list'));
    }

    public function getActivityProperty(): ?Activity
    {
        // Scoped through assignedActivities() so the query itself enforces that
        // the activity belongs to the current operator - find() only ever
        // returns a match if $this->activityId is actually assigned to them.
        return auth()->user()->assignedActivities()
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

        $normalizedSearch = Person::normalizeSearchText($search);
        $escapedSearch = $this->escapeLike($search);
        $escapedNormalizedSearch = $this->escapeLike($normalizedSearch);
        $digits = preg_replace('/\D+/', '', $search) ?: '';
        $escapedDigits = $this->escapeLike($digits);
        $hasNormalizedColumns = Person::hasNormalizedSearchColumns();

        return Person::query()
            ->select(['id', 'first_name', 'last_name', 'full_name', 'person_code', 'national_id'])
            ->where(function ($query) use ($escapedSearch, $escapedNormalizedSearch, $escapedDigits, $hasNormalizedColumns, $normalizedSearch): void {
                if ($escapedDigits !== '') {
                    $query->where('person_code', 'like', "{$escapedDigits}%")
                        ->orWhere('national_id', 'like', "{$escapedDigits}%");
                } else {
                    $query->where('person_code', 'like', "{$escapedSearch}%")
                        ->orWhere('national_id', 'like', "{$escapedSearch}%");
                }

                if ($hasNormalizedColumns && $escapedNormalizedSearch !== '') {
                    $query->orWhere('normalized_full_name', 'like', "{$escapedNormalizedSearch}%")
                        ->orWhere('normalized_first_name', 'like', "{$escapedNormalizedSearch}%")
                        ->orWhere('normalized_last_name', 'like', "{$escapedNormalizedSearch}%");
                } else {
                    $query->orWhere('full_name', 'like', "{$escapedSearch}%")
                        ->orWhere('first_name', 'like', "{$escapedSearch}%")
                        ->orWhere('last_name', 'like', "{$escapedSearch}%");
                }

                if (mb_strlen($normalizedSearch) >= 3) {
                    if ($hasNormalizedColumns && $escapedNormalizedSearch !== '') {
                        $query->orWhere('normalized_full_name', 'like', "%{$escapedNormalizedSearch}%");
                    } else {
                        $query->orWhere('full_name', 'like', "%{$escapedSearch}%");
                    }
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        if ($this->activityUnavailable) {
            return view('livewire.activity-operators.activity-check-in-unavailable');
        }

        return view('livewire.activity-operators.activity-check-in', [
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

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
