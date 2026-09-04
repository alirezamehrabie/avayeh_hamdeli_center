<?php

namespace App\Livewire\SocialWorkers;

use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\Person;
use App\Services\AttendanceSheetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.social-worker')]
class Attendance extends Component
{
    public string $activeSection = 'attendance';

    public ?int $sheetId = null;

    public string $mode = AttendanceSheet::MODE_IN;

    public string $newSheetName = '';

    public string $scanStatus = 'initializing';

    public string $scanMessage = '';

    public ?array $lastScanResult = null;

    public string $manualSearch = '';

    public ?int $selectedPersonId = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);

        $this->scanMessage = 'یک حضور و غیاب بسازید یا انتخاب کنید.';
    }

    public function createSheet(): void
    {
        $validated = $this->validate([
            'newSheetName' => [
                'required',
                'string',
                'max:120',
                Rule::unique('attendance_sheets', 'name')
                    ->where(fn ($query) => $query
                        ->where('social_worker_id', $this->currentSocialWorkerId())
                        ->whereNull('deleted_at')),
            ],
        ], [], ['newSheetName' => 'نام حضور و غیاب']);

        $sheet = AttendanceSheet::query()->create([
            'social_worker_id' => $this->currentSocialWorkerId(),
            'name' => trim($validated['newSheetName']),
            'created_by' => auth()->id(),
        ]);

        $this->newSheetName = '';
        $this->openSheet($sheet->id);
    }

    public function openSheet(int $sheetId): void
    {
        $sheet = $this->findOwnSheet($sheetId);
        abort_unless($sheet, 404);

        $this->sheetId = $sheet->id;
        $this->mode = AttendanceSheet::MODE_IN;
        $this->resetScanState();
        $this->scanStatus = 'ready';
        $this->scanMessage = $this->modeHint();
    }

    public function closeSheetView(): void
    {
        $this->sheetId = null;
        $this->resetScanState();
        $this->scanStatus = 'initializing';
        $this->scanMessage = 'یک حضور و غیاب بسازید یا انتخاب کنید.';
    }

    public function setMode(string $mode): void
    {
        $sheet = $this->sheet;
        abort_unless($sheet, 404);

        if (! in_array($mode, [AttendanceSheet::MODE_IN, AttendanceSheet::MODE_OUT], true) || $mode === $this->mode) {
            return;
        }

        $this->mode = $mode;

        $this->resetScanState();
        $this->scanStatus = 'scanning';
        $this->scanMessage = $this->modeHint();

        // Keep the camera live so the worker never has to press resume after switching mode.
        $this->dispatch('id-card-scanner-resume');
    }

    public function resolveScannedQr(string $payload): array
    {
        $sheet = $this->sheet;
        abort_unless($sheet, 404);

        $service = app(AttendanceSheetService::class);
        $result = $this->isCheckOutMode()
            ? $service->checkOutByQr($sheet, $payload, auth()->user())
            : $service->checkInByQr($sheet, $payload, auth()->user());

        $this->applyResult($result->toArray());

        return [
            'ok' => $result->ok,
            'status' => $this->scanStatus,
            'message' => $this->scanMessage,
            'result' => $this->lastScanResult,
        ];
    }

    public function selectManualPerson(int $personId): void
    {
        $this->selectedPersonId = $personId;
    }

    public function manualRegister(): void
    {
        $sheet = $this->sheet;
        abort_unless($sheet, 404);

        $person = $this->selectedPersonId
            ? $this->ownPeopleQuery()->find($this->selectedPersonId)
            : null;

        $service = app(AttendanceSheetService::class);
        $result = $this->isCheckOutMode()
            ? $service->checkOutPerson($sheet, $person, auth()->user(), 'manual')
            : $service->checkInPerson($sheet, $person, auth()->user(), 'manual');

        $this->applyResult($result->toArray());
        $this->selectedPersonId = null;
        $this->manualSearch = '';
    }

    public function resumeScanning(): void
    {
        abort_unless($this->sheet, 404);

        $this->scanStatus = 'scanning';
        $this->scanMessage = $this->modeHint();
        $this->dispatch('id-card-scanner-resume');
    }

    public function getSheetProperty(): ?AttendanceSheet
    {
        return $this->sheetId ? $this->findOwnSheet($this->sheetId) : null;
    }

    public function getSheetsProperty(): EloquentCollection
    {
        return AttendanceSheet::query()
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->withCount([
                'entries as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
                'entries as checked_out_count' => fn ($query) => $query->whereNotNull('checked_out_at'),
            ])
            ->latest('id')
            ->limit(30)
            ->get();
    }

    public function getManualCandidatesProperty(): Collection
    {
        $search = trim($this->manualSearch);

        if (mb_strlen($search) < 2 || ! $this->sheetId) {
            return collect();
        }

        $normalized = Person::normalizeSearchText($search);
        $escaped = $this->escapeLike($search);
        $escapedNormalized = $this->escapeLike($normalized);
        $digits = preg_replace('/\D+/', '', $search) ?: '';
        $escapedDigits = $this->escapeLike($digits);

        return $this->ownPeopleQuery()
            ->select(['id', 'first_name', 'last_name', 'full_name', 'person_code', 'national_id'])
            ->when($this->isCheckOutMode(), fn (Builder $query) => $query->whereHas(
                'attendanceSheetEntries',
                fn (Builder $entryQuery) => $entryQuery
                    ->where('attendance_sheet_id', $this->sheetId)
                    ->whereNotNull('checked_in_at')
                    ->whereNull('checked_out_at')
            ))
            ->where(function (Builder $query) use ($escaped, $escapedNormalized, $escapedDigits, $normalized): void {
                if ($escapedDigits !== '') {
                    $query->where('person_code', 'like', "{$escapedDigits}%")
                        ->orWhere('national_id', 'like', "{$escapedDigits}%");
                } else {
                    $query->where('person_code', 'like', "{$escaped}%")
                        ->orWhere('national_id', 'like', "{$escaped}%");
                }

                if ($escapedNormalized !== '') {
                    $query->orWhere('normalized_full_name', 'like', "{$escapedNormalized}%")
                        ->orWhere('normalized_first_name', 'like', "{$escapedNormalized}%")
                        ->orWhere('normalized_last_name', 'like', "{$escapedNormalized}%");

                    if (mb_strlen($normalized) >= 3) {
                        $query->orWhere('normalized_full_name', 'like', "%{$escapedNormalized}%");
                    }
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        $sheet = $this->sheet;

        return view('livewire.social-workers.attendance', [
            'sheet' => $sheet,
            'sheets' => $sheet ? collect() : $this->sheets,
            'entries' => $sheet ? $this->sheetEntries($sheet) : collect(),
            'checkedInCount' => $sheet ? $sheet->entries()->whereNotNull('checked_in_at')->count() : 0,
            'checkedOutCount' => $sheet ? $sheet->entries()->whereNotNull('checked_out_at')->count() : 0,
            'manualCandidates' => $sheet ? $this->manualCandidates : collect(),
            'selectedPerson' => $this->selectedPersonId ? $this->ownPeopleQuery()->find($this->selectedPersonId) : null,
        ]);
    }

    protected function sheetEntries(AttendanceSheet $sheet): EloquentCollection
    {
        return AttendanceSheetEntry::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->when(
                $this->isCheckOutMode(),
                fn ($query) => $query->orderByRaw('checked_out_at IS NULL')->latest('checked_out_at'),
                fn ($query) => $query->latest('checked_in_at')
            )
            ->limit(50)
            ->get();
    }

    protected function isCheckOutMode(): bool
    {
        return $this->mode === AttendanceSheet::MODE_OUT;
    }

    protected function modeHint(): string
    {
        return $this->isCheckOutMode()
            ? 'حالت خروج فعال است. QR مددجو را مقابل دوربین بگیرید.'
            : 'حالت ورود فعال است. QR مددجو را مقابل دوربین بگیرید.';
    }

    protected function applyResult(array $result): void
    {
        $this->lastScanResult = $result;
        $this->scanStatus = $result['ok'] ? 'paused' : 'scan_error';
        $this->scanMessage = (string) ($result['message'] ?? '');
    }

    protected function resetScanState(): void
    {
        $this->lastScanResult = null;
        $this->selectedPersonId = null;
        $this->manualSearch = '';
        $this->resetValidation();
    }

    protected function findOwnSheet(int $sheetId): ?AttendanceSheet
    {
        return AttendanceSheet::query()
            ->where('social_worker_id', $this->currentSocialWorkerId())
            ->find($sheetId);
    }

    protected function ownPeopleQuery(): Builder
    {
        return Person::query()->whereHas(
            'guardian',
            fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId())
        );
    }

    protected function currentSocialWorkerId(): int
    {
        return (int) auth()->user()->social_worker_id;
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
