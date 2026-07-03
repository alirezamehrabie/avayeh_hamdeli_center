<?php

namespace App\Livewire\People;

use App\Exports\BeneficiaryCaseFileExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class BeneficiaryCaseFile extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $selectedPersonId = null;

    public string $recordType = BeneficiaryCaseRecord::TYPE_NOTE;

    public string $recordTitle = '';

    public string $recordDescription = '';

    public string $recordedAt = '';

    public ?string $recordAmount = null;

    public string $recordReferenceNumber = '';

    #[Validate(['recordAttachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096'])]
    public array $recordAttachments = [];

    public function mount(?int $personId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
        $this->recordedAt = now()->toDateString();
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
        $this->resetRecordForm();
    }

    public function exportToExcel()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $person = $this->selectedPerson;

        if (! $person) {
            session()->flash('case-record-error', 'ابتدا یک مددجو را انتخاب کنید.');

            return null;
        }

        $timeline = $this->timeline;

        if ($timeline->isEmpty()) {
            session()->flash('case-record-error', 'رکوردی برای خروجی گرفتن یافت نشد.');

            return null;
        }

        $fileName = 'پرونده-مددجو-'.($person->person_code ?: $person->id).'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        return Excel::download(new BeneficiaryCaseFileExport($person, $timeline), $fileName);
    }

    public function saveCaseRecord(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless((bool) $this->selectedPerson, 404);

        $validated = $this->validate([
            'recordType' => ['required', Rule::in(array_keys(BeneficiaryCaseRecord::TYPE_OPTIONS))],
            'recordTitle' => ['required', 'string', 'max:255'],
            'recordDescription' => ['nullable', 'string', 'max:5000'],
            'recordedAt' => ['nullable', 'date'],
            'recordAmount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'recordReferenceNumber' => ['nullable', 'string', 'max:255'],
            'recordAttachments' => ['array', 'max:5'],
            'recordAttachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [], [
            'recordType' => 'نوع رکورد',
            'recordTitle' => 'عنوان',
            'recordDescription' => 'توضیحات',
            'recordedAt' => 'تاریخ',
            'recordAmount' => 'مبلغ',
            'recordReferenceNumber' => 'شماره مرجع',
            'recordAttachments' => 'پیوست‌ها',
            'recordAttachments.*' => 'پیوست',
        ]);

        DB::transaction(function () use ($validated): void {
            $record = BeneficiaryCaseRecord::query()->create([
                'person_id' => $this->selectedPerson->id,
                'created_by' => auth()->id(),
                'record_type' => $validated['recordType'],
                'title' => trim($validated['recordTitle']),
                'description' => filled($validated['recordDescription']) ? trim($validated['recordDescription']) : null,
                'recorded_at' => filled($validated['recordedAt']) ? Carbon::parse($validated['recordedAt'])->toDateString() : null,
                'amount' => filled($validated['recordAmount']) ? (int) $validated['recordAmount'] : null,
                'reference_number' => filled($validated['recordReferenceNumber']) ? trim($validated['recordReferenceNumber']) : null,
            ]);

            foreach ($this->recordAttachments as $attachment) {
                $path = $attachment->store("beneficiary-case-records/{$record->person_id}/{$record->id}", 'public');

                BeneficiaryCaseRecordAttachment::query()->create([
                    'beneficiary_case_record_id' => $record->id,
                    'uploaded_by' => auth()->id(),
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ]);
            }
        });

        $this->resetRecordForm();
        session()->flash('case-record-success', 'رکورد پرونده با موفقیت ثبت شد.');
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
                'created_at',
            ])
            ->with(['guardian:id,first_name,last_name,national_code,social_worker_id'])
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
                'guardian:id,first_name,last_name,national_code,guardian_phone_number,social_worker_id',
                'guardian.socialWorker:id,first_name,last_name,worker_code',
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

    public function getCaseRecordsProperty(): Collection
    {
        if (! $this->selectedPersonId) {
            return collect();
        }

        return BeneficiaryCaseRecord::query()
            ->with(['creator:id,name', 'attachments:id,beneficiary_case_record_id,disk,path,original_name,mime_type,size'])
            ->where('person_id', $this->selectedPersonId)
            ->latest('recorded_at')
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

        $caseRecordRows = $this->caseRecords->map(function (BeneficiaryCaseRecord $record): array {
            return [
                'type' => 'manual',
                'date' => $record->recorded_at,
                'timestamp' => optional($record->recorded_at)->timestamp ?? optional($record->created_at)->timestamp ?? 0,
                'title' => $record->title,
                'subtitle' => $record->description,
                'badge' => $record->record_type_label,
                'quantity' => $record->reference_number ?: 'ثبت دستی',
                'value' => $record->amount !== null ? number_format((int) $record->amount).' ریال' : 'ثبت نشده',
                'details' => array_filter([
                    'شماره مرجع' => $record->reference_number,
                    'ثبت‌کننده' => $record->creator?->name,
                    'پیوست‌ها' => $record->attachments->isNotEmpty() ? number_format($record->attachments->count()).' فایل' : null,
                ]),
                'attachments' => $record->attachments,
            ];
        });

        return $serviceRows
            ->toBase()
            ->merge($attendanceRows->toBase())
            ->merge($caseRecordRows->toBase())
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

    protected function resetRecordForm(): void
    {
        $this->recordType = BeneficiaryCaseRecord::TYPE_NOTE;
        $this->recordTitle = '';
        $this->recordDescription = '';
        $this->recordedAt = now()->toDateString();
        $this->recordAmount = null;
        $this->recordReferenceNumber = '';
        $this->recordAttachments = [];
        $this->resetValidation([
            'recordType',
            'recordTitle',
            'recordDescription',
            'recordedAt',
            'recordAmount',
            'recordReferenceNumber',
            'recordAttachments',
            'recordAttachments.*',
        ]);
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.beneficiary-case-file', [
            'recordTypeOptions' => BeneficiaryCaseRecord::TYPE_OPTIONS,
        ]);
    }
}
