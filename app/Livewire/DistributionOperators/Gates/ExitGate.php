<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;

#[Layout('layouts.distribution-operator')]
class ExitGate extends AbstractGateComponent
{
    public const ABILITY = 'access-distribution-outbound-gate';

    /** Whether the current subject's finalized exit has been unlocked by a manager. */
    public bool $exitUnlocked = false;

    /** Manager password typed into the unlock form. */
    public string $managerPassword = '';

    /** Inline error shown when the manager password is rejected. */
    public ?string $unlockError = null;

    protected function scanContext(): string
    {
        return 'distribution-exit-gate';
    }

    protected function onSubjectLoaded(): void
    {
        $this->lockExit();
    }

    protected function resetGateSpecificState(): void
    {
        $this->exitUnlocked = false;
        $this->managerPassword = '';
        $this->unlockError = null;
    }

    protected function selectServicePrompt(): string
    {
        return 'ابتدا خدمت گیت خروج را انتخاب کنید.';
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

    /**
     * Authenticate an override against a manager/admin account's hashed password. On success the
     * current subject's finalized exit is unlocked so its categories can be individually cancelled.
     */
    public function unlockExit(): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        $password = trim($this->managerPassword);
        $this->managerPassword = '';
        $this->unlockError = null;

        if ($password === '') {
            $this->unlockError = 'رمز عبور مدیریت را وارد کنید.';

            return;
        }

        $approvers = User::query()
            ->whereIn('access_level', [User::ACCESS_LEVEL_MANAGER, User::ACCESS_LEVEL_ADMIN])
            ->get();

        foreach ($approvers as $approver) {
            if (Hash::check($password, $approver->password)) {
                $this->exitUnlocked = true;

                return;
            }
        }

        $this->unlockError = 'رمز عبور مدیریت نادرست است.';
    }

    public function lockExit(): void
    {
        $this->authorizeGate();

        $this->exitUnlocked = false;
        $this->managerPassword = '';
        $this->unlockError = null;
    }

    /**
     * Cancel one already-finalized category: the delivery ledger row is removed and the assignment
     * returns to "pending" so it can be redelivered from the Delivery Gate. Only reachable while the
     * subject's exit is unlocked by a manager password (see unlockExit).
     */
    public function cancelFinalizedCategory(int $assignmentId): void
    {
        $this->authorizeGate();

        if (! $this->exitUnlocked || ! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        $assignment = $this->subjectAssignmentQuery()
            ->where('id', $assignmentId)
            ->where('status', GateEntryAssignment::STATUS_FINALIZED)
            ->first();

        if (! $assignment) {
            return;
        }

        DB::transaction(function () use ($assignment): void {
            // Force-deleting frees the unique gate_entry_assignment_id slot so the category can be
            // delivered and finalized cleanly again; the model's deleted event refreshes stock.
            ServiceDelivery::query()
                ->where('gate_entry_assignment_id', $assignment->id)
                ->forceDelete();

            $assignment->forceFill([
                'status' => GateEntryAssignment::STATUS_PENDING,
                'delivered_at' => null,
                'delivered_by' => null,
            ])->save();
        });

        $this->dispatch('exit-category-cancelled');
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

    public function render()
    {
        $this->authorizeGate();

        return view('livewire.distribution-operators.gates.exit-gate', [
            'selectedService' => $this->selectedService,
            'gateServices' => $this->selectedServiceId ? collect() : $this->gateServices,
        ]);
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
}
