<?php

namespace App\Livewire\Admin;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Illuminate\Support\Str;
use Livewire\Component;

class IdCardScanner extends Component
{
    public string $scanStatus = 'initializing';
    public string $scanMessage = '';
    public ?string $resolvedSubjectType = null;
    public ?int $selectedPersonId = null;
    public ?int $selectedGuardianId = null;
    public bool $showPersonModal = false;
    public bool $showHouseholdModal = false;
    public ?array $lastScanResult = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->scanStatus = 'ready';
        $this->scanMessage = 'دوربین را فعال کنید و کد QR را مقابل تصویر قرار دهید.';
    }

    public function resolveScannedQr(string $payload): array
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $token = $this->extractQrToken($payload);

        if (! $token) {
            $this->setInlineError('QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');

            return $this->scanResponse(false);
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'admin-id-card-scanner');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity) {
            $this->setInlineError('QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.');

            return $this->scanResponse(false);
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_GUARDIAN) {
            abort_unless(auth()->user()->can('full-access'), 403);

            $guardian = Guardian::query()->find((int) $identity->subject_id);

            if (! $guardian) {
                $this->setInlineError('اطلاعات خانوار برای این QR پیدا نشد.');

                return $this->scanResponse(false);
            }

            $this->selectedGuardianId = (int) $guardian->id;
            $this->selectedPersonId = null;
            $this->resolvedSubjectType = QrIdentity::SUBJECT_GUARDIAN;
            $this->lastScanResult = [
                'type' => QrIdentity::SUBJECT_GUARDIAN,
                'title' => 'خانوار شناسایی شد',
                'name' => $guardian->full_name ?: trim($guardian->first_name . ' ' . $guardian->last_name) ?: '-',
                'code_label' => 'کد سرپرست خانوار',
                'code' => (string) ($guardian->guardian_code ?: '-'),
            ];
            $this->showHouseholdModal = true;
            $this->showPersonModal = false;
            $this->scanStatus = 'paused';
            $this->scanMessage = 'اطلاعات خانوار شناسایی شد.';

            return $this->scanResponse(true);
        }

        if (! auth()->user()->can('manage-people')) {
            abort(403);
        }

        $person = Person::query()->find((int) $identity->subject_id);

        if (! $person) {
            $this->setInlineError('اطلاعات مددجو برای این QR پیدا نشد.');

            return $this->scanResponse(false);
        }

        $this->selectedPersonId = (int) $person->id;
        $this->selectedGuardianId = null;
        $this->resolvedSubjectType = QrIdentity::SUBJECT_PERSON;
        $this->lastScanResult = [
            'type' => QrIdentity::SUBJECT_PERSON,
            'title' => 'مددجو شناسایی شد',
            'name' => $person->full_name ?: trim($person->first_name . ' ' . $person->last_name) ?: '-',
            'code_label' => 'کد مددجو',
            'code' => (string) ($person->formatted_person_code ?: $person->person_code ?: '-'),
        ];
        $this->showPersonModal = true;
        $this->showHouseholdModal = false;
        $this->scanStatus = 'paused';
        $this->scanMessage = 'اطلاعات مددجو شناسایی شد.';

        return $this->scanResponse(true);
    }

    public function setScanStatus(string $status, string $message = ''): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $allowedStatuses = [
            'initializing',
            'ready',
            'scanning',
            'paused',
            'camera_denied',
            'unsupported',
            'scan_error',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            return;
        }

        $this->scanStatus = $status;

        if ($message !== '') {
            $this->scanMessage = $message;
        }
    }

    public function resumeScanning(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->showPersonModal = false;
        $this->showHouseholdModal = false;
        $this->selectedPersonId = null;
        $this->selectedGuardianId = null;
        $this->resolvedSubjectType = null;
        $this->lastScanResult = null;
        $this->scanStatus = 'scanning';
        $this->scanMessage = 'اسکن دوباره فعال شد. QR را مقابل دوربین نگه دارید.';

        $this->dispatch('id-card-scanner-resume');
    }

    public function closePersonModal(): void
    {
        $this->showPersonModal = false;
        $this->selectedPersonId = null;
        $this->resolvedSubjectType = null;
    }

    public function closeHouseholdModal(): void
    {
        $this->showHouseholdModal = false;
        $this->selectedGuardianId = null;
        $this->resolvedSubjectType = null;
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if (! $this->selectedPersonId) {
            return null;
        }

        return Person::with([
            'guardian.occupation',
            'guardian.jobType',
            'guardian.residence',
            'guardian.socialWorker',
            'education.educationLevel',
            'education.educationDegreeLevel',
            'supportCoverage.organization',
            'disabilityType',
            'familyStatus.guardianRelationType',
            'skills',
            'harmTypes',
            'needsLevel.levelType',
        ])->find($this->selectedPersonId);
    }

    public function getSelectedGuardianProperty(): ?Guardian
    {
        if (! $this->selectedGuardianId) {
            return null;
        }

        return Guardian::with([
            'socialWorker',
            'occupation',
            'insuranceType',
            'vehicleType',
            'residence.residenceStatus',
            'residence.district',
            'people.familyStatus.guardianRelationType',
            'people.harmTypes:id,title',
        ])
            ->withCount('people')
            ->find($this->selectedGuardianId);
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.admin.id-card-scanner');
    }

    private function setInlineError(string $message): void
    {
        $this->showPersonModal = false;
        $this->showHouseholdModal = false;
        $this->selectedPersonId = null;
        $this->selectedGuardianId = null;
        $this->resolvedSubjectType = null;
        $this->lastScanResult = null;
        $this->scanStatus = 'scan_error';
        $this->scanMessage = $message;
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

    private function resolvePublicCode(string $token): ?QrIdentity
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

    private function extractQrToken(string $payload): ?string
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
}
