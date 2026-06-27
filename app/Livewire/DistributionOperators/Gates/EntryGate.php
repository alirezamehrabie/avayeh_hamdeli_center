<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\GateEntryAssignment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Services\QrIdentityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class EntryGate extends Component
{
    public const ABILITY = 'access-distribution-inbound-gate';

    public ?int $selectedServiceId = null;

    public string $scanStatus = 'ready';

    public string $scanMessage = '';

    public ?array $lastScanResult = null;

    public ?string $scannedSubjectType = null;

    public ?int $scannedPersonId = null;

    public ?int $scannedGuardianId = null;

    public array $assignedCategoryIds = [];

    public function mount(): void
    {
        $this->authorizeGate();

        $this->scanMessage = 'ابتدا خدمت گیت ورود را انتخاب کنید.';
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
            return $this->scanError('QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.');
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            $guardian = Guardian::query()->find((int) $identity->subject_id);

            if (! $guardian) {
                return $this->scanError('اطلاعات خانوار برای این QR پیدا نشد.');
            }

            $this->applyGuardianScan($guardian);

            return $this->scanResponse(true);
        }

        $person = Person::query()->find((int) $identity->subject_id);

        if (! $person) {
            return $this->scanError('اطلاعات مددجو برای این QR پیدا نشد.');
        }

        $this->applyPersonScan($person);

        return $this->scanResponse(true);
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
            ->orderByDesc('id')
            ->get();
    }

    public function render()
    {
        $this->authorizeGate();

        return view('livewire.distribution-operators.gates.entry-gate', [
            'selectedService' => $this->selectedService,
            'gateServices' => $this->selectedServiceId ? collect() : $this->gateServices,
        ]);
    }

    protected function applyPersonScan(Person $person): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_PERSON;
        $this->scannedPersonId = (int) $person->id;
        $this->scannedGuardianId = null;
        $this->lastScanResult = [
            'type' => QrIdentity::SUBJECT_PERSON,
            'title' => 'مددجو شناسایی شد',
            'name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name) ?: '-',
            'code_label' => 'کد مددجو',
            'code' => (string) ($person->formatted_person_code ?: $person->person_code ?: '-'),
            'national_id' => (string) ($person->national_id ?: '-'),
            'mobile' => (string) ($person->phone_number ?: ($person->guardian?->guardian_phone_number ?: '-')),
        ];
        $this->loadSubjectAssignments();
        $this->scanStatus = 'paused';
        $this->scanMessage = 'اطلاعات مددجو نمایش داده شد. دسته‌بندی‌های مجاز را انتخاب کنید.';
    }

    protected function applyGuardianScan(Guardian $guardian): void
    {
        $this->scannedSubjectType = QrIdentity::SUBJECT_GUARDIAN;
        $this->scannedGuardianId = (int) $guardian->id;
        $this->scannedPersonId = null;
        $this->lastScanResult = [
            'type' => QrIdentity::SUBJECT_GUARDIAN,
            'title' => 'سرپرست خانوار شناسایی شد',
            'name' => $guardian->full_name ?: trim($guardian->first_name.' '.$guardian->last_name) ?: '-',
            'code_label' => 'کد خانوار',
            'code' => (string) ($guardian->guardian_code ?: '-'),
            'national_id' => (string) ($guardian->national_code ?: '-'),
            'mobile' => (string) ($guardian->guardian_phone_number ?: '-'),
        ];
        $this->loadSubjectAssignments();
        $this->scanStatus = 'paused';
        $this->scanMessage = 'اطلاعات خانوار نمایش داده شد. دسته‌بندی‌های مجاز را انتخاب کنید.';
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

    protected function scanError(string $message): array
    {
        $this->resetScanState();
        $this->scanStatus = 'scan_error';
        $this->scanMessage = $message;

        return $this->scanResponse(false);
    }

    protected function scanResponse(bool $ok): array
    {
        return [
            'ok' => $ok,
            'status' => $this->scanStatus,
            'message' => $this->scanMessage,
            'result' => $this->lastScanResult,
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
