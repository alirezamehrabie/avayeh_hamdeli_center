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
use Illuminate\Database\UniqueConstraintViolationException;
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

    /** @var array<int, int> Category ids locked because their assignment is already delivered/finalized downstream. */
    public array $lockedCategoryIds = [];

    public string $manualSearch = '';

    public bool $showManualSearch = false;

    /** @var array<int, array{title: string, type: string}> Editable definitions of the selected service's extra fields, keyed by field id. */
    public array $entryFieldDrafts = [];

    public bool $showFieldConfig = false;

    /** @var array<int, mixed> Values entered for the scanned subject, keyed by service_entry_field id. */
    public array $entryFieldValues = [];

    /** @var array<int, string>|null Cached education levels. */
    private ?array $cachedEducationLevels = null;

    public function mount(): void
    {
        $this->authorizeGate();

        // A service id can arrive from the URL on reload — drop it if it is no longer gate-eligible.
        if ($this->selectedServiceId !== null && ! $this->selectedService) {
            $this->selectedServiceId = null;
        }

        if ($this->selectedServiceId !== null) {
            $this->serviceSearch = '';
            $this->loadEntryFieldDrafts();
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
        $this->loadEntryFieldDrafts();
        $this->scanStatus = 'ready';
        $this->scanMessage = 'دوربین را فعال کنید و QR مددجو یا سرپرست خانوار را اسکن کنید.';
    }

    public function changeService(): void
    {
        $this->authorizeGate();

        $this->selectedServiceId = null;
        $this->resetScanState();
        $this->entryFieldDrafts = [];
        $this->showFieldConfig = false;
        $this->scanStatus = 'ready';
        $this->scanMessage = 'ابتدا خدمت گیت ورود را انتخاب کنید.';
    }

    public function clearServiceSearch(): void
    {
        $this->serviceSearch = '';
    }

    public function toggleFieldConfig(): void
    {
        $this->authorizeGate();

        $this->showFieldConfig = ! $this->showFieldConfig;
    }

    /**
     * Define a new extra field for the selected service. Persisted right away so it is reused
     * automatically the next time this service is opened at any gate.
     */
    public function addEntryField(): void
    {
        $this->authorizeGate();

        if (! $this->selectedServiceId) {
            return;
        }

        $maxSortOrder = ServiceEntryField::query()
            ->where('service_id', $this->selectedServiceId)
            ->max('sort_order') ?? 0;

        ServiceEntryField::query()->create([
            'service_id' => $this->selectedServiceId,
            'title' => '',
            'type' => ServiceEntryField::TYPE_TEXT,
            'sort_order' => (int) $maxSortOrder + 1,
        ]);

        $this->showFieldConfig = true;
        $this->loadEntryFieldDrafts();
    }

    public function removeEntryField(int $fieldId): void
    {
        $this->authorizeGate();

        if (! $this->selectedServiceId) {
            return;
        }

        ServiceEntryField::query()
            ->where('service_id', $this->selectedServiceId)
            ->whereKey($fieldId)
            ->delete();

        $this->loadEntryFieldDrafts();
        unset($this->entryFieldValues[$fieldId]);
    }

    /**
     * Persist an edited field definition (title or type) when its input blurs.
     */
    public function updatedEntryFieldDrafts(mixed $value, string $key): void
    {
        $this->authorizeGate();

        if (! $this->selectedService) {
            return;
        }

        // $key is "<fieldId>.title" or "<fieldId>.type".
        [$fieldId, $attribute] = array_pad(explode('.', $key, 2), 2, null);

        if (! in_array($attribute, ['title', 'type'], true)) {
            return;
        }

        $field = ServiceEntryField::query()
            ->where('service_id', $this->selectedServiceId)
            ->whereKey((int) $fieldId)
            ->first();

        if (! $field) {
            return;
        }

        if ($attribute === 'type' && ! array_key_exists((string) $value, ServiceEntryField::TYPE_OPTIONS)) {
            return;
        }

        $field->forceFill([$attribute => is_string($value) ? trim($value) : $value])->save();
    }

    /**
     * Save the entered value for the scanned subject when an input blurs.
     */
    public function updatedEntryFieldValues(mixed $value, string $key): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        $fieldId = (int) $key;

        // Ignore stray keys that no longer map to a defined field for this service.
        if (! $this->selectedService->entryFields->contains('id', $fieldId)) {
            return;
        }

        $isGuardian = $this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN;

        $record = GateEntryFieldValue::query()->firstOrNew([
            'service_id' => $this->selectedServiceId,
            'person_id' => $isGuardian ? null : $this->scannedPersonId,
            'guardian_id' => $isGuardian ? $this->scannedGuardianId : null,
        ]);

        $values = $record->values ?? [];
        $normalized = is_string($value) ? trim($value) : $value;

        if ($normalized === '' || $normalized === null) {
            unset($values[$fieldId]);
        } else {
            $values[$fieldId] = $normalized;
        }

        $record->values = $values;
        $record->created_by ??= auth()->id();
        $record->save();
    }

    protected function loadEntryFieldDrafts(): void
    {
        if (! $this->selectedServiceId) {
            $this->entryFieldDrafts = [];

            return;
        }

        $this->entryFieldDrafts = ServiceEntryField::query()
            ->where('service_id', $this->selectedServiceId)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (ServiceEntryField $field): array => [
                $field->id => ['title' => (string) $field->title, 'type' => (string) $field->type],
            ])
            ->all();
    }

    protected function loadSubjectFieldValues(): void
    {
        if (! $this->hasScannedSubject()) {
            $this->entryFieldValues = [];

            return;
        }

        $isGuardian = $this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN;

        $record = GateEntryFieldValue::query()
            ->where('service_id', $this->selectedServiceId)
            ->when($isGuardian,
                fn ($query) => $query->where('guardian_id', $this->scannedGuardianId),
                fn ($query) => $query->where('person_id', $this->scannedPersonId),
            )
            ->first();

        // Cast keys back to int so they line up with the field ids used in the view.
        $this->entryFieldValues = collect($record?->values ?? [])
            ->mapWithKeys(fn ($value, $fieldId): array => [(int) $fieldId => $value])
            ->all();
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

        // Include trashed rows: toggling a category off only soft-deletes the assignment, but the
        // (service_category_id, person_id/guardian_id) unique index still holds that key. Re-adding via a
        // fresh create() would collide with the soft-deleted row and throw, so we restore-and-reset instead.
        $existing = $this->subjectAssignmentQuery()
            ->withTrashed()
            ->where('service_category_id', $categoryId)
            ->first();

        // Once an item has been delivered or finalized at a later gate it is locked here: soft-deleting it
        // would orphan the ServiceDelivery ledger, and re-authoring it would reset a finalized record to
        // pending. The Entry Gate may only edit items still pending.
        if ($existing && ! $existing->trashed() && in_array($existing->status, [
            GateEntryAssignment::STATUS_DELIVERED,
            GateEntryAssignment::STATUS_FINALIZED,
        ], true)) {
            return;
        }

        if ($existing && ! $existing->trashed()) {
            $existing->delete();
            $this->assignedCategoryIds = array_values(array_diff($this->assignedCategoryIds, [$categoryId]));

            return;
        }

        if ($existing) {
            // Re-authorize a previously removed item: clear the soft delete and refresh its details.
            $existing->forceFill(array_merge(
                $this->assignmentAttributes($category->id),
                ['deleted_at' => null],
            ))->save();
        } else {
            try {
                GateEntryAssignment::query()->create($this->assignmentAttributes($category->id));
            } catch (UniqueConstraintViolationException) {
                // Another station authorized the same item between our read and write. The row now exists
                // with the same (category, recipient) key, so converge to it instead of surfacing a 500.
                $this->reconcileConcurrentAssignment($categoryId);
            }
        }

        if (! in_array($categoryId, $this->assignedCategoryIds, true)) {
            $this->assignedCategoryIds[] = $categoryId;
        }
    }

    /**
     * Restore the shared assignment a concurrent scan created if it was left soft-deleted, so both
     * stations end with the item authorized and exactly one active row for the (category, recipient) key.
     */
    protected function reconcileConcurrentAssignment(int $categoryId): void
    {
        $row = $this->subjectAssignmentQuery()
            ->withTrashed()
            ->where('service_category_id', $categoryId)
            ->first();

        if ($row && $row->trashed()) {
            $row->forceFill(array_merge(
                $this->assignmentAttributes($categoryId),
                ['deleted_at' => null],
            ))->save();
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
            ->with([
                'categories' => fn ($query) => $query->ordered(),
                'entryFields',
            ])
            ->find($this->selectedServiceId);
    }

    public function getEducationLevelsProperty(): Collection
    {
        if ($this->cachedEducationLevels === null) {
            $this->cachedEducationLevels = EducationLevel::query()
                ->orderBy('sort_order')
                ->pluck('name', 'id')
                ->all();
        }

        return collect($this->cachedEducationLevels)->map(fn ($name, $id) => (object) ['id' => $id, 'name' => $name]);
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

        $selectedService = $this->selectedService;

        return view('livewire.distribution-operators.gates.entry-gate', [
            'selectedService' => $selectedService,
            'entryFields' => $selectedService?->entryFields ?? collect(),
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
        ];
        $this->loadSubjectAssignments();
        $this->loadSubjectFieldValues();
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('مددجو', $isDuplicate, $source);

        // Open the mobile categories bottom-sheet now that a subject is on screen.
        $this->dispatch('entry-gate-subject-loaded');
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
        ];
        $this->loadSubjectAssignments();
        $this->loadSubjectFieldValues();
        $this->scanStatus = 'paused';
        $this->scanMessage = $this->subjectLoadedMessage('خانوار', $isDuplicate, $source);

        // Open the mobile categories bottom-sheet now that a subject is on screen.
        $this->dispatch('entry-gate-subject-loaded');
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

    protected function loadSubjectAssignments(): void
    {
        if (! $this->hasScannedSubject()) {
            $this->assignedCategoryIds = [];
            $this->lockedCategoryIds = [];

            return;
        }

        // Pull status alongside the category id so we can mark already delivered/finalized items as locked.
        $statuses = $this->subjectAssignmentQuery()->pluck('status', 'service_category_id');

        $this->assignedCategoryIds = $statuses->keys()->map(fn ($id) => (int) $id)->all();

        $this->lockedCategoryIds = $statuses
            ->filter(fn (string $status): bool => in_array($status, [
                GateEntryAssignment::STATUS_DELIVERED,
                GateEntryAssignment::STATUS_FINALIZED,
            ], true))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
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
        $this->lockedCategoryIds = [];
        $this->entryFieldValues = [];
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
