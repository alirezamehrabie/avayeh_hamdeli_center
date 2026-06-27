<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\GateEntryAssignment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Services\QrIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class EntryGate extends Component
{
    public const ABILITY = 'access-distribution-inbound-gate';

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

    public array $assignedCategoryIds = [];

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
            $this->scanMessage = 'ابتدا خدمت گیت ورود را انتخاب کنید.';
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
        $this->scanMessage = 'ابتدا خدمت گیت ورود را انتخاب کنید.';
    }

    public function clearServiceSearch(): void
    {
        $this->serviceSearch = '';
    }

    public function resolveScannedQr(string $payload): array
    {
        $this->authorizeGate();

        if (! $this->selectedService) {
            return $this->scanError('ابتدا خدمت گیت ورود را انتخاب کنید.');
        }

        $token = $this->extractQrToken($payload);

        if (! $token) {
            return $this->scanError('QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'distribution-entry-gate');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity) {
            // Distinguish a revoked/replaced card from an unknown one so the operator knows what to do next.
            return $this->scanError($this->describeUnresolvedQr($token), 'invalid');
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            $guardian = Guardian::query()->find((int) $identity->subject_id);

            if (! $guardian) {
                return $this->scanError('اطلاعات خانوار برای این QR پیدا نشد.', 'not_found');
            }

            $isDuplicate = $this->isCurrentSubject(QrIdentity::SUBJECT_GUARDIAN, (int) $guardian->id);
            $this->applyGuardianScan($guardian, $isDuplicate);

            return $this->scanResponse(true);
        }

        $person = Person::query()->find((int) $identity->subject_id);

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
            $guardian = Guardian::query()->find($subjectId);

            if ($guardian) {
                $this->applyGuardianScan($guardian, $this->isCurrentSubject($subjectType, $subjectId), 'manual');
            }
        } else {
            $person = Person::query()->find($subjectId);

            if ($person) {
                $this->applyPersonScan($person, $this->isCurrentSubject($subjectType, $subjectId), 'manual');
            }
        }

        $this->manualSearch = '';
        $this->showManualSearch = false;
    }

    public function toggleCategory(int $categoryId): void
    {
        $this->authorizeGate();

        $service = $this->selectedService;

        if (! $service || ! $this->hasScannedSubject()) {
            return;
        }

        $category = $service->categories->firstWhere('id', $categoryId);

        if (! $category) {
            return;
        }

        $existing = $this->subjectAssignmentQuery()
            ->where('service_category_id', $categoryId)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->assignedCategoryIds = array_values(array_diff($this->assignedCategoryIds, [$categoryId]));

            return;
        }

        GateEntryAssignment::query()->create($this->assignmentAttributes($category->id));

        if (! in_array($categoryId, $this->assignedCategoryIds, true)) {
            $this->assignedCategoryIds[] = $categoryId;
        }
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
            ->with(['categories' => fn ($query) => $query->ordered()])
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

    public function render()
    {
        $this->authorizeGate();

        return view('livewire.distribution-operators.gates.entry-gate', [
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
            'code_key' => $isDuplicate ? 'duplicate' : 'success',
            'title' => $isDuplicate ? 'این مددجو هم‌اکنون روی صفحه است' : 'مددجو شناسایی شد',
            'name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
            'code_label' => 'کد مددجو',
            'code' => (string) ($person->formatted_person_code ?: $person->person_code ?: '-'),
            'national_id' => (string) ($person->national_id ?: '-'),
            'mobile' => (string) ($person->phone_number ?: ($person->guardian?->guardian_phone_number ?: '-')),
        ];
        $this->loadSubjectAssignments();
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
            'code_key' => $isDuplicate ? 'duplicate' : 'success',
            'title' => $isDuplicate ? 'این خانوار هم‌اکنون روی صفحه است' : 'سرپرست خانوار شناسایی شد',
            'name' => $guardian->full_name ?: trim($guardian->first_name.' '.$guardian->last_name) ?: '-',
            'code_label' => 'کد خانوار',
            'code' => (string) ($guardian->guardian_code ?: '-'),
            'national_id' => (string) ($guardian->national_code ?: '-'),
            'mobile' => (string) ($guardian->guardian_phone_number ?: '-'),
        ];
        $this->loadSubjectAssignments();
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('خانوار', $isDuplicate, $source);
    }

    protected function subjectLoadedMessage(string $subjectLabel, bool $isDuplicate, string $source): string
    {
        if ($isDuplicate) {
            $assigned = count($this->assignedCategoryIds);

            return $assigned > 0
                ? "این {$subjectLabel} قبلاً اسکن شده و {$assigned} دسته‌بندی برای آن ثبت شده است."
                : "این {$subjectLabel} هم‌اکنون انتخاب شده است. دسته‌بندی‌های مجاز را انتخاب کنید.";
        }

        $prefix = $source === 'manual' ? 'به‌صورت دستی انتخاب شد. ' : '';

        return "{$prefix}اطلاعات {$subjectLabel} نمایش داده شد. دسته‌بندی‌های مجاز را انتخاب کنید.";
    }

    protected function isCurrentSubject(string $subjectType, int $subjectId): bool
    {
        return $subjectType === QrIdentity::SUBJECT_GUARDIAN
            ? $this->scannedGuardianId === $subjectId
            : $this->scannedPersonId === $subjectId;
    }

    protected function loadSubjectAssignments(): void
    {
        $this->assignedCategoryIds = $this->hasScannedSubject()
            ? $this->subjectAssignmentQuery()->pluck('service_category_id')->map(fn ($id) => (int) $id)->all()
            : [];
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

    protected function assignmentAttributes(int $categoryId): array
    {
        $isGuardian = $this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN;

        return [
            'service_id' => $this->selectedServiceId,
            'service_category_id' => $categoryId,
            'person_id' => $isGuardian ? null : $this->scannedPersonId,
            'guardian_id' => $isGuardian ? $this->scannedGuardianId : null,
            'national_id' => (string) ($this->lastScanResult['national_id'] ?? '') ?: null,
            'full_name' => (string) ($this->lastScanResult['name'] ?? '') ?: null,
            'mobile' => ($this->lastScanResult['mobile'] ?? '-') !== '-' ? $this->lastScanResult['mobile'] : null,
            'status' => GateEntryAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'created_by' => auth()->id(),
        ];
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
        $this->assignedCategoryIds = [];
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
