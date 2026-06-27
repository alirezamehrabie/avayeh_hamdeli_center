<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
use App\Models\GateEntryFieldValue;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceEntryField;
use App\Models\SocialWorker;
use App\Services\QrIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class ExitGate extends Component
{
    public const ABILITY = 'access-distribution-outbound-gate';

    #[Url(as: 'service', except: null)]
    public ?int $selectedServiceId = null;

    #[Url(as: 'q', except: '')]
    public string $serviceSearch = '';

    public string $scanStatus = 'ready';

    public string $scanMessage = '';

    public ?array $lastScanResult = null;

    public ?string $scannedSubjectType = null;

    public ?int $scannedPersonId = null;

    public ?int $scannedGuardianId = null;

    public string $manualSearch = '';

    public bool $showManualSearch = false;

    public function mount(): void
    {
        $this->authorizeGate();

        // A service id can arrive from the URL on reload — drop it if it is no longer gate-eligible.
        if ($this->selectedServiceId !== null && ! $this->selectedService) {
            $this->selectedServiceId = null;
        }

        if ($this->selectedServiceId !== null) {
            $this->serviceSearch = '';
            $this->scanMessage = 'دوربین را فعال کنید و QR مددجو یا سرپرست خانوار را اسکن کنید.';
        } else {
            $this->scanMessage = 'ابتدا خدمت گیت خروج را انتخاب کنید.';
        }
    }

    public function selectService(int $serviceId): void
    {
        $this->authorizeGate();

        if ($this->selectedServiceId !== null) {
            return;
        }

        $service = Service::query()
            ->supportsGateDelivery()
            ->whereKey($serviceId)
            ->first();

        if (! $service) {
            return;
        }

        $this->selectedServiceId = (int) $service->id;
        $this->serviceSearch = '';
        $this->resetScanState();
        $this->scanStatus = 'ready';
        $this->scanMessage = 'دوربین را فعال کنید و QR مددجو یا سرپرست خانوار را اسکن کنید.';
    }

    public function changeService(): void
    {
        $this->authorizeGate();

        $this->selectedServiceId = null;
        $this->resetScanState();
        $this->scanStatus = 'ready';
        $this->scanMessage = 'ابتدا خدمت گیت خروج را انتخاب کنید.';
    }

    public function clearServiceSearch(): void
    {
        $this->serviceSearch = '';
    }

    public function resolveScannedQr(string $payload): array
    {
        $this->authorizeGate();

        if (! $this->selectedService) {
            return $this->scanError('ابتدا خدمت گیت خروج را انتخاب کنید.');
        }

        $token = $this->extractQrToken($payload);

        if (! $token) {
            return $this->scanError('QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'distribution-exit-gate');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity) {
            // Distinguish a revoked/replaced card from an unknown one so the operator knows what to do next.
            return $this->scanError($this->describeUnresolvedQr($token), 'invalid');
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            $guardian = $this->findGuardian((int) $identity->subject_id);

            if (! $guardian) {
                return $this->scanError('اطلاعات خانوار برای این QR پیدا نشد.', 'not_found');
            }

            $isDuplicate = $this->isCurrentSubject(QrIdentity::SUBJECT_GUARDIAN, (int) $guardian->id);
            $this->applyGuardianScan($guardian, $isDuplicate);

            return $this->scanResponse(true);
        }

        $person = $this->findPerson((int) $identity->subject_id);

        if (! $person) {
            return $this->scanError('اطلاعات مددجو برای این QR پیدا نشد.', 'not_found');
        }

        $isDuplicate = $this->isCurrentSubject(QrIdentity::SUBJECT_PERSON, (int) $person->id);
        $this->applyPersonScan($person, $isDuplicate);

        return $this->scanResponse(true);
    }

    public function toggleManualSearch(): void
    {
        $this->authorizeGate();

        $this->showManualSearch = ! $this->showManualSearch;
        $this->manualSearch = '';
    }

    public function selectManualSubject(string $subjectType, int $subjectId): void
    {
        $this->authorizeGate();

        if (! $this->selectedService) {
            return;
        }

        if ($subjectType === QrIdentity::SUBJECT_GUARDIAN) {
            $guardian = $this->findGuardian($subjectId);

            if ($guardian) {
                $this->applyGuardianScan($guardian, $this->isCurrentSubject($subjectType, $subjectId), 'manual');
            }
        } else {
            $person = $this->findPerson($subjectId);

            if ($person) {
                $this->applyPersonScan($person, $this->isCurrentSubject($subjectType, $subjectId), 'manual');
            }
        }

        $this->manualSearch = '';
        $this->showManualSearch = false;
    }

    /**
     * Commit the official delivery ledger and lock the session: each item marked delivered at the
     * Delivery Gate becomes a permanent ServiceDelivery record and its assignment is locked.
     */
    public function finalizeExit(): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        $service = $this->selectedService;
        $operatorId = auth()->id();

        $created = DB::transaction(function () use ($service, $operatorId): int {
            // lockForUpdate + the status guard make this idempotent against a double-click or two tabs.
            $assignments = $this->subjectAssignmentQuery()
                ->where('status', GateEntryAssignment::STATUS_DELIVERED)
                ->with('serviceCategory')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                // Keyed on the assignment id so the ledger stays idempotent: if a row already exists for
                // this assignment (active or soft-deleted) we never write a second one. The unique index on
                // gate_entry_assignment_id is the database-level backstop behind this check.
                $alreadyLedgered = ServiceDelivery::withTrashed()
                    ->where('gate_entry_assignment_id', $assignment->id)
                    ->exists();

                if (! $alreadyLedgered) {
                    $category = $assignment->serviceCategory;
                    $unitValue = (int) ($category?->value ?? $service->deliveryUnitValue());
                    // The gate model is binary (one row per recipient per category) — one unit each.
                    $quantity = 1;

                    ServiceDelivery::query()->create([
                        'service_id' => $service->id,
                        'service_category_id' => $assignment->service_category_id,
                        'gate_entry_assignment_id' => $assignment->id,
                        'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
                        'social_worker_id' => null,
                        'person_id' => $assignment->person_id,
                        'guardian_id' => $assignment->guardian_id,
                        'national_id' => $assignment->national_id ?: '0000000000',
                        'full_name' => $assignment->recipient_name,
                        'mobile' => $assignment->mobile,
                        'delivered_quantity' => $quantity,
                        'value_per_unit_snapshot' => $unitValue,
                        'delivered_total_value' => (int) round($quantity * $unitValue),
                        'delivered_at' => now()->toDateString(),
                        'notes' => 'تحویل نهایی از گیت خروج',
                        'created_by' => $operatorId,
                    ]);
                }

                $assignment->forceFill(['status' => GateEntryAssignment::STATUS_FINALIZED])->save();
            }

            return $assignments->count();
        });

        $this->scanStatus = 'paused';
        $this->scanMessage = $created > 0
            ? "خروج تأیید و {$created} قلم به‌صورت نهایی ثبت شد."
            : 'قلم تحویل‌شده‌ای برای ثبت نهایی یافت نشد.';
    }

    public function resumeScanning(): void
    {
        $this->authorizeGate();

        $this->resetScanState();
        $this->manualSearch = '';
        $this->showManualSearch = false;
        $this->scanStatus = 'scanning';
        $this->scanMessage = 'اسکن دوباره فعال شد. QR بعدی را مقابل دوربین نگه دارید.';

        $this->dispatch('id-card-scanner-resume');
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->supportsGateDelivery()
            ->with([
                'categories' => fn ($query) => $query->ordered(),
                'entryFields',
            ])
            ->find($this->selectedServiceId);
    }

    /**
     * Items marked delivered at the Delivery Gate and still awaiting exit finalization.
     */
    public function getDeliveredItemsProperty(): Collection
    {
        return $this->subjectItemsByStatus(GateEntryAssignment::STATUS_DELIVERED);
    }

    /**
     * Items already finalized at the Exit Gate — used to show the locked "already exited" state.
     */
    public function getFinalizedItemsProperty(): Collection
    {
        return $this->subjectItemsByStatus(GateEntryAssignment::STATUS_FINALIZED);
    }

    public function getGateServicesProperty(): Collection
    {
        return Service::query()
            ->supportsGateDelivery()
            ->withCount('categories')
            ->when(trim($this->serviceSearch) !== '', function (Builder $query): void {
                $search = trim($this->serviceSearch);

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('serviceName', fn (Builder $serviceNameQuery) => $serviceNameQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->get();
    }

    public function getServiceSearchActiveProperty(): bool
    {
        return trim($this->serviceSearch) !== '';
    }

    public function getManualCandidatesProperty(): Collection
    {
        $search = trim($this->manualSearch);

        if (! $this->selectedService || mb_strlen($search) < 2) {
            return collect();
        }

        $escaped = addcslashes($search, '\\%_');
        $digits = preg_replace('/\D+/', '', $search) ?: '';

        $people = Person::query()
            ->select(['id', 'first_name', 'last_name', 'full_name', 'person_code', 'national_id'])
            ->where(function (Builder $query) use ($escaped, $digits): void {
                if ($digits !== '') {
                    $query->where('person_code', 'like', "{$digits}%")
                        ->orWhere('national_id', 'like', "{$digits}%");
                } else {
                    $query->where('full_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "%{$escaped}%")
                        ->orWhere('last_name', 'like', "%{$escaped}%");
                }
            })
            ->orderBy('last_name')
            ->limit(6)
            ->get()
            ->map(fn (Person $person): array => [
                'type' => QrIdentity::SUBJECT_PERSON,
                'id' => (int) $person->id,
                'name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
                'code_label' => 'کد مددجو',
                'code' => (string) ($person->person_code ?: '-'),
                'national_id' => (string) ($person->national_id ?: '-'),
            ]);

        $guardians = Guardian::query()
            ->select(['id', 'first_name', 'last_name', 'guardian_code', 'national_code'])
            ->where(function (Builder $query) use ($escaped, $digits): void {
                if ($digits !== '') {
                    $query->where('guardian_code', 'like', "{$digits}%")
                        ->orWhere('national_code', 'like', "{$digits}%");
                } else {
                    $query->where('first_name', 'like', "%{$escaped}%")
                        ->orWhere('last_name', 'like', "%{$escaped}%");
                }
            })
            ->orderBy('last_name')
            ->limit(6)
            ->get()
            ->map(fn (Guardian $guardian): array => [
                'type' => QrIdentity::SUBJECT_GUARDIAN,
                'id' => (int) $guardian->id,
                'name' => $guardian->full_name ?: trim($guardian->first_name.' '.$guardian->last_name) ?: '-',
                'code_label' => 'کد خانوار',
                'code' => (string) ($guardian->guardian_code ?: '-'),
                'national_id' => (string) ($guardian->national_code ?: '-'),
            ]);

        return $people->concat($guardians)->take(8)->values();
    }

    public function render()
    {
        $this->authorizeGate();

        return view('livewire.distribution-operators.gates.exit-gate', [
            'selectedService' => $this->selectedService,
            'gateServices' => $this->selectedServiceId ? collect() : $this->gateServices,
        ]);
    }

    protected function applyPersonScan(Person $person, bool $isDuplicate = false, string $source = 'qr'): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_PERSON;
        $this->scannedPersonId = (int) $person->id;
        $this->scannedGuardianId = null;
        $this->lastScanResult = [
            'type' => QrIdentity::SUBJECT_PERSON,
            'subject_label' => 'مددجو',
            'code_key' => $isDuplicate ? 'duplicate' : 'success',
            'title' => $isDuplicate ? 'این مددجو هم‌اکنون روی صفحه است' : 'مددجو شناسایی شد',
            'name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
            'avatar_url' => $person->profile_photo ? asset($person->profile_photo) : null,
            'code_label' => 'کد مددجو',
            'code' => (string) ($person->formatted_person_code ?: $person->person_code ?: '-'),
            'national_id' => (string) ($person->national_id ?: '-'),
            'mobile' => (string) ($person->phone_number ?: ($person->guardian?->guardian_phone_number ?: '-')),
            'details' => $this->personDetails($person),
            'extra_fields' => $this->subjectEntryFields(),
        ];
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('مددجو', $isDuplicate, $source);
    }

    protected function applyGuardianScan(Guardian $guardian, bool $isDuplicate = false, string $source = 'qr'): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_GUARDIAN;
        $this->scannedGuardianId = (int) $guardian->id;
        $this->scannedPersonId = null;
        $this->lastScanResult = [
            'type' => QrIdentity::SUBJECT_GUARDIAN,
            'subject_label' => 'سرپرست خانوار',
            'code_key' => $isDuplicate ? 'duplicate' : 'success',
            'title' => $isDuplicate ? 'این خانوار هم‌اکنون روی صفحه است' : 'سرپرست خانوار شناسایی شد',
            'name' => $guardian->full_name ?: trim($guardian->first_name.' '.$guardian->last_name) ?: '-',
            'avatar_url' => null,
            'code_label' => 'کد خانوار',
            'code' => (string) ($guardian->guardian_code ?: '-'),
            'national_id' => (string) ($guardian->national_code ?: '-'),
            'mobile' => (string) ($guardian->guardian_phone_number ?: '-'),
            'details' => $this->guardianDetails($guardian),
            'extra_fields' => $this->subjectEntryFields(),
        ];
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('خانوار', $isDuplicate, $source);
    }

    protected function subjectLoadedMessage(string $subjectLabel, bool $isDuplicate, string $source): string
    {
        $delivered = $this->deliveredItems->count();
        $finalized = $this->finalizedItems->count();
        $prefix = $source === 'manual' ? 'به‌صورت دستی انتخاب شد. ' : '';

        if ($delivered === 0 && $finalized > 0) {
            return "{$prefix}این {$subjectLabel} پیش‌تر از گیت خروج تأیید شده است؛ {$finalized} قلم به‌صورت نهایی ثبت شده است.";
        }

        if ($delivered === 0) {
            return "برای این {$subjectLabel} قلم تحویل‌شده‌ای برای این خدمت ثبت نشده است.";
        }

        if ($isDuplicate) {
            return "این {$subjectLabel} هم‌اکنون انتخاب شده است؛ {$delivered} قلم آماده تأیید خروج است.";
        }

        return "{$prefix}{$delivered} قلم تحویل‌شده نمایش داده شد. اطلاعات را بررسی و خروج را تأیید کنید.";
    }

    protected function isCurrentSubject(string $subjectType, int $subjectId): bool
    {
        return $subjectType === QrIdentity::SUBJECT_GUARDIAN
            ? $this->scannedGuardianId === $subjectId
            : $this->scannedPersonId === $subjectId;
    }

    protected function findPerson(int $subjectId): ?Person
    {
        return Person::query()
            ->with(['socialWorker', 'guardian:id,guardian_phone_number'])
            ->find($subjectId);
    }

    protected function findGuardian(int $subjectId): ?Guardian
    {
        return Guardian::query()
            ->with(['socialWorker', 'residence.district'])
            ->withCount('people')
            ->find($subjectId);
    }

    /**
     * Secondary identity fields used by the operator to confirm the right person — kept short on purpose.
     */
    protected function personDetails(Person $person): array
    {
        $details = [];

        if ($person->gender_label) {
            $details[] = ['label' => 'جنسیت', 'value' => (string) $person->gender_label];
        }

        if ($person->age) {
            $details[] = ['label' => 'سن', 'value' => $person->age.' سال'];
        }

        if (trim((string) $person->father_name) !== '') {
            $details[] = ['label' => 'نام پدر', 'value' => (string) $person->father_name];
        }

        if ($person->socialWorker) {
            $details[] = ['label' => 'مددکار', 'value' => $this->workerLabel($person->socialWorker)];
        }

        return $details;
    }

    protected function guardianDetails(Guardian $guardian): array
    {
        $details = [
            ['label' => 'اعضای خانوار', 'value' => (string) ($guardian->people_count ?? 0)],
        ];

        if ($guardian->socialWorker) {
            $details[] = ['label' => 'مددکار', 'value' => $this->workerLabel($guardian->socialWorker)];
        }

        $district = $guardian->residence?->district?->name;

        if ($district) {
            $details[] = ['label' => 'منطقه', 'value' => (string) $district];
        }

        return $details;
    }

    protected function workerLabel(SocialWorker $worker): string
    {
        $name = trim($worker->first_name.' '.$worker->last_name);
        $code = $worker->worker_code ? str_pad((string) $worker->worker_code, 2, '0', STR_PAD_LEFT) : null;

        if ($name === '') {
            return $code ? "کد {$code}" : '-';
        }

        return $code ? "{$name} (کد {$code})" : $name;
    }

    protected function subjectItemsByStatus(string $status): Collection
    {
        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return collect();
        }

        return $this->subjectAssignmentQuery()
            ->where('status', $status)
            ->with(['serviceCategory' => fn ($query) => $query->ordered()])
            ->get()
            ->sortByDesc(fn (GateEntryAssignment $assignment) => $assignment->serviceCategory?->sort_id ?? 0)
            ->values();
    }

    /**
     * The selected service's extra fields filled in for the scanned subject at the Entry Gate,
     * formatted as label/value rows for the identity card. Education-level ids resolve to their name.
     *
     * @return array<int, array{label: string, value: string}>
     */
    protected function subjectEntryFields(): array
    {
        $service = $this->selectedService;

        if (! $service || ! $this->hasScannedSubject() || $service->entryFields->isEmpty()) {
            return [];
        }

        $isGuardian = $this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN;

        $record = GateEntryFieldValue::query()
            ->where('service_id', $this->selectedServiceId)
            ->when($isGuardian,
                fn ($query) => $query->where('guardian_id', $this->scannedGuardianId),
                fn ($query) => $query->where('person_id', $this->scannedPersonId),
            )
            ->first();

        $values = collect($record?->values ?? [])
            ->mapWithKeys(fn ($value, $fieldId): array => [(int) $fieldId => $value]);

        if ($values->isEmpty()) {
            return [];
        }

        $educationNames = null;
        $rows = [];

        foreach ($service->entryFields as $field) {
            $value = $values[$field->id] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($field->type === ServiceEntryField::TYPE_EDUCATION_LEVEL) {
                $educationNames ??= EducationLevel::query()->pluck('name', 'id');
                $value = $educationNames[(int) $value] ?? null;

                if (! $value) {
                    continue;
                }
            }

            $rows[] = ['label' => $field->title ?: 'بدون عنوان', 'value' => (string) $value];
        }

        return $rows;
    }

    protected function subjectAssignmentQuery()
    {
        $query = GateEntryAssignment::query()
            ->where('service_id', $this->selectedServiceId);

        if ($this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN) {
            return $query->where('guardian_id', $this->scannedGuardianId);
        }

        return $query->where('person_id', $this->scannedPersonId);
    }

    protected function hasScannedSubject(): bool
    {
        return $this->scannedPersonId !== null || $this->scannedGuardianId !== null;
    }

    protected function resetScanState(): void
    {
        $this->lastScanResult = null;
        $this->scannedSubjectType = null;
        $this->scannedPersonId = null;
        $this->scannedGuardianId = null;
    }

    protected function scanError(string $message, string $code = 'error'): array
    {
        $this->resetScanState();
        $this->scanStatus = 'scan_error';
        $this->scanMessage = $message;

        return [
            'ok' => false,
            'status' => 'scan_error',
            'message' => $message,
            'result' => ['code' => $code, 'ok' => false, 'message' => $message],
        ];
    }

    protected function scanResponse(bool $ok): array
    {
        // The browser scanner reads result.code to pick its feedback (success chirp vs duplicate chime);
        // the Blade view reads the component's own lastScanResult, so the subject code stays intact there.
        $feedback = $this->lastScanResult['code_key'] ?? ($ok ? 'success' : 'error');

        return [
            'ok' => $ok,
            'status' => $this->scanStatus,
            'message' => $this->scanMessage,
            'result' => $this->lastScanResult
                ? array_merge($this->lastScanResult, ['code' => $feedback])
                : null,
        ];
    }

    protected function describeUnresolvedQr(string $token): string
    {
        $hashed = app(QrIdentityService::class)->hashToken($token);

        $identity = QrIdentity::query()
            ->where('token_hash', $hashed)
            ->orWhere('public_code', strtoupper(trim($token)))
            ->latest('id')
            ->first();

        if ($identity && ! $identity->isActive()) {
            return match ($identity->status) {
                QrIdentity::STATUS_REVOKED => 'این QR ابطال شده است و دیگر معتبر نیست.',
                QrIdentity::STATUS_REPLACED => 'این QR جایگزین شده است؛ از کارت جدید فرد استفاده کنید.',
                default => 'این QR غیرفعال است.',
            };
        }

        return 'QR نامعتبر است یا در این سامانه ثبت نشده است.';
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

        if (! $subject || (method_exists($subject, 'trashed') && $subject->trashed())) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity->setRelation('subject', $subject);
    }

    protected function extractQrToken(string $payload): ?string
    {
        $value = trim($payload);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\/qr\/r\/([^\/?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }

    protected function authorizeGate(): void
    {
        abort_unless(auth()->check() && auth()->user()->can(self::ABILITY), 403);
    }
}
