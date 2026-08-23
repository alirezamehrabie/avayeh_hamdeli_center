<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
use App\Models\GateEntryFieldValue;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceEntryField;
use App\Models\SocialWorker;
use App\Services\QrIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Shared core of the three distribution gates (Entry, Delivery, Exit).
 *
 * All gates follow the same operator workflow — pick a gate-eligible service, scan a person or
 * guardian QR (with a manual-search fallback), show the subject's identity card, then apply the
 * gate-specific action. That whole pipeline lives here once; concrete gates only define their
 * ability, their wording, and what happens when a subject lands on screen.
 */
abstract class AbstractGateComponent extends Component
{
    /** Shown once a service is selected and the operator should start scanning. */
    protected const CAMERA_PROMPT = 'دوربین را فعال کنید و QR مددجو یا سرپرست خانوار را اسکن کنید.';

    #[\Livewire\Attributes\Url(as: 'service', except: null)]
    public ?int $selectedServiceId = null;

    #[\Livewire\Attributes\Url(as: 'q', except: '')]
    public string $serviceSearch = '';

    public string $scanStatus = 'ready';

    public string $scanMessage = '';

    public ?array $lastScanResult = null;

    public ?string $scannedSubjectType = null;

    public ?int $scannedPersonId = null;

    public ?int $scannedGuardianId = null;

    public string $manualSearch = '';

    public bool $showManualSearch = false;

    /** The QrIdentityScanLog context recorded for scans at this gate. */
    abstract protected function scanContext(): string;

    /** Prompt shown while no service is selected yet, naming this gate. */
    abstract protected function selectServicePrompt(): string;

    /** Status line shown under the identity card once a subject is loaded. */
    abstract protected function subjectLoadedMessage(string $subjectLabel, bool $isDuplicate, string $source): string;

    /** Hook: a service was just selected (or restored from the URL on mount). */
    protected function onServiceSelected(): void {}

    /** Hook: the selected service was cleared via changeService(). */
    protected function onServiceCleared(): void {}

    /** Hook: a subject was just scanned/selected — load whatever this gate shows for them. */
    protected function onSubjectLoaded(): void {}

    /** Hook: scanning was re-armed for the next subject. */
    protected function onResumeScanning(): void {}

    /** Hook: clear gate-specific per-subject state alongside the shared scan state. */
    protected function resetGateSpecificState(): void {}

    /**
     * Extra key/value entries merged into the identity-card payload. Delivery and Exit surface the
     * Entry Gate's extra fields here; the Entry Gate itself renders them as editable inputs instead.
     */
    protected function scanResultExtras(): array
    {
        return ['extra_fields' => $this->subjectEntryFields()];
    }

    public function mount(): void
    {
        $this->authorizeGate();

        // A service id can arrive from the URL on reload — drop it if it is no longer gate-eligible.
        if ($this->selectedServiceId !== null && ! $this->selectedService) {
            $this->selectedServiceId = null;
        }

        if ($this->selectedServiceId !== null) {
            $this->serviceSearch = '';
            $this->onServiceSelected();
            $this->scanMessage = static::CAMERA_PROMPT;
        } else {
            $this->scanMessage = $this->selectServicePrompt();
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
        $this->onServiceSelected();
        $this->scanStatus = 'ready';
        $this->scanMessage = static::CAMERA_PROMPT;
    }

    public function changeService(): void
    {
        $this->authorizeGate();

        $this->selectedServiceId = null;
        $this->resetScanState();
        $this->onServiceCleared();
        $this->scanStatus = 'ready';
        $this->scanMessage = $this->selectServicePrompt();
    }

    public function clearServiceSearch(): void
    {
        $this->serviceSearch = '';
    }

    public function resolveScannedQr(string $payload): array
    {
        $this->authorizeGate();

        if (! $this->selectedService) {
            return $this->scanError($this->selectServicePrompt());
        }

        $qrService = app(QrIdentityService::class);
        $token = $qrService->extractToken($payload);

        if (! $token) {
            return $this->scanError('QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');
        }

        $identity = $qrService->resolveToken($token, $this->scanContext());

        if (! $identity) {
            $identity = $qrService->resolvePublicCode($token);
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

    public function resumeScanning(): void
    {
        $this->authorizeGate();

        $this->resetScanState();
        $this->manualSearch = '';
        $this->showManualSearch = false;
        $this->scanStatus = 'scanning';
        $this->scanMessage = 'اسکن دوباره فعال شد. QR بعدی را مقابل دوربین نگه دارید.';

        $this->dispatch('id-card-scanner-resume');
        $this->onResumeScanning();
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

    protected function applyPersonScan(Person $person, bool $isDuplicate = false, string $source = 'qr'): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_PERSON;
        $this->scannedPersonId = (int) $person->id;
        $this->scannedGuardianId = null;
        $this->lastScanResult = array_merge([
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
            'social_worker' => $this->workerFullName($person->guardian?->socialWorker),
            'details' => $this->personDetails($person),
        ], $this->scanResultExtras());
        $this->onSubjectLoaded();
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('مددجو', $isDuplicate, $source);
    }

    protected function applyGuardianScan(Guardian $guardian, bool $isDuplicate = false, string $source = 'qr'): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_GUARDIAN;
        $this->scannedGuardianId = (int) $guardian->id;
        $this->scannedPersonId = null;
        $this->lastScanResult = array_merge([
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
            'social_worker' => $this->workerFullName($guardian->socialWorker),
            'details' => $this->guardianDetails($guardian),
        ], $this->scanResultExtras());
        $this->onSubjectLoaded();
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('خانوار', $isDuplicate, $source);
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
            ->with(['guardian:id,guardian_phone_number,social_worker_id', 'guardian.socialWorker'])
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

        return $details;
    }

    protected function guardianDetails(Guardian $guardian): array
    {
        $details = [
            ['label' => 'اعضای خانوار', 'value' => (string) ($guardian->people_count ?? 0)],
        ];

        $district = $guardian->residence?->district?->name;

        if ($district) {
            $details[] = ['label' => 'منطقه', 'value' => (string) $district];
        }

        return $details;
    }

    /** Full name of the subject household's assigned social worker, shown prominently on the identity card. */
    protected function workerFullName(?SocialWorker $worker): string
    {
        return $worker ? trim($worker->first_name.' '.$worker->last_name) : '';
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
        $this->resetGateSpecificState();
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

    protected function authorizeGate(): void
    {
        abort_unless(auth()->check() && auth()->user()->can(static::ABILITY), 403);
    }
}
