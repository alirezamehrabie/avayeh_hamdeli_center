<?php

namespace App\Livewire\SocialWorkers;

use AllowDynamicProperties;
use App\Helpers\Morilog\Jalalian;
use App\Helpers\PersianNumber;
use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\SocialWorker;
use App\Services\Announcements\CreateMandatoryAnnouncementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[AllowDynamicProperties]
#[Layout('layouts.admin')]
class MandatoryAnnouncementsManager extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public string $activeTab = 'list'; // 'list' | 'create'

    // Form fields
    public string $title = '';

    public string $body = '';

    public string $priority = MandatoryAnnouncement::PRIORITY_IMPORTANT;

    public string $targetType = MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS;

    /** @var array<int> */
    public array $selectedSocialWorkerIds = [];

    public bool $isBlocking = true;

    public int $minReadingSeconds = 5;

    // Filters for list
    public string $search = '';

    public string $priorityFilter = 'all';

    // Social workers picker search in creation form
    public string $workerSearch = '';

    // Audit Log Modal state
    public bool $showAuditModal = false;

    public ?int $selectedAnnouncementIdForAudit = null;

    public string $auditSearch = '';

    public string $auditStatusFilter = 'all'; // 'all' | 'read' | 'unread'

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorizeAdminAccess();
    }

    protected function authorizeAdminAccess(): void
    {
        abort_unless(
            auth()->check() && (
                auth()->user()->hasFullAccess()
                || auth()->user()->can('manage-social-workers')
                || auth()->user()->can('access-admin-panel')
            ),
            403,
            'شما اجازه دسترسی به مدیریت پیام‌های مددکاران را ندارید.'
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['list', 'create'], true)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'body' => ['required', 'string', 'min:5'],
            'priority' => [
                'required',
                Rule::in([
                    MandatoryAnnouncement::PRIORITY_NORMAL,
                    MandatoryAnnouncement::PRIORITY_IMPORTANT,
                    MandatoryAnnouncement::PRIORITY_URGENT,
                ]),
            ],
            'targetType' => [
                'required',
                Rule::in([
                    MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS,
                    MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS,
                ]),
            ],
            'selectedSocialWorkerIds' => [
                'array',
                Rule::requiredIf($this->targetType === MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS),
                function ($attribute, $value, $fail) {
                    if ($this->targetType === MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS && empty($value)) {
                        $fail('حداقل باید یک مددکار اجتماعی برای دریافت پیام انتخاب شود.');
                    }
                },
            ],
            'selectedSocialWorkerIds.*' => ['integer', 'exists:social_workers,id'],
            'isBlocking' => ['boolean'],
            'minReadingSeconds' => ['integer', 'min:0', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => 'عنوان پیام',
            'body' => 'متن پیام',
            'priority' => 'سطح اولویت',
            'targetType' => 'دامنه مخاطبان',
            'selectedSocialWorkerIds' => 'مددکاران منتخب',
            'isBlocking' => 'الزام مسدودکننده',
            'minReadingSeconds' => 'حداقل ثانیه مطالعه الزامی',
        ];
    }

    public function saveAnnouncement(CreateMandatoryAnnouncementService $service): void
    {
        $this->authorizeAdminAccess();

        $validated = $this->validate();

        $service->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'target_type' => $validated['targetType'],
            'social_worker_ids' => $this->targetType === MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS
                ? $this->selectedSocialWorkerIds
                : [],
            'is_blocking' => $validated['isBlocking'],
            'min_reading_seconds' => $validated['minReadingSeconds'],
        ], auth()->user());

        $this->reset([
            'title',
            'body',
            'selectedSocialWorkerIds',
            'workerSearch',
        ]);
        $this->priority = MandatoryAnnouncement::PRIORITY_IMPORTANT;
        $this->targetType = MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS;
        $this->isBlocking = true;
        $this->minReadingSeconds = 5;

        $this->activeTab = 'list';
        session()->flash('success', 'پیام با موفقیت ثبت و به حساب مددکاران مربوطه ابلاغ شد.');
    }

    public function deleteAnnouncement(int $announcementId): void
    {
        $this->authorizeAdminAccess();

        $announcement = MandatoryAnnouncement::query()->findOrFail($announcementId);

        // Delete recipients and announcement
        $announcement->recipients()->delete();
        $announcement->delete();

        if ($this->selectedAnnouncementIdForAudit === $announcementId) {
            $this->closeAuditModal();
        }

        session()->flash('success', 'پیام مورد نظر با موفقیت حذف گردید.');
    }

    public function openAuditModal(int $announcementId): void
    {
        $this->selectedAnnouncementIdForAudit = $announcementId;
        $this->auditSearch = '';
        $this->auditStatusFilter = 'all';
        $this->showAuditModal = true;
    }

    public function closeAuditModal(): void
    {
        $this->showAuditModal = false;
        $this->selectedAnnouncementIdForAudit = null;
        $this->auditSearch = '';
        $this->auditStatusFilter = 'all';
    }

    public function selectAllSocialWorkers(): void
    {
        $allIds = SocialWorker::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->selectedSocialWorkerIds = $allIds;
    }

    public function deselectAllSocialWorkers(): void
    {
        $this->selectedSocialWorkerIds = [];
    }

    public function toggleSocialWorkerSelection(int $workerId): void
    {
        if (in_array($workerId, $this->selectedSocialWorkerIds, true)) {
            $this->selectedSocialWorkerIds = array_values(
                array_filter($this->selectedSocialWorkerIds, fn ($id) => $id !== $workerId)
            );
        } else {
            $this->selectedSocialWorkerIds[] = $workerId;
        }
    }

    public function formatJalaliDate(?\DateTimeInterface $date, string $format = 'Y/m/d - H:i'): string
    {
        if (! $date) {
            return '—';
        }

        try {
            $formatted = Jalalian::fromDateTime($date)->format($format);

            return PersianNumber::convert($formatted);
        } catch (\Throwable) {
            return '—';
        }
    }

    public function render(): View
    {
        $announcements = MandatoryAnnouncement::query()
            ->with(['creator'])
            ->withCount(['recipients', 'readRecipients'])
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhere('body', 'like', "%{$this->search}%");
                });
            })
            ->when($this->priorityFilter !== 'all', function (Builder $query) {
                $query->where('priority', $this->priorityFilter);
            })
            ->latest('id')
            ->paginate(10);

        $availableSocialWorkers = collect();
        if ($this->activeTab === 'create' && $this->targetType === MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS) {
            $availableSocialWorkers = SocialWorker::query()
                ->where('is_active', true)
                ->when($this->workerSearch !== '', function (Builder $query) {
                    $query->where(function (Builder $sub) {
                        $sub->where('first_name', 'like', "%{$this->workerSearch}%")
                            ->orWhere('last_name', 'like', "%{$this->workerSearch}%")
                            ->orWhere('worker_code', 'like', "%{$this->workerSearch}%");
                    });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'worker_code', 'mobile']);
        }

        $auditAnnouncement = null;
        $auditRecipients = collect();
        if ($this->showAuditModal && $this->selectedAnnouncementIdForAudit) {
            $auditAnnouncement = MandatoryAnnouncement::query()
                ->with(['creator'])
                ->withCount(['recipients', 'readRecipients'])
                ->find($this->selectedAnnouncementIdForAudit);

            if ($auditAnnouncement) {
                $auditRecipients = MandatoryAnnouncementRecipient::query()
                    ->with(['user', 'socialWorker'])
                    ->where('announcement_id', $auditAnnouncement->id)
                    ->when($this->auditStatusFilter === 'read', fn (Builder $q) => $q->whereNotNull('read_at'))
                    ->when($this->auditStatusFilter === 'unread', fn (Builder $q) => $q->whereNull('read_at'))
                    ->when($this->auditSearch !== '', function (Builder $query) {
                        $query->where(function (Builder $sub) {
                            $sub->whereHas('socialWorker', function (Builder $sw) {
                                $sw->where('first_name', 'like', "%{$this->auditSearch}%")
                                    ->orWhere('last_name', 'like', "%{$this->auditSearch}%")
                                    ->orWhere('worker_code', 'like', "%{$this->auditSearch}%");
                            })
                                ->orWhereHas('user', function (Builder $u) {
                                    $u->where('name', 'like', "%{$this->auditSearch}%")
                                        ->orWhere('email', 'like', "%{$this->auditSearch}%");
                                });
                        });
                    })
                    ->orderByRaw('CASE WHEN read_at IS NOT NULL THEN 0 ELSE 1 END')
                    ->latest('read_at')
                    ->get();
            }
        }

        return view('livewire.social-workers.mandatory-announcements-manager', [
            'announcements' => $announcements,
            'availableSocialWorkers' => $availableSocialWorkers,
            'auditAnnouncement' => $auditAnnouncement,
            'auditRecipients' => $auditRecipients,
        ]);
    }
}
