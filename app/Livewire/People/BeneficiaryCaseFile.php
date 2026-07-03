<?php

namespace App\Livewire\People;

use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class BeneficiaryCaseFile extends Component
{
    public string $search = '';

    public ?int $selectedPersonId = null;

    public function mount(?int $personId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
    }

    public function updatedSearch(): void
    {
        $this->search = Person::normalizeSearchText($this->search);
    }

    public function selectPerson(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
    }

    public function clearSelection(): void
    {
        $this->selectedPersonId = null;
    }

    public function getSearchResultsProperty(): Collection
    {
        $search = Person::normalizeSearchText($this->search);

        if (mb_strlen($search) < 2 && ! ctype_digit($search)) {
            return collect();
        }

        $hasNormalizedSearchColumns = Person::hasNormalizedSearchColumns();
        $nameColumn = $hasNormalizedSearchColumns ? 'normalized_full_name' : 'full_name';
        $prefixSearch = "{$search}%";

        return Person::query()
            ->select([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'full_name',
                'national_id',
                'birth_day',
                'birth_month',
                'birth_year',
                'guardian_id',
                'social_worker_id',
                'created_at',
            ])
            ->with(['guardian:id,first_name,last_name,national_code', 'socialWorker:id,first_name,last_name,worker_code'])
            ->where(function (Builder $query) use ($search, $prefixSearch, $nameColumn): void {
                if (ctype_digit($search)) {
                    $query->where('person_code', 'like', $prefixSearch)
                        ->orWhere('national_id', strlen($search) === 10 ? '=' : 'like', strlen($search) === 10 ? $search : $prefixSearch);

                    return;
                }

                $query->where($nameColumn, 'like', $prefixSearch);
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if (! $this->selectedPersonId) {
            return null;
        }

        return Person::query()
            ->with([
                'guardian:id,first_name,last_name,national_code,guardian_phone_number',
                'socialWorker:id,first_name,last_name,worker_code',
            ])
            ->withCount(['serviceDeliveries', 'activityAttendances'])
            ->find($this->selectedPersonId);
    }

    public function getServiceDeliveriesProperty(): Collection
    {
        $person = $this->selectedPerson;

        if (! $person) {
            return collect();
        }

        return ServiceDelivery::query()
            ->with([
                'service:id,code,name,service_name_id,service_type,activity_id',
                'service.serviceName:id,name',
                'service.activity:id,code,name,activity_type,starts_at',
                'serviceCategory:id,service_id,name,unit,value',
                'socialWorker:id,first_name,last_name,worker_code',
                'creator:id,name',
                'gateEntryAssignment:id,status,assigned_at,delivered_at,delivered_by',
                'activityAttendance:id,activity_id,status,checked_in_at,registration_method',
                'activityAttendance.activity:id,code,name,activity_type,starts_at',
            ])
            ->where(function (Builder $query) use ($person): void {
                $query->where('person_id', $person->id);

                if ($person->guardian_id) {
                    $query->orWhere('guardian_id', $person->guardian_id);
                }
            })
            ->latest('delivered_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function getActivityAttendancesProperty(): Collection
    {
        if (! $this->selectedPersonId) {
            return collect();
        }

        return ActivityAttendance::query()
            ->with(['activity:id,code,name,activity_type,starts_at,ends_at,location', 'recorder:id,name'])
            ->where('person_id', $this->selectedPersonId)
            ->latest('checked_in_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function getTimelineProperty(): Collection
    {
        $serviceRows = $this->serviceDeliveries->map(function (ServiceDelivery $delivery): array {
            $service = $delivery->service;
            $activity = $delivery->activityAttendance?->activity ?? $service?->activity;

            return [
                'type' => 'service',
                'date' => $delivery->delivered_at,
                'timestamp' => optional($delivery->delivered_at)->timestamp ?? optional($delivery->created_at)->timestamp ?? 0,
                'title' => $service?->name ?: $service?->serviceName?->name ?: 'خدمت ثبت‌شده',
                'subtitle' => $delivery->serviceCategory?->name,
                'badge' => $this->deliveryChannelLabel($delivery->delivery_channel),
                'quantity' => $this->formatQuantity($delivery),
                'value' => $delivery->delivered_total_value ? number_format((int) $delivery->delivered_total_value).' ریال' : 'ثبت نشده',
                'details' => array_filter([
                    'دریافت‌کننده' => $delivery->recipient_name,
                    'کد خدمت' => $service?->code,
                    'فعالیت مرتبط' => $activity?->name,
                    'مددکار/تحویل‌دهنده' => $this->socialWorkerName($delivery),
                    'ثبت‌کننده' => $delivery->creator?->name,
                    'یادداشت' => $delivery->notes,
                ]),
            ];
        });

        $attendanceRows = $this->activityAttendances->map(function (ActivityAttendance $attendance): array {
            $activity = $attendance->activity;

            return [
                'type' => 'activity',
                'date' => $attendance->checked_in_at,
                'timestamp' => optional($attendance->checked_in_at)->timestamp ?? optional($attendance->created_at)->timestamp ?? 0,
                'title' => $activity?->name ?: 'فعالیت ثبت‌شده',
                'subtitle' => $activity ? (Activity::TYPE_OPTIONS[$activity->activity_type] ?? $activity->activity_type) : null,
                'badge' => ActivityAttendance::STATUS_OPTIONS[$attendance->status] ?? $attendance->status,
                'quantity' => ActivityAttendance::METHOD_OPTIONS[$attendance->registration_method] ?? $attendance->registration_method,
                'value' => 'وابسته به خدمات فعالیت',
                'details' => array_filter([
                    'کد فعالیت' => $activity?->code,
                    'مکان' => $activity?->location,
                    'زمان ورود' => $this->formatDateTime($attendance->checked_in_at),
                    'زمان خروج' => $this->formatDateTime($attendance->checked_out_at),
                    'ثبت‌کننده' => $attendance->recorder?->name,
                    'یادداشت' => $attendance->notes,
                ]),
            ];
        });

        return $serviceRows
            ->merge($attendanceRows)
            ->sortByDesc('timestamp')
            ->values();
    }

    public function deliveryChannelLabel(?string $channel): string
    {
        return Service::DELIVERY_CHANNEL_OPTIONS[$channel] ?? 'خدمت';
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return 'ثبت نشده';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }

    public function formatDateTime($date): string
    {
        if (! $date) {
            return 'ثبت نشده';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d H:i');
    }

    protected function formatQuantity(ServiceDelivery $delivery): string
    {
        $quantity = rtrim(rtrim(number_format((float) $delivery->delivered_quantity, 2, '.', ''), '0'), '.');
        $unit = $delivery->serviceCategory?->unit_label;

        return trim($quantity.' '.($unit ?: 'واحد'));
    }

    protected function socialWorkerName(ServiceDelivery $delivery): ?string
    {
        if (! $delivery->socialWorker) {
            return null;
        }

        return trim($delivery->socialWorker->first_name.' '.$delivery->socialWorker->last_name) ?: null;
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.beneficiary-case-file');
    }
}
