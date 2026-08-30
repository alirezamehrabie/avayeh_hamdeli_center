<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
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

        // Open the mobile categories bottom-sheet now that a subject is on screen.
        $this->dispatch('entry-gate-subject-loaded');
    }

    protected function resetGateSpecificState(): void
    {
        $this->assignedCategoryIds = [];
        $this->lockedCategoryIds = [];
        $this->entryFieldValues = [];
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

        $this->resumeScanning();
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
