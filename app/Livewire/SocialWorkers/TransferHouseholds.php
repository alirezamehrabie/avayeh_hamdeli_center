<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use App\Models\SocialWorker;
use App\Services\SocialWorkers\HouseholdTransferService;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * پنجرهٔ انتقال خانوارهای یک مددکار به مددکار دیگر.
 *
 * دو حالت ورود دارد:
 *  - انتقال معمولی (transfer): کلی یا انتخابی.
 *  - انتقال پیش از غیرفعال‌سازی (deactivate): همهٔ خانوارها منتقل و سپس مبدأ غیرفعال می‌شود.
 */
#[AllowDynamicProperties]
class TransferHouseholds extends Component
{
    use InteractsWithNotificationModal;

    public bool $open = false;

    /** intent: transfer | deactivate */
    public string $intent = 'transfer';

    /** mode: all | selective */
    public string $mode = 'all';

    public ?int $sourceWorkerId = null;
    public string $sourceWorkerName = '';
    public string $sourceWorkerCode = '';
    public int $sourceHouseholdCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $sourceGuardians = [];

    /** @var array<int, int> */
    public array $selectedGuardianIds = [];

    // ── انتخابگر مددکار مقصد (هم‌الگو با social-worker-selector) ──
    public string $socialWorkerQuery = '';
    public ?int $socialWorkerId = null;
    public string $selectedSocialWorkerCode = '';
    public string $selectedSocialWorkerDisplay = '';
    public bool $showSocialWorkerSuggestions = false;

    protected ?Collection $socialWorkerSuggestionsCache = null;
    protected ?string $socialWorkerSuggestionsCacheKey = null;

    public function mount(): void
    {
        $this->authorizeManage();
    }

    #[On('open-transfer-households')]
    public function openForTransfer(int $workerId): void
    {
        $this->authorizeManage();
        $this->bootFor($workerId, intent: 'transfer');
    }

    #[On('open-reassign-before-deactivate')]
    public function openForDeactivate(int $workerId): void
    {
        $this->authorizeManage();
        $this->bootFor($workerId, intent: 'deactivate');
    }

    protected function bootFor(int $workerId, string $intent): void
    {
        $worker = SocialWorker::find($workerId);

        if (! $worker) {
            $this->openSystemErrorModal('مددکار مبدأ یافت نشد یا غیرفعال است.');

            return;
        }

        $this->resetTransferState();

        $this->intent = $intent;
        $this->mode = 'all';
        $this->sourceWorkerId = $worker->id;
        $this->sourceWorkerName = trim($worker->full_name) ?: 'بدون نام';
        $this->sourceWorkerCode = $worker->worker_code ? (string) $worker->worker_code : '-';
        $this->sourceGuardians = $this->loadGuardians($worker);
        $this->sourceHouseholdCount = count($this->sourceGuardians);
        $this->open = true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadGuardians(SocialWorker $worker): array
    {
        return $worker->guardians()
            ->select(['id', 'social_worker_id', 'national_code', 'first_name', 'last_name', 'guardian_phone_number'])
            ->withCount('people')
            ->orderBy('id')
            ->get()
            ->map(fn ($guardian) => [
                'id' => (int) $guardian->id,
                'name' => trim(($guardian->first_name ?? '').' '.($guardian->last_name ?? '')) ?: '-',
                'national_code' => $guardian->national_code ?: '-',
                'phone' => $guardian->guardian_phone_number ?: '-',
                'people_count' => (int) $guardian->people_count,
            ])
            ->all();
    }

    public function setMode(string $mode): void
    {
        if ($this->intent === 'deactivate') {
            return; // در غیرفعال‌سازی همیشه همهٔ خانوارها منتقل می‌شوند.
        }

        $this->mode = $mode === 'selective' ? 'selective' : 'all';

        if ($this->mode === 'all') {
            $this->selectedGuardianIds = [];
        }
    }

    public function selectAllGuardians(): void
    {
        $this->selectedGuardianIds = array_map(
            static fn (array $guardian): int => (int) $guardian['id'],
            $this->sourceGuardians,
        );
    }

    public function clearGuardianSelection(): void
    {
        $this->selectedGuardianIds = [];
    }

    // ── انتخابگر مددکار مقصد ──

    public function selectSocialWorker(int $socialWorkerId): void
    {
        $worker = SocialWorker::query()
            ->with('district:id,name')
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'district_id'])
            ->find($socialWorkerId);

        if (! $worker || (int) $worker->id === (int) $this->sourceWorkerId) {
            return;
        }

        $this->socialWorkerId = $worker->id;
        $this->socialWorkerQuery = trim($worker->full_name.' - کد '.$worker->worker_code);
        $this->selectedSocialWorkerCode = $worker->worker_code ? (string) $worker->worker_code : '-';
        $this->selectedSocialWorkerDisplay = $this->formatSelectedSocialWorkerDisplay($worker);
        $this->showSocialWorkerSuggestions = false;
        $this->flushSocialWorkerSuggestions();
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->socialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->selectedSocialWorkerCode = '';
        $this->selectedSocialWorkerDisplay = '';
        $this->showSocialWorkerSuggestions = true;
        $this->flushSocialWorkerSuggestions();
    }

    public function getSocialWorkerSuggestionsProperty(): Collection
    {
        $query = trim($this->socialWorkerQuery);

        if (! $this->showSocialWorkerSuggestions || mb_strlen($query) < 2) {
            return collect();
        }

        $cacheKey = mb_strtolower($query).'|'.(int) $this->socialWorkerId.'|'.(int) $this->sourceWorkerId;

        if ($this->socialWorkerSuggestionsCacheKey === $cacheKey && $this->socialWorkerSuggestionsCache instanceof Collection) {
            return $this->socialWorkerSuggestionsCache;
        }

        $workers = SocialWorker::query()
            ->with('district:id,name')
            ->select(['id', 'first_name', 'last_name', 'worker_code', 'mobile', 'district_id', 'covered_households_count'])
            ->when($this->sourceWorkerId, fn (Builder $q) => $q->whereKeyNot($this->sourceWorkerId))
            ->where(function (Builder $workerQuery) use ($query): void {
                $workerQuery->where('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%')
                    ->orWhere('worker_code', 'like', $query.'%')
                    ->orWhere('mobile', 'like', $query.'%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%'.$query.'%']);
            })
            ->orderBy('worker_code')
            ->limit(10)
            ->get();

        $this->socialWorkerSuggestionsCacheKey = $cacheKey;

        return $this->socialWorkerSuggestionsCache = $workers
            ->map(fn (SocialWorker $worker): array => [
                'id' => (int) $worker->id,
                'duplicate' => false,
                'name' => trim($worker->full_name) ?: 'مددکار بدون نام',
                'code' => $worker->worker_code ? (string) $worker->worker_code : '-',
                'mobile' => $worker->mobile ?: '-',
                'district' => $worker->district?->name ?: 'بدون منطقه',
            ])
            ->values();
    }

    protected function formatSelectedSocialWorkerDisplay(SocialWorker $worker): string
    {
        $name = trim($worker->full_name) ?: 'مددکار بدون نام';
        $district = trim((string) ($worker->district?->name ?? ''));

        return $district !== '' ? $name.' - '.$district : $name;
    }

    protected function flushSocialWorkerSuggestions(): void
    {
        $this->socialWorkerSuggestionsCache = null;
        $this->socialWorkerSuggestionsCacheKey = null;
    }

    // ── تأیید و اجرای انتقال ──

    public function confirm(HouseholdTransferService $service): void
    {
        $this->authorizeManage();

        if (! $this->sourceWorkerId) {
            $this->openSystemErrorModal('مددکار مبدأ مشخص نیست. لطفاً دوباره تلاش کنید.');

            return;
        }

        if (! $this->socialWorkerId) {
            $this->openValidationErrorModal(['لطفاً مددکار مقصد را انتخاب کنید.']);

            return;
        }

        if ((int) $this->socialWorkerId === (int) $this->sourceWorkerId) {
            $this->openValidationErrorModal(['مددکار مقصد باید با مددکار مبدأ متفاوت باشد.']);

            return;
        }

        $source = SocialWorker::find($this->sourceWorkerId);
        $target = SocialWorker::find($this->socialWorkerId);

        if (! $source || ! $target) {
            $this->openSystemErrorModal('مددکار مبدأ یا مقصد یافت نشد یا غیرفعال است.');

            return;
        }

        $isSelective = $this->intent === 'transfer' && $this->mode === 'selective';

        $guardianIds = null;
        if ($isSelective) {
            $guardianIds = array_values(array_map('intval', $this->selectedGuardianIds));

            if ($guardianIds === []) {
                $this->openValidationErrorModal(['برای انتقال انتخابی، حداقل یک خانوار را انتخاب کنید.']);

                return;
            }
        }

        try {
            $moved = $this->intent === 'deactivate'
                ? $service->transferAllAndDeactivate($source, $target)
                : $service->transfer($source, $target, $guardianIds);
        } catch (Throwable $exception) {
            report($exception);
            $this->openSystemErrorModal('انتقال خانوارها ناموفق بود و هیچ تغییری ثبت نشد. لطفاً دوباره تلاش کنید.');

            return;
        }

        $targetName = trim($target->full_name) ?: 'مددکار مقصد';

        $message = $this->intent === 'deactivate'
            ? sprintf('%d خانوار به «%s» منتقل شد و مددکار مبدأ غیرفعال گردید.', $moved, $targetName)
            : sprintf('%d خانوار با موفقیت به «%s» منتقل شد.', $moved, $targetName);

        session()->flash('success', $message);

        $this->closeModal();
        $this->dispatch('households-transferred');
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetTransferState();
    }

    protected function resetTransferState(): void
    {
        $this->intent = 'transfer';
        $this->mode = 'all';
        $this->sourceWorkerId = null;
        $this->sourceWorkerName = '';
        $this->sourceWorkerCode = '';
        $this->sourceHouseholdCount = 0;
        $this->sourceGuardians = [];
        $this->selectedGuardianIds = [];
        $this->clearSocialWorkerSelection();
        $this->showSocialWorkerSuggestions = false;
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->check() && Gate::allows('manage-social-workers'), 403);
    }

    public function render()
    {
        return view('livewire.social-workers.transfer-households', [
            'socialWorkerSuggestions' => $this->showSocialWorkerSuggestions ? $this->socialWorkerSuggestions : collect(),
        ]);
    }
}
