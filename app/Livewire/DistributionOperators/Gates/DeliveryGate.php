<?php

namespace App\Livewire\DistributionOperators\Gates;

use App\Models\GateEntryAssignment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('layouts.distribution-operator')]
class DeliveryGate extends AbstractGateComponent
{
    public const ABILITY = 'access-distribution-delivery-gate';

    /** @var array<int, int> Category ids whose assignment is already marked delivered. */
    public array $deliveredCategoryIds = [];

    /** @var array<int, int> Category ids locked because they were already finalized at the Exit Gate. */
    public array $finalizedCategoryIds = [];

    protected function scanContext(): string
    {
        return 'distribution-delivery-gate';
    }

    protected function selectServicePrompt(): string
    {
        return 'ابتدا خدمت گیت تحویل را انتخاب کنید.';
    }

    protected function onSubjectLoaded(): void
    {
        $this->loadSubjectDeliveryState();
    }

    protected function onResumeScanning(): void
    {
        $this->dispatch('delivery-confirmed');
    }

    protected function resetGateSpecificState(): void
    {
        $this->deliveredCategoryIds = [];
        $this->finalizedCategoryIds = [];
    }

    protected function subjectLoadedMessage(string $subjectLabel, bool $isDuplicate, string $source): string
    {
        $authorized = $this->authorizedItems->count();

        if ($authorized === 0) {
            return "برای این {$subjectLabel} در گیت ورود قلمی برای این خدمت ثبت نشده است.";
        }

        $delivered = count($this->deliveredCategoryIds);
        $prefix = $source === 'manual' ? 'به‌صورت دستی انتخاب شد. ' : '';

        if ($isDuplicate) {
            return "این {$subjectLabel} هم‌اکنون انتخاب شده است؛ {$delivered} از {$authorized} قلم تحویل شده است.";
        }

        return "{$prefix}{$authorized} قلم مجاز برای تحویل نمایش داده شد. اقلام تحویل‌شده را علامت بزنید.";
    }

    public function toggleDelivered(int $categoryId): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        // Only items approved at the Entry Gate are deliverable here — no assignment, no delivery.
        $assignment = $this->subjectAssignmentQuery()
            ->where('service_category_id', $categoryId)
            ->first();

        if (! $assignment) {
            return;
        }

        // An item already finalized at the Exit Gate is locked. Reverting it here would push it back to
        // "delivered" and let the Exit Gate finalize it a second time, creating a duplicate ServiceDelivery
        // ledger entry (a double distribution). Only pending ⇄ delivered transitions are allowed at this gate.
        if ($assignment->status === GateEntryAssignment::STATUS_FINALIZED) {
            return;
        }

        if ($assignment->status === GateEntryAssignment::STATUS_DELIVERED) {
            $assignment->forceFill([
                'status' => GateEntryAssignment::STATUS_PENDING,
                'delivered_at' => null,
                'delivered_by' => null,
            ])->save();
            $this->deliveredCategoryIds = array_values(array_diff($this->deliveredCategoryIds, [$categoryId]));
            $this->skipRender();

            return;
        }

        $assignment->forceFill([
            'status' => GateEntryAssignment::STATUS_DELIVERED,
            'delivered_at' => now(),
            'delivered_by' => auth()->id(),
        ])->save();

        if (! in_array($categoryId, $this->deliveredCategoryIds, true)) {
            $this->deliveredCategoryIds[] = $categoryId;
        }

        // The client already flipped this item optimistically (see the deliveryItems
        // Alpine component); persisting is all the server needs to do. Skipping the render
        // avoids re-querying the service + authorized items and re-diffing the whole gate
        // on every single tap — the core of the old selection lag.
        $this->skipRender();
    }

    /**
     * Confirm the current subject's delivery and jump straight to the next scan.
     *
     * Each item is already persisted the moment it is toggled (see toggleDelivered),
     * so confirming has nothing left to write — it simply closes out this subject and
     * re-arms the scanner, i.e. the exact same workflow as the "next scan" button.
     */
    public function confirmDelivery(): void
    {
        $this->authorizeGate();

        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return;
        }

        $this->resumeScanning();
    }

    /**
     * The items the scanned subject may receive — exactly the categories approved at the Entry Gate.
     */
    public function getAuthorizedItemsProperty(): Collection
    {
        if (! $this->selectedService || ! $this->hasScannedSubject()) {
            return collect();
        }

        return $this->subjectAssignmentQuery()
            ->with(['serviceCategory' => fn ($query) => $query->ordered()])
            ->get()
            ->sortByDesc(fn (GateEntryAssignment $assignment) => $assignment->serviceCategory?->sort_id ?? 0)
            ->values();
    }

    public function render()
    {
        $this->authorizeGate();

        return view('livewire.distribution-operators.gates.delivery-gate', [
            'selectedService' => $this->selectedService,
            'gateServices' => $this->selectedServiceId ? collect() : $this->gateServices,
        ]);
    }

    protected function loadSubjectDeliveryState(): void
    {
        if (! $this->hasScannedSubject()) {
            $this->deliveredCategoryIds = [];
            $this->finalizedCategoryIds = [];

            return;
        }

        // One pass over the subject's rows, then split by status: delivered ones are toggleable,
        // finalized ones are locked (already exited) so the view can render them as read-only.
        $statuses = $this->subjectAssignmentQuery()
            ->whereIn('status', [GateEntryAssignment::STATUS_DELIVERED, GateEntryAssignment::STATUS_FINALIZED])
            ->pluck('status', 'service_category_id');

        $this->deliveredCategoryIds = $statuses
            ->filter(fn (string $status): bool => $status === GateEntryAssignment::STATUS_DELIVERED)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->finalizedCategoryIds = $statuses
            ->filter(fn (string $status): bool => $status === GateEntryAssignment::STATUS_FINALIZED)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
