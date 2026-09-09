<?php

namespace App\Livewire\Admin;

use App\Helpers\Morilog\Jalalian;
use App\Models\GateEntryAssignment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceDeliveryCancellation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class GateTechnicalReport extends Component
{
    use WithPagination;

    public const TAB_GATE = 'gate';

    public const TAB_OPERATORS = 'operators';

    /**
     * Page tabs. Adding a future tab = one entry here plus a new section in
     * the view; setTab() validates against these keys.
     */
    public const TABS = [
        self::TAB_GATE => 'گزارش گیت',
        self::TAB_OPERATORS => 'گزارش مسئول گیت',
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
        // Raw aggregates return MAX() as strings while model casts give Carbon
        // instances — parse both into Jalali here.
        $jalaliDateTime = fn ($dateTime) => $dateTime ? Jalalian::fromDateTime(Carbon::parse((string) $dateTime))->format('Y/m/d H:i') : '—';

        // Tab isolation: each tab runs ONLY its own queries.
        if ($this->activeTab === self::TAB_OPERATORS) {
            return view('livewire.admin.gate-technical-report', [
                'tabs' => self::TABS,
                'operatorReports' => $this->buildOperatorReports(),
                'unitOptions' => Service::unitOptions(),
                'jalaliDateTime' => $jalaliDateTime,
            ]);
        }

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
            'operatorReports' => null,
            'unitOptions' => [],
            'jalaliDateTime' => $jalaliDateTime,
        ]);
    }

    /**
     * Per-gate operator performance for THIS service, from recorded activity
     * only (constant aggregate queries grouped by user + category, no N+1):
     *   entry    → assignments.created_by (مجوز ثبت‌شده)
     *   delivery → assignments.delivered_by (تأیید تحویل)
     *   exit     → gate ledger created_by + cancellation canceled_by
     * The authorized roster (distribution operators holding each gate
     * permission) is merged in, so permission-holders with zero activity are
     * flagged «بدون ثبت» and historical actors whose permission was later
     * revoked show as «بدون مجوز فعلی».
     *
     * @return array<string, array<string, mixed>>
     */
    protected function buildOperatorReports(): array
    {
        $gateConfig = [
            'entry' => [
                'label' => 'گیت ورود (Entry)',
                'permission' => User::PERMISSION_DISTRIBUTION_INBOUND_GATE,
                'extraColumn' => 'تأیید در تحویل',
            ],
            'delivery' => [
                'label' => 'گیت تحویل (Delivery)',
                'permission' => User::PERMISSION_DISTRIBUTION_DELIVERY_GATE,
                'extraColumn' => 'خروج قطعی‌شده',
            ],
            'exit' => [
                'label' => 'گیت خروج (Exit)',
                'permission' => User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE,
                'extraColumn' => 'ثبت در دفترچه',
            ],
        ];

        // A subject is counted once per operator/category: individuals by
        // person id, households by guardian id (prefixed to avoid id clashes).
        $subjectsExpr = "COUNT(DISTINCT COALESCE(person_id, CONCAT('g', guardian_id)))";

        $entryRows = GateEntryAssignment::query()
            ->where('service_id', $this->service->id)
            ->whereNotNull('created_by')
            ->selectRaw(
                'created_by as user_id, service_category_id, COUNT(*) as records,'
                .'SUM(CASE WHEN status <> ? THEN 1 ELSE 0 END) as extra, MAX(assigned_at) as last_at',
                [GateEntryAssignment::STATUS_PENDING]
            )
            ->groupBy('created_by', 'service_category_id')
            ->get();

        $deliveryRows = GateEntryAssignment::query()
            ->where('service_id', $this->service->id)
            ->whereNotNull('delivered_by')
            ->selectRaw(
                'delivered_by as user_id, service_category_id, COUNT(*) as records,'
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as extra, MAX(delivered_at) as last_at',
                [GateEntryAssignment::STATUS_FINALIZED]
            )
            ->groupBy('delivered_by', 'service_category_id')
            ->get();

        $exitRows = ServiceDelivery::query()
            ->where('service_id', $this->service->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_GATE)
            ->whereNotNull('created_by')
            ->selectRaw(
                'created_by as user_id, service_category_id, COUNT(*) as records,'
                .'COALESCE(SUM(delivered_quantity), 0) as quantity, COALESCE(SUM(delivered_total_value), 0) as total_value,'
                .'MAX(delivered_at) as last_at'
            )
            ->groupBy('created_by', 'service_category_id')
            ->get();

        $cancelRows = ServiceDeliveryCancellation::query()
            ->where('service_id', $this->service->id)
            ->whereNotNull('canceled_by')
            ->selectRaw('canceled_by as user_id, service_category_id, COUNT(*) as records, MAX(canceled_at) as last_at')
            ->groupBy('canceled_by', 'service_category_id')
            ->get();

        $cancelsByUser = $cancelRows->groupBy('user_id');
        $cancelsByUserAndCategory = $cancelRows->groupBy(fn ($row) => $row->user_id.'|'.$row->service_category_id);

        // Distinct subjects per operator across all their categories (the
        // per-category figures would double-count multi-category subjects).
        $subjectsPerUser = [
            'entry' => GateEntryAssignment::query()
                ->where('service_id', $this->service->id)->whereNotNull('created_by')
                ->selectRaw('created_by as user_id, '.$subjectsExpr.' as subjects')
                ->groupBy('created_by')->pluck('subjects', 'user_id'),
            'delivery' => GateEntryAssignment::query()
                ->where('service_id', $this->service->id)->whereNotNull('delivered_by')
                ->selectRaw('delivered_by as user_id, '.$subjectsExpr.' as subjects')
                ->groupBy('delivered_by')->pluck('subjects', 'user_id'),
            'exit' => ServiceDelivery::query()
                ->where('service_id', $this->service->id)
                ->where('delivery_channel', Service::DELIVERY_CHANNEL_GATE)->whereNotNull('created_by')
                ->selectRaw('created_by as user_id, '.$subjectsExpr.' as subjects')
                ->groupBy('created_by')->pluck('subjects', 'user_id'),
        ];

        // Roster: distribution operators with at least one gate permission.
        $roster = User::query()
            ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
            ->where('is_admin', false)
            ->where(function (Builder $query) use ($gateConfig): void {
                foreach ($gateConfig as $gate) {
                    $query->orWhereJsonContains('permissions', $gate['permission']);
                }
            })
            ->get(['id', 'name', 'first_name', 'last_name', 'permissions']);

        // Every actor id seen in stats too (historical operators who lost the
        // permission are still reported, flagged accordingly).
        $actorIds = $entryRows->pluck('user_id')
            ->merge($deliveryRows->pluck('user_id'))
            ->merge($exitRows->pluck('user_id'))
            ->merge($cancelRows->pluck('user_id'))
            ->filter()
            ->unique();

        $usersById = User::query()
            ->whereIn('id', $roster->pluck('id')->merge($actorIds)->unique()->all())
            ->get(['id', 'name', 'first_name', 'last_name', 'permissions'])
            ->keyBy('id');

        $categoryIds = $entryRows->pluck('service_category_id')
            ->merge($deliveryRows->pluck('service_category_id'))
            ->merge($exitRows->pluck('service_category_id'))
            ->merge($cancelRows->pluck('service_category_id'))
            ->filter()
            ->unique();

        $categoriesById = ServiceCategory::query()
            ->withTrashed()
            ->whereIn('id', $categoryIds->all())
            ->get()
            ->keyBy('id');

        $unitOptions = Service::unitOptions();

        $statsByGate = [
            'entry' => $entryRows,
            'delivery' => $deliveryRows,
            'exit' => $exitRows,
        ];

        $reports = [];

        foreach ($gateConfig as $gateKey => $gate) {
            $rowsByUser = $statsByGate[$gateKey]->groupBy('user_id');

            $permissionHolders = $roster
                ->filter(fn (User $user) => in_array($gate['permission'], $user->getPermissionKeys(), true))
                ->pluck('id');

            $userIds = $permissionHolders->merge($rowsByUser->keys())->unique();

            $operators = $userIds->map(function ($userId) use ($gateKey, $gate, $rowsByUser, $usersById, $categoriesById, $unitOptions, $cancelsByUser, $cancelsByUserAndCategory, $subjectsPerUser) {
                $user = $usersById->get($userId);
                $rows = $rowsByUser->get($userId, collect());

                $categories = $rows->map(function ($row) use ($categoriesById, $unitOptions, $gateKey, $cancelsByUserAndCategory) {
                    $category = $row->service_category_id ? $categoriesById->get((int) $row->service_category_id) : null;
                    $unitKey = $category?->unit;

                    return [
                        'category' => $category?->name ?: '—',
                        'categoryModel' => $category,
                        'unitLabel' => $unitKey ? ($unitOptions[$unitKey] ?? $unitKey) : '—',
                        'records' => (int) $row->records,
                        'extra' => (int) ($row->extra ?? 0),
                        'quantity' => $gateKey === 'exit' ? Service::formatQuantityForUnit((float) $row->quantity, $unitKey ?: null) : null,
                        'totalValue' => $gateKey === 'exit' ? (int) $row->total_value : null,
                        'cancelled' => $gateKey === 'exit'
                            ? (int) ($cancelsByUserAndCategory->get($row->user_id.'|'.$row->service_category_id)?->sum('records') ?? 0)
                            : 0,
                        'lastAt' => $row->last_at,
                    ];
                })->sortByDesc('records')->values();

                return [
                    'userId' => (int) $userId,
                    'name' => trim((string) ($user?->first_name.' '.$user?->last_name)) ?: ($user?->name ?: 'کاربر حذف‌شده'),
                    'username' => $user?->name,
                    'authorized' => in_array($gate['permission'], $user?->getPermissionKeys() ?? [], true),
                    'totalRecords' => (int) $rows->sum('records'),
                    'subjects' => (int) ($subjectsPerUser[$gateKey][$userId] ?? 0),
                    'extraTotal' => (int) $rows->sum('extra'),
                    'quantityTotal' => $gateKey === 'exit' ? Service::formatQuantityForUnit((float) $rows->sum('quantity'), null) : null,
                    'valueTotal' => $gateKey === 'exit' ? (int) $rows->sum('total_value') : null,
                    'cancelledTotal' => $gateKey === 'exit' ? (int) ($cancelsByUser->get($userId)?->sum('records') ?? 0) : 0,
                    'lastAt' => $rows->max('last_at'),
                    'categories' => $categories,
                ];
            })
                ->sortBy([
                    ['totalRecords', 'desc'],
                    ['name', 'asc'],
                ])
                ->values()
                ->all();

            $reports[$gateKey] = [
                'label' => $gate['label'],
                'extraColumn' => $gate['extraColumn'],
                'operators' => $operators,
                'totalRecords' => array_sum(array_column($operators, 'totalRecords')),
                'activeCount' => count(array_filter($operators, fn (array $o): bool => $o['totalRecords'] > 0)),
                'idleCount' => count(array_filter($operators, fn (array $o): bool => $o['authorized'] && $o['totalRecords'] === 0)),
            ];
        }

        return $reports;
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
