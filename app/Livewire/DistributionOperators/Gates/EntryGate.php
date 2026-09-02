<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
use App\Models\GateEntryDeliveryRecipient;
use App\Models\GateEntryFieldValue;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceEntryField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('layouts.distribution-operator')]
class EntryGate extends AbstractGateComponent
{
    public const ABILITY = 'access-distribution-inbound-gate';

    public array $assignedCategoryIds = [];

    /** @var array<int, int> Category ids locked because their assignment is already delivered/finalized downstream. */
    public array $lockedCategoryIds = [];

    /** @var array<int, array{title: string, type: string}> Editable definitions of the selected service's extra fields, keyed by field id. */
    public array $entryFieldDrafts = [];

    public bool $showFieldConfig = false;

    /** @var array<int, mixed> Values entered for the scanned subject, keyed by service_entry_field id. */
    public array $entryFieldValues = [];

    /** Operator's declaration that this subject's items are handed to somebody else. */
    public bool $isProxyDelivery = false;

    /** One of GateEntryDeliveryRecipient::TYPE_OPTIONS keys; empty until the operator picks one. */
    public string $proxyRecipientType = '';

    /** Name/details of the recipient — only stored when the type is "other". */
    public string $proxyRecipientName = '';

    /** Inline validation message shown under the delivery-method box. */
    public ?string $proxyError = null;

    /** @var array<int, string>|null Cached education levels. */
    private ?array $cachedEducationLevels = null;

    protected function scanContext(): string
    {
        return 'distribution-entry-gate';
    }

    protected function selectServicePrompt(): string
    {
        return 'ابتدا خدمت گیت ورود را انتخاب کنید.';
    }

    protected function onServiceSelected(): void
    {
        $this->loadEntryFieldDrafts();
    }

    protected function onServiceCleared(): void
    {
        $this->entryFieldDrafts = [];
        $this->showFieldConfig = false;
    }

    protected function onSubjectLoaded(): void
    {
        $this->loadSubjectAssignments();
        $this->loadSubjectFieldValues();
        $this->loadSubjectDeliveryRecipient();

        // Open the mobile categories bottom-sheet now that a subject is on screen.
        $this->dispatch('entry-gate-subject-loaded');
    }

    protected function resetGateSpecificState(): void
    {
        $this->assignedCategoryIds = [];
        $this->lockedCategoryIds = [];
        $this->entryFieldValues = [];
        $this->isProxyDelivery = false;
        $this->proxyRecipientType = '';
        $this->proxyRecipientName = '';
        $this->proxyError = null;
    }

    /** The Entry Gate renders the extra fields as editable inputs, not identity-card rows. */
    protected function scanResultExtras(): array
    {
        return [];
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

    /**
     * Restore the delivery-method declaration for the scanned subject so re-scanning shows what
     * was recorded earlier instead of silently resetting to a direct handover.
     */
    protected function loadSubjectDeliveryRecipient(): void
    {
        $this->proxyError = null;

        if (! $this->hasScannedSubject()) {
            $this->isProxyDelivery = false;
            $this->proxyRecipientType = '';
            $this->proxyRecipientName = '';

            return;
        }

        $record = $this->subjectDeliveryRecipientQuery()->first();

        $this->isProxyDelivery = (bool) $record?->is_proxy_delivery;
        $this->proxyRecipientType = (string) ($record?->recipient_type ?? '');
        $this->proxyRecipientName = (string) ($record?->recipient_name ?? '');
    }

    /**
     * Toggling the "delivery to non-customer" checkbox. Persisting immediately keeps the
     * declaration safe if the operator walks away before confirming, and mirrors how the extra
     * fields already save on blur.
     */
    public function updatedIsProxyDelivery(): void
    {
        $this->authorizeGate();

        if (! $this->isProxyDelivery) {
            // Clearing the flag also clears the recipient so a stale "mother" can't linger on the row.
            $this->proxyRecipientType = '';
            $this->proxyRecipientName = '';
        }

        $this->persistDeliveryRecipient();
    }

    public function updatedProxyRecipientType(): void
    {
        $this->authorizeGate();

        if (! array_key_exists($this->proxyRecipientType, GateEntryDeliveryRecipient::TYPE_OPTIONS)) {
            $this->proxyRecipientType = '';
        }

        if ($this->proxyRecipientType !== GateEntryDeliveryRecipient::TYPE_OTHER) {
            $this->proxyRecipientName = '';
        }

        $this->persistDeliveryRecipient();
    }

    public function updatedProxyRecipientName(): void
    {
        $this->authorizeGate();

        $this->persistDeliveryRecipient();
    }

    /**
     * Upsert the scanned subject's declaration for this service.
     *
     * Only a complete declaration is written — a half-filled one (box ticked, recipient not chosen
     * yet) would read back as "handed to somebody, unknown who" in the reports. Until it is complete
     * the choice lives in the component only, and confirmPermission() refuses to move on. Turning the
     * box back off updates an existing row so the record never contradicts the screen.
     */
    protected function persistDeliveryRecipient(): void
    {
        if (! $this->selectedServiceId || ! $this->hasScannedSubject()) {
            return;
        }

        $this->proxyError = null;

        $type = $this->isProxyDelivery ? $this->proxyRecipientType : '';
        $name = $type === GateEntryDeliveryRecipient::TYPE_OTHER ? trim($this->proxyRecipientName) : '';

        $isComplete = $this->isProxyDelivery
            && array_key_exists($type, GateEntryDeliveryRecipient::TYPE_OPTIONS)
            && ($type !== GateEntryDeliveryRecipient::TYPE_OTHER || $name !== '');

        $existing = $this->subjectDeliveryRecipientQuery()->first();

        if (! $isComplete && (! $existing || $this->isProxyDelivery)) {
            return;
        }

        $isGuardian = $this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN;

        $record = $existing ?? GateEntryDeliveryRecipient::query()->newModelInstance([
            'service_id' => $this->selectedServiceId,
            'person_id' => $isGuardian ? null : $this->scannedPersonId,
            'guardian_id' => $isGuardian ? $this->scannedGuardianId : null,
            'created_by' => auth()->id(),
        ]);

        $record->forceFill([
            'is_proxy_delivery' => $this->isProxyDelivery,
            'recipient_type' => $type ?: null,
            'recipient_name' => $name ?: null,
        ]);

        if ($record->exists) {
            $record->updated_by = auth()->id();
        }

        try {
            $record->save();
        } catch (UniqueConstraintViolationException) {
            // Another station recorded a declaration for the same (service, subject) between our read
            // and write. Converge onto that row instead of surfacing a 500.
            $winner = $this->subjectDeliveryRecipientQuery()->first();

            $winner?->forceFill([
                'is_proxy_delivery' => $this->isProxyDelivery,
                'recipient_type' => $type ?: null,
                'recipient_name' => $name ?: null,
                'updated_by' => auth()->id(),
            ])->save();
        }
    }

    protected function subjectDeliveryRecipientQuery()
    {
        $query = GateEntryDeliveryRecipient::query()
            ->where('service_id', $this->selectedServiceId);

        if ($this->scannedSubjectType === QrIdentity::SUBJECT_GUARDIAN) {
            return $query->where('guardian_id', $this->scannedGuardianId);
        }

        return $query->where('person_id', $this->scannedPersonId);
    }

    public function toggleCategory(int $categoryId): void
    {
        $this->authorizeGate();

        if (! $this->selectedServiceId || ! $this->hasScannedSubject()) {
            return;
        }

        // One existence check instead of loading the service with its categories and entry fields:
        // a toggle only needs to know that this category still belongs to the selected gate service.
        $isEligibleCategory = Service::query()
            ->supportsGateDelivery()
            ->whereKey($this->selectedServiceId)
            ->whereHas('categories', fn (Builder $query) => $query->whereKey($categoryId))
            ->exists();

        if (! $isEligibleCategory) {
            return;
        }

        // The client already flipped this item optimistically (see the entryGateCategories
        // Alpine component), so persisting is all the server needs to do. Skipping the render
        // avoids re-querying the service, its categories and its extra fields and re-diffing the
        // whole gate — identity card, scanner column and checklist — on every single tap.
        $this->skipRender();

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
                $this->assignmentAttributes($categoryId),
                ['deleted_at' => null],
            ))->save();
        } else {
            try {
                GateEntryAssignment::query()->create($this->assignmentAttributes($categoryId));
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

    public function confirmPermission(): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        // A half-filled proxy declaration would be recorded as "delivered to someone else, unknown who",
        // which defeats the point of the field — hold the operator on this subject until it is complete.
        if ($this->isProxyDelivery) {
            if (! array_key_exists($this->proxyRecipientType, GateEntryDeliveryRecipient::TYPE_OPTIONS)) {
                $this->proxyError = 'گیرنده خدمت را انتخاب کنید.';

                return;
            }

            if ($this->proxyRecipientType === GateEntryDeliveryRecipient::TYPE_OTHER && trim($this->proxyRecipientName) === '') {
                $this->proxyError = 'نام و مشخصات گیرنده را وارد کنید.';

                return;
            }
        }

        $this->resumeScanning();
    }

    /** Persian label of the currently selected proxy recipient, for the summary chip. */
    public function getProxyRecipientLabelProperty(): string
    {
        if (! $this->isProxyDelivery) {
            return '';
        }

        if ($this->proxyRecipientType === GateEntryDeliveryRecipient::TYPE_OTHER) {
            return trim($this->proxyRecipientName) ?: GateEntryDeliveryRecipient::TYPE_OPTIONS[GateEntryDeliveryRecipient::TYPE_OTHER];
        }

        return GateEntryDeliveryRecipient::TYPE_OPTIONS[$this->proxyRecipientType] ?? '';
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
}
