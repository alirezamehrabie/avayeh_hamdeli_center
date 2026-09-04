<?php

namespace App\Livewire\Admin;

use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Live monitor of social workers' beneficiary attendance sheets for managers.
 *
 * Read-only by design: the social worker panel owns the data; this screen only
 * observes it. The root view polls every 20 seconds while visible.
 */
class AttendanceMonitor extends Component
{
    use WithPagination;

    public const RANGE_OPTIONS = [
        'today' => 'امروز',
        'week' => '۷ روز اخیر',
        'month' => '۳۰ روز اخیر',
        'all' => 'همه',
    ];

    public const TAB_OPTIONS = [
        'sheets' => 'شیت‌های حضور و غیاب',
        'present' => 'حاضران همین حالا',
    ];

    public bool $embedded = false;

    public string $range = 'today';

    public string $activeTab = 'sheets';

    public string $search = '';

    /** '' means all social workers. */
    public string $socialWorkerFilter = '';

    public ?int $expandedSheetId = null;

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-social-workers'), 403);
    }

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-social-workers'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['range', 'search', 'socialWorkerFilter'], true)) {
            $this->resetPage('sheets');
            $this->resetPage('present');
            $this->expandedSheetId = null;
        }
    }

    public function setRange(string $range): void
    {
        if (! array_key_exists($range, self::RANGE_OPTIONS) || $range === $this->range) {
            return;
        }

        $this->range = $range;
        $this->resetPage('sheets');
        $this->resetPage('present');
        $this->expandedSheetId = null;
    }

    public function setActiveTab(string $tab): void
    {
        if (! array_key_exists($tab, self::TAB_OPTIONS) || $tab === $this->activeTab) {
            return;
        }

        $this->activeTab = $tab;
        $this->expandedSheetId = null;
    }

    public function toggleSheet(int $sheetId): void
    {
        $this->expandedSheetId = $this->expandedSheetId === $sheetId ? null : $sheetId;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->socialWorkerFilter = '';
        $this->expandedSheetId = null;
        $this->resetPage('sheets');
        $this->resetPage('present');
    }

    public function render()
    {
        $sheetIds = fn () => $this->sheetsQuery()->reorder()->select('attendance_sheets.id');

        return view('livewire.admin.attendance-monitor', [
            'sheets' => $this->sheetsQuery()->paginate(10, ['*'], 'sheets'),
            'presentEntries' => $this->activeTab === 'present'
                ? $this->presentQuery()->paginate(20, ['*'], 'present')
                : collect(),
            'expandedEntries' => $this->expandedSheetId
                ? $this->sheetEntries($this->expandedSheetId)
                : collect(),
            'socialWorkers' => $this->socialWorkerOptions(),
            'stats' => [
                'sheets' => (clone $this->sheetsQuery())->count(),
                'checkIns' => AttendanceSheetEntry::query()
                    ->whereIn('attendance_sheet_id', $sheetIds())
                    ->whereNotNull('checked_in_at')
                    ->count(),
                'checkOuts' => AttendanceSheetEntry::query()
                    ->whereIn('attendance_sheet_id', $sheetIds())
                    ->whereNotNull('checked_out_at')
                    ->count(),
                'present' => $this->presentQuery()->count(),
            ],
        ]);
    }

    /**
     * @return Collection<int, SocialWorker>
     */
    protected function socialWorkerOptions(): Collection
    {
        return SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->whereIn('id', AttendanceSheet::query()->select('social_worker_id'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    protected function sheetsQuery(): Builder
    {
        $search = trim($this->search);
        $like = '%'.$this->escapeLike($search).'%';

        return AttendanceSheet::query()
            ->with('socialWorker:id,first_name,last_name,worker_code')
            ->withCount([
                'entries as check_ins_count' => fn (Builder $query) => $query->whereNotNull('checked_in_at'),
                'entries as check_outs_count' => fn (Builder $query) => $query->whereNotNull('checked_out_at'),
                'entries as present_count' => fn (Builder $query) => $query
                    ->whereNotNull('checked_in_at')
                    ->whereNull('checked_out_at'),
            ])
            ->when($this->rangeStart(), fn (Builder $query, $start) => $query->where('created_at', '>=', $start))
            ->when($this->socialWorkerFilter !== '', fn (Builder $query) => $query->where('social_worker_id', (int) $this->socialWorkerFilter))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhereHas('socialWorker', fn (Builder $worker) => $worker
                        ->where('full_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like))
                    ->orWhereHas('entries', fn (Builder $entry) => $entry
                        ->where('person_name', 'like', $like)
                        ->orWhere('person_code', 'like', $like)
                        ->orWhere('national_id', 'like', $like));
            }))
            ->latest('id');
    }

    /**
     * Entries whose beneficiary is inside right now (checked in, not checked out).
     * Deliberately range-free: presence is a live fact, not a historical query.
     */
    protected function presentQuery(): Builder
    {
        $search = trim($this->search);
        $like = '%'.$this->escapeLike($search).'%';

        return AttendanceSheetEntry::query()
            ->with([
                'sheet:id,name,social_worker_id',
                'sheet.socialWorker:id,first_name,last_name,worker_code',
            ])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->when($this->socialWorkerFilter !== '', fn (Builder $query) => $query->whereHas(
                'sheet',
                fn (Builder $sheet) => $sheet->where('social_worker_id', (int) $this->socialWorkerFilter)
            ))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($like): void {
                $query->where('person_name', 'like', $like)
                    ->orWhere('person_code', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhereHas('sheet', fn (Builder $sheet) => $sheet->where('name', 'like', $like));
            }))
            ->orderByDesc('checked_in_at');
    }

    protected function sheetEntries(int $sheetId): Collection
    {
        return AttendanceSheetEntry::query()
            ->where('attendance_sheet_id', $sheetId)
            ->orderByRaw('checked_out_at IS NULL DESC')
            ->orderByDesc('checked_in_at')
            ->limit(100)
            ->get();
    }

    protected function rangeStart(): ?Carbon
    {
        return match ($this->range) {
            'today' => today(),
            'week' => now()->subDays(6)->startOfDay(),
            'month' => now()->subDays(29)->startOfDay(),
            default => null,
        };
    }

    public function formatPresenceDuration(?Carbon $since): string
    {
        if (! $since) {
            return '-';
        }

        $minutes = max(0, (int) $since->diffInMinutes(now()));
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.' دقیقه';
        }

        return $hours.' ساعت'.($rest > 0 ? ' و '.$rest.' دقیقه' : '');
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
