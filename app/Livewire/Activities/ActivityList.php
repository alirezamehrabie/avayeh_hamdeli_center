<?php

namespace App\Livewire\Activities;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Services\ActivityLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ActivityList extends Component
{
    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public ?string $startsFrom = null;
    public ?string $startsUntil = null;
    public ?int $selectedActivityId = null;
    public string $transitionNotes = '';

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
        $this->resetValidation();
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
        $this->resetValidation();
    }

    public function getSelectedActivityProperty(): ?Activity
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return Activity::query()
            ->with(['creator'])
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => fn ($query) => $query->where('status', 'present'),
                'attendances as late_attendances_count' => fn ($query) => $query->where('status', 'late'),
                'attendances as absent_attendances_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->find($this->selectedActivityId);
    }

    public function allowedTransitionTargets(Activity $activity): array
    {
        return app(ActivityLifecycleService::class)->allowedTargets($activity);
    }

    public function formatJalaliDateTime($dateTime): string
    {
        return $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-';
    }

    public function render()
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
                ->get(),
            'selectedActivity' => $this->selectedActivity,
            'statusOptions' => Activity::STATUS_OPTIONS,
            'typeOptions' => Activity::TYPE_OPTIONS,
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
