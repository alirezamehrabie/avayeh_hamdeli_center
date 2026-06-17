<?php

namespace App\Livewire\Activities;

use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ActivityDefinition extends Component
{
    public ?int $activityId = null;

    public ?int $editingActivityId = null;

    public string $name = '';

    public string $activityType = 'group_activity';

    public string $description = '';

    public string $location = '';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public string $capacity = '';

    public string $status = 'ongoing';

    public string $statusNotes = '';

    public function mount(?int $activityId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->activityId = $activityId;
        $this->bootDefaults();

        if ($activityId) {
            $this->editActivity($activityId);
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $this->normalizeInput();

        $activity = $this->editingActivityId
            ? Activity::query()->findOrFail($this->editingActivityId)
            : new Activity;

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        if (! $this->editingActivityId) {
            $activity->code = Activity::generateNextCode();
            $activity->created_by = auth()->id();
        }

        $startsAt = blank($validated['startsAt']) ? null : $this->jalaliDateTimeToGregorian($validated['startsAt']);
        $endsAt = blank($validated['endsAt']) ? null : $this->jalaliDateTimeToGregorian($validated['endsAt']);

        $payload = [
            'name' => trim($validated['name']),
            'activity_type' => $validated['activityType'],
            'description' => blank($validated['description']) ? null : trim($validated['description']),
            'location' => blank($validated['location']) ? null : trim($validated['location']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => blank($validated['capacity']) ? null : (int) $validated['capacity'],
            'status' => $this->resolveStatus($validated['status'], $startsAt),
            'status_notes' => blank($validated['statusNotes']) ? null : trim($validated['statusNotes']),
        ];

        $activity->fill($payload);
        $activity->save();

        session()->flash('activity-success', 'فعالیت با موفقیت ذخیره شد.');

        return redirect()->to('/admin/dashboard?section=activity-list');
    }

    public function editActivity(int $activityId): void
    {
        $activity = Activity::query()->findOrFail($activityId);

        $this->editingActivityId = $activity->id;
        $this->name = $activity->name;
        $this->activityType = (string) $activity->activity_type;
        $this->description = (string) $activity->description;
        $this->location = (string) $activity->location;
        $this->startsAt = $activity->starts_at ? Jalalian::fromDateTime($activity->starts_at)->format('Y/m/d H:i') : null;
        $this->endsAt = $activity->ends_at ? Jalalian::fromDateTime($activity->ends_at)->format('Y/m/d H:i') : null;
        $this->capacity = $activity->capacity ? (string) $activity->capacity : '';
        $this->status = (string) $activity->status;
        $this->statusNotes = (string) $activity->status_notes;
    }

    public function backToList(): void
    {
        $this->dispatch('open-dashboard-section', section: 'activity-list');
    }

    public function getPreviewActivityCodeProperty(): string
    {
        return $this->editingActivityId
            ? (string) Activity::query()->whereKey($this->editingActivityId)->value('code')
            : Activity::generateNextCode();
    }

    public function render()
    {
        return view('livewire.activities.activity-definition', [
            'typeOptions' => Activity::TYPE_OPTIONS,
            'statusOptions' => Activity::STATUS_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'activityType' => ['required', Rule::in(array_keys(Activity::TYPE_OPTIONS))],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'startsAt' => ['nullable', 'string', $this->jalaliDateTimeRule('زمان شروع')],
            'endsAt' => ['nullable', 'string', $this->jalaliDateTimeRule('زمان پایان'), function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (blank($this->startsAt)) {
                    $fail('برای ثبت زمان پایان، زمان شروع نیز الزامی است.');

                    return;
                }

                if (! $this->isValidJalaliDateTime((string) $value) || ! $this->isValidJalaliDateTime((string) $this->startsAt)) {
                    return;
                }

                if (! $this->jalaliDateTimeToGregorian((string) $value)
                    ->greaterThan($this->jalaliDateTimeToGregorian((string) $this->startsAt))) {
                    $fail('زمان پایان باید بعد از زمان شروع باشد.');
                }
            }],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_keys(Activity::STATUS_OPTIONS))],
            'statusNotes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام فعالیت',
            'activityType' => 'نوع فعالیت',
            'description' => 'توضیحات',
            'location' => 'مکان',
            'startsAt' => 'زمان شروع',
            'endsAt' => 'زمان پایان',
            'capacity' => 'ظرفیت',
            'statusNotes' => 'یادداشت وضعیت',
        ];
    }

    protected function bootDefaults(): void
    {
        $this->activityType = $this->activityType ?: 'group_activity';
        $this->status = $this->status ?: 'ongoing';
    }

    protected function normalizeInput(): void
    {
        $this->name = trim($this->name);
        $this->activityType = trim($this->activityType) ?: 'group_activity';
        $this->description = trim($this->description);
        $this->location = trim($this->location);
        $this->startsAt = $this->normalizeJalaliDateTimeInput($this->startsAt);
        $this->endsAt = $this->normalizeJalaliDateTimeInput($this->endsAt);
        $this->capacity = trim($this->capacity);
        $this->status = trim($this->status) ?: 'ongoing';
        $this->statusNotes = trim($this->statusNotes);
    }

    protected function jalaliDateTimeRule(string $label): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
            if (blank($value)) {
                return;
            }

            if (! $this->isValidJalaliDateTime((string) $value)) {
                $fail($label.' شمسی معتبر نیست. قالب درست: 1403/01/01 یا 1403/01/01 14:30');
            }
        };
    }

    protected function isValidJalaliDateTime(string $value): bool
    {
        [$date, $time] = $this->splitJalaliDateTime($value);

        if ($date === null) {
            return false;
        }

        $dateParts = explode('/', $date);

        if (count($dateParts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $dateParts);

        if (! CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
            return false;
        }

        if ($time === null) {
            return true;
        }

        $timeParts = explode(':', $time);

        if (count($timeParts) !== 2) {
            return false;
        }

        [$hour, $minute] = array_map('intval', $timeParts);

        return $hour >= 0 && $hour <= 23
            && $minute >= 0 && $minute <= 59;
    }

    protected function splitJalaliDateTime(?string $value): array
    {
        $normalized = $this->normalizeJalaliDateTimeInput($value);

        if ($normalized === null) {
            return [null, null];
        }

        $parts = explode(' ', $normalized, 2);

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    protected function jalaliDateTimeToGregorian(string $value): Carbon
    {
        [$date, $time] = $this->splitJalaliDateTime($value);

        $normalized = $time === null ? (string) $date : trim($date.' '.$time);
        $format = $time === null ? 'Y/m/d' : 'Y/m/d H:i';
        $timezone = new \DateTimeZone((string) config('app.timezone'));
        $carbon = Jalalian::fromFormat($format, $normalized, $timezone)
            ->toCarbon()
            ->setTimezone($timezone);

        return Carbon::instance($carbon);
    }

    protected function normalizeJalaliDateTimeInput(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = CalendarUtils::convertNumbers((string) $value, true);
        $normalized = str_replace(["\u{200c}", "\u{200f}", "\u{00a0}"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    protected function resolveStatus(string $status, ?Carbon $startsAt): string
    {
        $status = trim($status);

        if ($status !== 'ongoing') {
            return $status;
        }

        if ($startsAt && $startsAt->isFuture()) {
            return 'scheduled';
        }

        return 'ongoing';
    }
}
