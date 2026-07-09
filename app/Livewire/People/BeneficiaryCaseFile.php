<?php

namespace App\Livewire\People;

use App\Helpers\Morilog\CalendarUtils;
use App\Exports\BeneficiaryCaseFileExport;
use App\Helpers\Morilog\Jalalian;
use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use App\Models\QrIdentity;
use App\Models\Person;
use App\Models\ServiceDelivery;
use App\Queries\People\PeopleIndexSearchQuery;
use App\Services\People\BeneficiaryCaseFileTimeline;
use App\Services\QrIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

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

    protected ?Collection $searchResultsCache = null;

    protected ?Person $selectedPersonCache = null;

    protected bool $selectedPersonCacheLoaded = false;

    protected ?Collection $serviceDeliveriesCache = null;

    protected ?Collection $activityAttendancesCache = null;

    protected ?Collection $caseRecordsCache = null;

    protected ?Collection $timelineCache = null;

    protected ?array $caseFileTotalsCache = null;

    public function mount(?int $personId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
        $this->recordedAt = Jalalian::now()->format('Y/m/d');
    }

    public function updatedSearch(): void
    {
        $this->search = Person::normalizeSearchText($this->search);
        $this->searchResultsCache = null;
    }

    public function selectPerson(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
        $this->resetLoadedCaseFileData();
    }

    public function clearSelection(): void
    {
        $this->selectedPersonId = null;
        $this->resetLoadedCaseFileData();
        $this->resetRecordForm();
    }

    public function resolveScannedBeneficiaryQr(string $payload): array
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $token = $this->extractQrToken(trim((string) $payload));

        if (! $token) {
            return $this->beneficiaryQrScanResponse(false, 'QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'admin-beneficiary-case-file');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity || $identity->subject_type !== QrIdentity::SUBJECT_PERSON) {
            return $this->beneficiaryQrScanResponse(
                false,
                $identity ? 'این کد QR برای این بخش قابل استفاده نیست.' : 'QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.'
            );
        }

        $person = Person::query()->find((int) $identity->subject_id);

        if (! $person) {
            return $this->beneficiaryQrScanResponse(false, 'اطلاعات مددجو برای این QR پیدا نشد.');
        }

        $this->selectPerson((int) $person->id);
        $this->search = '';
        $this->searchResultsCache = collect();

        return $this->beneficiaryQrScanResponse(
            true,
            'مددجو شناسایی شد.',
            (string) ($person->full_name ?: trim($person->first_name.' '.$person->last_name))
        );
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
            'recordedAt' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ شمسی معتبر نیست.');
                    }
                },
            ],
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

        $storedAttachmentPaths = [];

        try {
            DB::transaction(function () use ($validated, &$storedAttachmentPaths): void {
                $record = BeneficiaryCaseRecord::query()->create([
                    'person_id' => $this->selectedPerson->id,
                    'created_by' => auth()->id(),
                    'record_type' => $validated['recordType'],
                    'title' => trim($validated['recordTitle']),
                    'description' => filled($validated['recordDescription']) ? trim($validated['recordDescription']) : null,
                    'recorded_at' => filled($validated['recordedAt']) ? $this->jalaliToGregorian($validated['recordedAt']) : null,
                    'amount' => filled($validated['recordAmount']) ? (int) $validated['recordAmount'] : null,
                    'reference_number' => filled($validated['recordReferenceNumber']) ? trim($validated['recordReferenceNumber']) : null,
                ]);

                foreach ($this->recordAttachments as $attachment) {
                    $path = $attachment->store("beneficiary-case-records/{$record->person_id}/{$record->id}", 'public');
                    $storedAttachmentPaths[] = $path;

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
        } catch (Throwable $exception) {
            if ($storedAttachmentPaths !== []) {
                Storage::disk('public')->delete($storedAttachmentPaths);
            }

            throw $exception;
        }

        $this->resetLoadedCaseFileData();
        $this->resetRecordForm();
        session()->flash('case-record-success', 'رکورد پرونده با موفقیت ثبت شد.');
    }

    public function getSearchResultsProperty(): Collection
    {
        if ($this->searchResultsCache !== null) {
            return $this->searchResultsCache;
        }

        $search = Person::normalizeSearchText($this->search);

        if (mb_strlen($search) < 2 && ! ctype_digit($search)) {
            return $this->searchResultsCache = collect();
        }

        $query = Person::query()
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
            ->orderByDesc('created_at');

        app(PeopleIndexSearchQuery::class)->applyTo($query, $search);

        return $this->searchResultsCache = $query
            ->limit(8)
            ->get();
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if ($this->selectedPersonCacheLoaded) {
            return $this->selectedPersonCache;
        }

        $this->selectedPersonCacheLoaded = true;

        if (! $this->selectedPersonId) {
            return $this->selectedPersonCache = null;
        }

        return $this->selectedPersonCache = Person::query()
            ->with([
                'guardian:id,first_name,last_name,national_code,guardian_phone_number,social_worker_id',
                'guardian.socialWorker:id,first_name,last_name,worker_code',
            ])
            ->find($this->selectedPersonId);
    }

    public function getServiceDeliveriesProperty(): Collection
    {
        if ($this->serviceDeliveriesCache !== null) {
            return $this->serviceDeliveriesCache;
        }

        $person = $this->selectedPerson;

        if (! $person) {
            return $this->serviceDeliveriesCache = collect();
        }

        return $this->serviceDeliveriesCache = ServiceDelivery::query()
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

    public function getDirectServiceDeliveriesProperty(): Collection
    {
        if (! $this->selectedPersonId) {
            return collect();
        }

        return $this->serviceDeliveries
            ->filter(fn (ServiceDelivery $delivery): bool => (int) $delivery->person_id === (int) $this->selectedPersonId)
            ->values();
    }

    public function getFamilyServiceDeliveriesProperty(): Collection
    {
        return $this->serviceDeliveries
            ->filter(fn (ServiceDelivery $delivery): bool => $this->isFamilyServiceDelivery($delivery))
            ->values();
    }

    public function getCaseFileTotalsProperty(): array
    {
        if ($this->caseFileTotalsCache !== null) {
            return $this->caseFileTotalsCache;
        }

        $person = $this->selectedPerson;

        if (! $person) {
            return $this->caseFileTotalsCache = [
                'direct_services_count' => 0,
                'direct_services_value' => 0,
                'family_services_count' => 0,
                'family_services_value' => 0,
                'activity_attendances_count' => 0,
                'manual_records_count' => 0,
                'manual_records_amount' => 0,
            ];
        }

        $directServices = ServiceDelivery::query()
            ->where('person_id', $person->id);

        $familyServices = ServiceDelivery::query()
            ->whereRaw('1 = 0');

        if ($person->guardian_id) {
            $familyServices = ServiceDelivery::query()
                ->where('guardian_id', $person->guardian_id)
                ->where(function (Builder $query) use ($person): void {
                    $query->whereNull('person_id')
                        ->orWhere('person_id', '!=', $person->id);
                });
        }

        return $this->caseFileTotalsCache = [
            'direct_services_count' => (clone $directServices)->count(),
            'direct_services_value' => (int) (clone $directServices)->sum('delivered_total_value'),
            'family_services_count' => (clone $familyServices)->count(),
            'family_services_value' => (int) (clone $familyServices)->sum('delivered_total_value'),
            'activity_attendances_count' => ActivityAttendance::query()
                ->where('person_id', $person->id)
                ->count(),
            'manual_records_count' => BeneficiaryCaseRecord::query()
                ->where('person_id', $person->id)
                ->count(),
            'manual_records_amount' => (int) BeneficiaryCaseRecord::query()
                ->where('person_id', $person->id)
                ->sum('amount'),
        ];
    }

    public function getActivityAttendancesProperty(): Collection
    {
        if ($this->activityAttendancesCache !== null) {
            return $this->activityAttendancesCache;
        }

        if (! $this->selectedPersonId) {
            return $this->activityAttendancesCache = collect();
        }

        return $this->activityAttendancesCache = ActivityAttendance::query()
            ->with([
                'activity:id,code,name,activity_type,starts_at,ends_at,location',
                'recorder:id,name',
                'serviceDeliveries.service:id,code,name,service_name_id,service_type,activity_id',
                'serviceDeliveries.service.serviceName:id,name',
                'serviceDeliveries.serviceCategory:id,service_id,name,unit,value',
                'serviceDeliveries.socialWorker:id,first_name,last_name,worker_code',
                'serviceDeliveries.creator:id,name',
            ])
            ->where('person_id', $this->selectedPersonId)
            ->latest('checked_in_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function getCaseRecordsProperty(): Collection
    {
        if ($this->caseRecordsCache !== null) {
            return $this->caseRecordsCache;
        }

        if (! $this->selectedPersonId) {
            return $this->caseRecordsCache = collect();
        }

        return $this->caseRecordsCache = BeneficiaryCaseRecord::query()
            ->with(['creator:id,name', 'attachments:id,beneficiary_case_record_id,disk,path,original_name,mime_type,size'])
            ->where('person_id', $this->selectedPersonId)
            ->latest('recorded_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function getTimelineProperty(): Collection
    {
        if ($this->timelineCache !== null) {
            return $this->timelineCache;
        }

        return $this->timelineCache = app(BeneficiaryCaseFileTimeline::class)->build(
            $this->selectedPerson,
            $this->serviceDeliveries,
            $this->activityAttendances,
            $this->caseRecords,
        );
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

    protected function isFamilyServiceDelivery(ServiceDelivery $delivery): bool
    {
        if (! $this->selectedPersonId) {
            return false;
        }

        $selectedPersonId = (int) $this->selectedPersonId;

        if ((int) $delivery->person_id === $selectedPersonId) {
            return false;
        }

        $selectedGuardianId = (int) ($this->selectedPerson?->guardian_id ?? 0);

        return $selectedGuardianId > 0 && (int) $delivery->guardian_id === $selectedGuardianId;
    }

    protected function resetRecordForm(): void
    {
        $this->recordType = BeneficiaryCaseRecord::TYPE_NOTE;
        $this->recordTitle = '';
        $this->recordDescription = '';
        $this->recordedAt = Jalalian::now()->format('Y/m/d');
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

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', $this->normalizeJalaliDate($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
    }

    protected function normalizeJalaliDate(?string $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        return strtr((string) str($normalized)->before(' ')->trim(), [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    protected function resetLoadedCaseFileData(): void
    {
        $this->selectedPersonCache = null;
        $this->selectedPersonCacheLoaded = false;
        $this->serviceDeliveriesCache = null;
        $this->activityAttendancesCache = null;
        $this->caseRecordsCache = null;
        $this->timelineCache = null;
        $this->caseFileTotalsCache = null;
    }

    protected function beneficiaryQrScanResponse(bool $ok, string $message, string $name = ''): array
    {
        return [
            'ok' => $ok,
            'status' => $ok ? 'paused' : 'scan_error',
            'message' => $message,
            'result' => [
                'ok' => $ok,
                'code' => $ok ? 'resolved' : 'invalid_qr',
                'message' => $message,
                'name' => $name,
            ],
        ];
    }

    protected function resolvePublicCode(string $token): ?QrIdentity
    {
        $identity = QrIdentity::query()
            ->where('public_code', strtoupper(trim($token)))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();

        if (! $identity) {
            return null;
        }

        $subject = $identity->subject;

        if (! $subject || method_exists($subject, 'trashed') && $subject->trashed()) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity->setRelation('subject', $subject);
    }

    protected function extractQrToken(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\\/qr\\/r\\/([^\\/\\?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.beneficiary-case-file', [
            'recordTypeOptions' => BeneficiaryCaseRecord::TYPE_OPTIONS,
        ]);
    }
}
