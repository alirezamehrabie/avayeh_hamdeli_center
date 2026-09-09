<?php

namespace App\Livewire\Admin;

use App\Helpers\Morilog\Jalalian;
use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceDeliveryCancellation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class GateTechnicalReport extends Component
{
    use WithPagination;

    public const TAB_GATE = 'gate';

    /**
     * Page tabs. Adding a future tab = one entry here plus a new section in
     * the view; setTab() validates against these keys.
     */
    public const TABS = [
        self::TAB_GATE => 'گزارش گیت',
    ];

    public Service $service;

    public string $activeTab = self::TAB_GATE;

    public function mount(Service $service): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless((bool) $service->supports_gate_delivery, 404);

        $this->service = $service;
    }

    public function setTab(string $tab): void
    {
        if (! array_key_exists($tab, self::TABS)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function render()
    {
        // The three sections share the same eager loads (view-only columns) so
        // every rendered table reads its relations from loaded models — no N+1.
        $assignmentWith = [
            'serviceCategory:id,name,unit',
            'person:id,person_code',
            'guardian:id,guardian_code',
            'creator:id,first_name,last_name',
            'deliveredBy:id,first_name,last_name',
        ];

        $authorized = GateEntryAssignment::query()->where('service_id', $this->service->id);

        $pendingTotal = (clone $authorized)->where('status', GateEntryAssignment::STATUS_PENDING)->count();
        $deliveredStatuses = [GateEntryAssignment::STATUS_DELIVERED, GateEntryAssignment::STATUS_FINALIZED];
        $deliveredTotal = (clone $authorized)->whereIn('status', $deliveredStatuses)->count();
        $finalizedTotal = (clone $authorized)->where('status', GateEntryAssignment::STATUS_FINALIZED)->count();

        $pendingRows = (clone $authorized)
            ->where('status', GateEntryAssignment::STATUS_PENDING)
            ->with($assignmentWith)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'pending');

        // Exit-gate history of the rows on screen (one query): a pending item
        // with a cancellation was delivered once, then cancelled at Exit.
        $cancelledAssignmentIds = ServiceDeliveryCancellation::query()
            ->where('service_id', $this->service->id)
            ->whereIn('gate_entry_assignment_id', $pendingRows->pluck('id')->all())
            ->distinct()
            ->pluck('gate_entry_assignment_id')
            ->all();

        $deliveredRows = (clone $authorized)
            ->whereIn('status', $deliveredStatuses)
            ->with(array_merge($assignmentWith, ['delivery:id,gate_entry_assignment_id,delivered_at']))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [GateEntryAssignment::STATUS_FINALIZED])
            ->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'delivered');

        // ── Integrity checks: reconciliation between the three gate stages ──
        $orphanCount = $this->orphanDeliveriesQuery()->count();
        $orphanRows = $orphanCount > 0
            ? $this->orphanDeliveriesQuery()
                ->with(['serviceCategory:id,name,unit', 'creator:id,first_name,last_name'])
                ->orderByDesc('delivered_at')
                ->paginate(20, ['*'], 'orphans')
            : null;

        $finalizedNoLedgerCount = (clone $authorized)
            ->where('status', GateEntryAssignment::STATUS_FINALIZED)
            ->whereDoesntHave('delivery')
            ->count();
        $finalizedNoLedgerRows = $finalizedNoLedgerCount > 0
            ? (clone $authorized)
                ->where('status', GateEntryAssignment::STATUS_FINALIZED)
                ->whereDoesntHave('delivery')
                ->with($assignmentWith)
                ->orderByDesc('delivered_at')
                ->paginate(20, ['*'], 'no-ledger')
            : null;

        $deliveredWithLedgerCount = (clone $authorized)
            ->where('status', GateEntryAssignment::STATUS_DELIVERED)
            ->whereHas('delivery')
            ->count();
        $deliveredWithLedgerRows = $deliveredWithLedgerCount > 0
            ? (clone $authorized)
                ->where('status', GateEntryAssignment::STATUS_DELIVERED)
                ->whereHas('delivery')
                ->with(array_merge($assignmentWith, ['delivery:id,gate_entry_assignment_id,delivered_at']))
                ->orderByDesc('delivered_at')
                ->paginate(20, ['*'], 'ledger-ahead')
            : null;

        $cancelledCount = ServiceDeliveryCancellation::query()->where('service_id', $this->service->id)->count();
        $cancelledRows = $cancelledCount > 0
            ? ServiceDeliveryCancellation::query()
                ->where('service_id', $this->service->id)
                ->with([
                    'serviceCategory:id,name,unit',
                    'person:id,person_code,first_name,last_name',
                    'guardian:id,guardian_code,first_name,last_name',
                    'canceller:id,first_name,last_name',
                ])
                ->orderByDesc('canceled_at')
                ->paginate(20, ['*'], 'cancelled')
            : null;

        $discrepancyTotal = $orphanCount + $finalizedNoLedgerCount + $deliveredWithLedgerCount;

        return view('livewire.admin.gate-technical-report', [
            'tabs' => self::TABS,
            'statusLabels' => [
                GateEntryAssignment::STATUS_PENDING => 'در انتظار تحویل',
                GateEntryAssignment::STATUS_DELIVERED => 'تحویل‌شده در گیت تحویل',
                GateEntryAssignment::STATUS_FINALIZED => 'خروج قطعی‌شده',
            ],
            'stats' => [
                'authorized' => $pendingTotal + $deliveredTotal,
                'pending' => $pendingTotal,
                'delivered' => $deliveredTotal,
                'finalized' => $finalizedTotal,
                'cancelled' => $cancelledCount,
                'discrepancies' => $discrepancyTotal,
            ],
            'pendingRows' => $pendingRows,
            'pendingTotal' => $pendingTotal,
            'deliveredRows' => $deliveredRows,
            'deliveredTotal' => $deliveredTotal,
            'cancelledAssignmentIds' => $cancelledAssignmentIds,
            'checks' => [
                [
                    'key' => 'finalized-no-ledger',
                    'title' => 'خروج قطعی‌شده بدون رکورد دفترچه تحویل',
                    'hint' => 'وضعیت نهایی ثبت شده اما ردیف ServiceDelivery وجود ندارد (حذف یا شکست ثبت).',
                    'count' => $finalizedNoLedgerCount,
                    'rows' => $finalizedNoLedgerRows,
                    'kind' => 'assignment',
                    'showLedger' => false,
                ],
                [
                    'key' => 'delivered-with-ledger',
                    'title' => 'تحویل‌شده با رکورد دفترچه اما خروج قطعی‌نشده',
                    'hint' => 'رکورد دفترچه پیش از نهایی‌سازی گیت خروج ساخته شده؛ وضعیت مجوز باید finalized می‌بود.',
                    'count' => $deliveredWithLedgerCount,
                    'rows' => $deliveredWithLedgerRows,
                    'kind' => 'assignment',
                    'showLedger' => true,
                ],
                [
                    'key' => 'orphans',
                    'title' => 'رکوردهای تحویل گیت بدون مجوز Entry',
                    'hint' => 'ردیف دفترچه با کانال گیت که به هیچ مجوز گیت ورودی (حتی حذف‌شده) وصل نیست.',
                    'count' => $orphanCount,
                    'rows' => $orphanRows,
                    'kind' => 'delivery',
                ],
            ],
            'cancelledRows' => $cancelledRows,
            'cancelledCount' => $cancelledCount,
            'jalaliDateTime' => fn ($dateTime) => $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '—',
        ]);
    }

    protected function orphanDeliveriesQuery(): Builder
    {
        return ServiceDelivery::query()
            ->where('service_id', $this->service->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_GATE)
            ->where(function (Builder $query): void {
                $query->whereNull('gate_entry_assignment_id')
                    // Raw NOT EXISTS (no SoftDeletes scope) so an assignment
                    // that was deleted after delivery is NOT counted as orphan.
                    ->orWhereNotExists(function ($exists) {
                        $exists->select(DB::raw(1))
                            ->from('gate_entry_assignments')
                            ->whereColumn('gate_entry_assignments.id', 'service_deliveries.gate_entry_assignment_id');
                    });
            });
    }
}
