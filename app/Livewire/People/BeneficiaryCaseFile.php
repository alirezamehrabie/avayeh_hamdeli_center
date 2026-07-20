<?php

namespace App\Livewire\People;

use App\Contracts\Ai\GeneratesBeneficiaryCaseAnalysis;
use App\Contracts\Ai\GeneratesFollowUpMessage;
use App\Data\People\BeneficiaryCaseRecordData;
use App\Exceptions\AiCaseAssistantException;
use App\Exports\BeneficiaryCaseFileExport;
use App\Helpers\Morilog\CalendarUtils;
use App\Helpers\Morilog\Jalalian;
use App\Models\BeneficiaryCaseRecord;
use App\Models\DashboardReminder;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\ServiceDelivery;
use App\Queries\People\BeneficiaryCaseFileQuery;
use App\Services\People\BeneficiaryCaseFileTimeline;
use App\Services\People\CreateBeneficiaryCaseRecord;
use App\Services\People\UpdateBeneficiaryCaseRecord;
use App\Services\QrIdentityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BeneficiaryCaseFile extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $selectedPersonId = null;

    public string $recordType = BeneficiaryCaseRecord::TYPE_NOTE;

    public string $recordTitle = '';

    public string $recordDescription = '';

    public string $recordedAt = '';

    public ?string $recordAmount = null;

    public string $recordReferenceNumber = '';

    public ?int $editingCaseRecordId = null;

    public string $editRecordType = BeneficiaryCaseRecord::TYPE_NOTE;

    public string $editRecordTitle = '';

    public string $editRecordDescription = '';

    public string $editRecordedAt = '';

    public ?string $editRecordAmount = null;

    public string $editRecordReferenceNumber = '';

    #[Validate(['recordAttachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096'])]
    public array $recordAttachments = [];

    #[Validate(['editRecordAttachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096'])]
    public array $editRecordAttachments = [];

    public array $editRemovedAttachmentIds = [];

    public bool $editAttachmentRemovalConfirmed = false;

    public ?string $aiCaseSummary = null;

    public array $aiReminderSuggestions = [];

    public string $aiAssistantError = '';

    public ?string $aiGeneratedAt = null;

    public string $followUpRecipient = 'beneficiary';

    public string $followUpChannel = 'sms';

    public string $followUpPurpose = 'case_follow_up';

    public string $followUpTone = 'respectful';

    public string $followUpDetails = '';

    public string $followUpDraft = '';

    public string $followUpReviewNote = '';

    public string $followUpError = '';

    public ?string $followUpGeneratedAt = null;

    protected ?Collection $searchResultsCache = null;

    protected ?Person $selectedPersonCache = null;

    protected bool $selectedPersonCacheLoaded = false;

    protected ?Collection $serviceDeliveriesCache = null;

    protected ?Collection $activityAttendancesCache = null;

    protected ?Collection $caseRecordsCache = null;

    protected ?Collection $timelineCache = null;

    protected ?array $caseFileTotalsCache = null;

    public function mount(?int $personId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
        $this->recordedAt = Jalalian::now()->format('Y/m/d');
    }

    public function updatedSearch(): void
    {
        $this->search = Person::normalizeSearchText($this->search);
        $this->searchResultsCache = null;
    }

    public function updatedFollowUpRecipient(): void
    {
        $this->resetFollowUpDraft();
    }

    public function updatedFollowUpChannel(): void
    {
        $this->resetFollowUpDraft();
    }

    public function updatedFollowUpPurpose(): void
    {
        $this->resetFollowUpDraft();
    }

    public function updatedFollowUpTone(): void
    {
        $this->resetFollowUpDraft();
    }

    public function updatedFollowUpDetails(): void
    {
        $this->resetFollowUpDraft();
    }

    public function selectPerson(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedPersonId = $personId;
        $this->resetLoadedCaseFileData();
        $this->resetEditRecordForm();
        $this->resetAiAnalysis();
        $this->resetFollowUpMessage();
    }

    public function clearSelection(): void
    {
        $this->selectedPersonId = null;
        $this->resetLoadedCaseFileData();
        $this->resetRecordForm();
        $this->resetEditRecordForm();
        $this->resetAiAnalysis();
        $this->resetFollowUpMessage();
    }

    public function resolveScannedBeneficiaryQr(string $payload): array
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $token = $this->extractQrToken(trim((string) $payload));

        if (! $token) {
            return $this->beneficiaryQrScanResponse(false, 'QR خوانده‌شده معتبر نیست یا به این سامانه تعلق ندارد.');
        }

        $identity = app(QrIdentityService::class)->resolveToken($token, 'admin-beneficiary-case-file');

        if (! $identity) {
            $identity = $this->resolvePublicCode($token);
        }

        if (! $identity || $identity->subject_type !== QrIdentity::SUBJECT_PERSON) {
            return $this->beneficiaryQrScanResponse(
                false,
                $identity ? 'این کد QR برای این بخش قابل استفاده نیست.' : 'QR نامعتبر، ابطال‌شده یا غیرقابل دسترس است.'
            );
        }

        $person = app(BeneficiaryCaseFileQuery::class)
            ->findQrPerson((int) $identity->subject_id);

        if (! $person) {
            return $this->beneficiaryQrScanResponse(false, 'اطلاعات مددجو برای این QR پیدا نشد.');
        }

        $this->selectPerson((int) $person->id);
        $this->search = '';
        $this->searchResultsCache = collect();

        return $this->beneficiaryQrScanResponse(
            true,
            'مددجو شناسایی شد.',
            (string) ($person->full_name ?: trim($person->first_name.' '.$person->last_name))
        );
    }

    public function exportToExcel()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $person = $this->selectedPerson;

        if (! $person) {
            session()->flash('case-record-error', 'ابتدا یک مددجو را انتخاب کنید.');

            return null;
        }

        $timeline = $this->timeline;

        if ($timeline->isEmpty()) {
            session()->flash('case-record-error', 'رکوردی برای خروجی گرفتن یافت نشد.');

            return null;
        }

        $fileName = 'پرونده-مددجو-'.($person->person_code ?: $person->id).'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        return Excel::download(new BeneficiaryCaseFileExport($person, $timeline), $fileName);
    }

    public function saveCaseRecord(CreateBeneficiaryCaseRecord $action): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless((bool) $this->selectedPerson, 404);

        $validated = $this->validate($this->createRecordRules(), [], $this->recordValidationAttributes());

        $action->create(
            $this->selectedPerson,
            (int) auth()->id(),
            new BeneficiaryCaseRecordData(
                recordType: $validated['recordType'],
                title: $validated['recordTitle'],
                description: $validated['recordDescription'] ?? null,
                recordedAt: filled($validated['recordedAt']) ? $this->jalaliToGregorian($validated['recordedAt']) : null,
                amount: filled($validated['recordAmount']) ? (int) $validated['recordAmount'] : null,
                referenceNumber: $validated['recordReferenceNumber'] ?? null,
            ),
            $this->recordAttachments,
        );

        $this->resetLoadedCaseFileData();
        $this->resetRecordForm();
        $this->resetAiAnalysis();
        session()->flash('case-record-success', 'رکورد پرونده با موفقیت ثبت شد.');
    }

    public function startEditingCaseRecord(int $recordId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless((bool) $this->selectedPerson, 404);

        $record = $this->resolveEditableCaseRecord($recordId);

        $this->editingCaseRecordId = $record->id;
        $this->editRecordType = (string) $record->record_type;
        $this->editRecordTitle = (string) $record->title;
        $this->editRecordDescription = (string) ($record->description ?? '');
        $this->editRecordedAt = $record->recorded_at
            ? Jalalian::fromDateTime($record->recorded_at)->format('Y/m/d')
            : '';
        $this->editRecordAmount = $record->amount !== null ? (string) $record->amount : null;
        $this->editRecordReferenceNumber = (string) ($record->reference_number ?? '');

        $this->resetValidation([
            'editRecordType',
            'editRecordTitle',
            'editRecordDescription',
            'editRecordedAt',
            'editRecordAmount',
            'editRecordReferenceNumber',
        ]);
    }

    public function cancelEditingCaseRecord(): void
    {
        $this->resetEditRecordForm();
    }

    public function markEditAttachmentForRemoval(int $attachmentId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless($this->editingCaseRecordId !== null, 404);

        $attachment = app(BeneficiaryCaseFileQuery::class)->findAttachmentForRecordOrFail(
            $this->editingCaseRecordId,
            $attachmentId,
        );

        if (! in_array($attachment->id, $this->editRemovedAttachmentIds, true)) {
            $this->editRemovedAttachmentIds[] = (int) $attachment->id;
        }

        $this->editAttachmentRemovalConfirmed = false;
    }

    public function unmarkEditAttachmentForRemoval(int $attachmentId): void
    {
        $this->editRemovedAttachmentIds = array_values(array_filter(
            $this->editRemovedAttachmentIds,
            fn (int $id): bool => $id !== $attachmentId
        ));

        if ($this->editRemovedAttachmentIds === []) {
            $this->editAttachmentRemovalConfirmed = false;
        }
    }

    public function confirmEditAttachmentRemoval(): void
    {
        if ($this->editRemovedAttachmentIds === []) {
            $this->editAttachmentRemovalConfirmed = false;

            return;
        }

        $this->editAttachmentRemovalConfirmed = true;
    }

    public function cancelEditAttachmentRemovalConfirmation(): void
    {
        $this->editAttachmentRemovalConfirmed = false;
    }

    public function removeEditPendingAttachment(int $index): void
    {
        if (! array_key_exists($index, $this->editRecordAttachments)) {
            return;
        }

        unset($this->editRecordAttachments[$index]);
        $this->editRecordAttachments = array_values($this->editRecordAttachments);
    }

    public function updateCaseRecord(UpdateBeneficiaryCaseRecord $action): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless((bool) $this->selectedPerson, 404);
        abort_unless($this->editingCaseRecordId !== null, 404);

        $record = $this->resolveEditableCaseRecord($this->editingCaseRecordId);
        $validated = $this->validate($this->editRecordRules(), [], $this->recordValidationAttributes());

        if ($this->editRemovedAttachmentIds !== [] && ! $this->editAttachmentRemovalConfirmed) {
            $this->addError('editRemovedAttachmentIds', 'برای حذف نهایی پیوست‌ها، ابتدا تایید حذف را فعال کنید.');

            return;
        }

        $action->update(
            $record,
            new BeneficiaryCaseRecordData(
                recordType: $validated['editRecordType'],
                title: $validated['editRecordTitle'],
                description: $validated['editRecordDescription'] ?? null,
                recordedAt: filled($validated['editRecordedAt']) ? $this->jalaliToGregorian($validated['editRecordedAt']) : null,
                amount: filled($validated['editRecordAmount']) ? (int) $validated['editRecordAmount'] : null,
                referenceNumber: $validated['editRecordReferenceNumber'] ?? null,
            ),
            $this->editRecordAttachments,
            $this->editRemovedAttachmentIds,
            (int) auth()->id(),
        );

        $this->resetLoadedCaseFileData();
        $this->resetEditRecordForm();
        $this->resetAiAnalysis();
        session()->flash('case-record-edit-success', 'رکورد پرونده به‌روزرسانی شد.');
    }

    public function generateAiCaseAnalysis(GeneratesBeneficiaryCaseAnalysis $assistant): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $person = $this->selectedPerson;
        abort_unless((bool) $person, 404);

        $this->resetAiAnalysis();

        try {
            $analysis = $assistant->generate(
                $person,
                $this->serviceDeliveries,
                $this->activityAttendances,
                $this->caseRecords,
                $this->caseFileTotals,
            );
        } catch (AiCaseAssistantException $exception) {
            report($exception);
            $this->aiAssistantError = 'سرویس هوش مصنوعی در حال حاضر پاسخ‌گو نیست. پیکربندی سرویس را بررسی کنید یا دوباره تلاش کنید.';

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->aiAssistantError = 'تحلیل پرونده انجام نشد. لطفا دوباره تلاش کنید.';

            return;
        }

        $this->aiCaseSummary = trim($analysis['summary']);
        $this->aiReminderSuggestions = collect($analysis['reminders'])
            ->map(fn (array $reminder): array => [
                'title' => $reminder['title'],
                'category' => $reminder['category'],
                'selected' => false,
            ])
            ->values()
            ->all();
        $this->aiGeneratedAt = Jalalian::now()->format('Y/m/d H:i');
    }

    public function generateFollowUpMessage(GeneratesFollowUpMessage $generator): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $person = $this->selectedPerson;
        abort_unless((bool) $person, 404);

        $validated = $this->validate([
            'followUpRecipient' => ['required', Rule::in([
                'beneficiary',
                'guardian',
                'social_worker',
                'sponsor',
                'other',
            ])],
            'followUpChannel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'followUpPurpose' => ['required', Rule::in([
                'appointment_reminder',
                'document_request',
                'case_follow_up',
                'service_notification',
                'payment_reminder',
                'custom',
            ])],
            'followUpTone' => ['required', Rule::in(['respectful', 'warm', 'formal'])],
            'followUpDetails' => ['required', 'string', 'min:5', 'max:1200'],
        ], [], [
            'followUpRecipient' => 'گیرنده',
            'followUpChannel' => 'کانال پیام',
            'followUpPurpose' => 'هدف پیام',
            'followUpTone' => 'لحن پیام',
            'followUpDetails' => 'جزئیات پیام',
        ]);

        $this->resetFollowUpDraft();

        try {
            $draft = $generator->generate(
                $person,
                $validated['followUpRecipient'],
                $validated['followUpChannel'],
                $validated['followUpPurpose'],
                $validated['followUpTone'],
                $validated['followUpDetails'],
            );
        } catch (AiCaseAssistantException $exception) {
            report($exception);
            $this->followUpError = 'سرویس هوش مصنوعی در حال حاضر پاسخ‌گو نیست. پیکربندی سرویس را بررسی کنید یا دوباره تلاش کنید.';

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->followUpError = 'پیش‌نویس پیام ایجاد نشد. لطفا دوباره تلاش کنید.';

            return;
        }

        $this->followUpReviewNote = trim($draft['review_note']);

        if (! $draft['can_generate']) {
            $this->followUpError = $this->followUpReviewNote !== ''
                ? $this->followUpReviewNote
                : 'برای ایجاد پیام، جزئیات دقیق‌تر و قابل بررسی وارد کنید.';

            return;
        }

        $this->followUpDraft = trim($draft['message']);
        $this->followUpGeneratedAt = Jalalian::now()->format('Y/m/d H:i');
    }

    public function saveSelectedAiReminders(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless((bool) $this->selectedPerson, 404);

        $allowedCategories = ['today_tasks', 'pending_approvals', 'contract_deadlines', 'required_reports'];
        $selectedIndexes = collect($this->aiReminderSuggestions)
            ->keys()
            ->filter(fn (int|string $index): bool => (bool) ($this->aiReminderSuggestions[$index]['selected'] ?? false))
            ->values();

        if ($selectedIndexes->isEmpty()) {
            $this->addError('aiReminderSuggestions', 'حداقل یک یادآوری پیشنهادی را انتخاب کنید.');

            return;
        }

        $savedIndexes = [];

        DB::transaction(function () use ($selectedIndexes, $allowedCategories, &$savedIndexes): void {
            foreach ($selectedIndexes as $index) {
                $suggestion = $this->aiReminderSuggestions[$index] ?? [];
                $title = mb_substr(trim((string) ($suggestion['title'] ?? '')), 0, 255);
                $category = (string) ($suggestion['category'] ?? '');

                if ($title === '' || ! in_array($category, $allowedCategories, true)) {
                    continue;
                }

                DashboardReminder::query()->create([
                    'user_id' => auth()->id(),
                    'title' => $title,
                    'category' => $category,
                    'is_done' => false,
                ]);

                $savedIndexes[] = (int) $index;
            }
        });

        if ($savedIndexes === []) {
            $this->addError('aiReminderSuggestions', 'یادآوری معتبری برای ذخیره وجود ندارد.');

            return;
        }

        $this->aiReminderSuggestions = collect($this->aiReminderSuggestions)
            ->reject(fn (array $suggestion, int $index): bool => in_array($index, $savedIndexes, true))
            ->values()
            ->all();
        $this->resetValidation('aiReminderSuggestions');
        session()->flash('ai-reminder-success', number_format(count($savedIndexes)).' یادآوری با تایید شما ذخیره شد.');
    }

    public function getSearchResultsProperty(): Collection
    {
        if ($this->searchResultsCache !== null) {
            return $this->searchResultsCache;
        }

        return $this->searchResultsCache = app(BeneficiaryCaseFileQuery::class)
            ->searchPeople($this->search);
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if ($this->selectedPersonCacheLoaded) {
            return $this->selectedPersonCache;
        }

        $this->selectedPersonCacheLoaded = true;

        return $this->selectedPersonCache = app(BeneficiaryCaseFileQuery::class)
            ->findPerson($this->selectedPersonId);
    }

    public function getServiceDeliveriesProperty(): Collection
    {
        if ($this->serviceDeliveriesCache !== null) {
            return $this->serviceDeliveriesCache;
        }

        $person = $this->selectedPerson;

        if (! $person) {
            return $this->serviceDeliveriesCache = collect();
        }

        return $this->serviceDeliveriesCache = app(BeneficiaryCaseFileQuery::class)
            ->serviceDeliveries($person);
    }

    public function getDirectServiceDeliveriesProperty(): Collection
    {
        if (! $this->selectedPersonId) {
            return collect();
        }

        return $this->serviceDeliveries
            ->filter(fn (ServiceDelivery $delivery): bool => (int) $delivery->person_id === (int) $this->selectedPersonId)
            ->values();
    }

    public function getFamilyServiceDeliveriesProperty(): Collection
    {
        return $this->serviceDeliveries
            ->filter(fn (ServiceDelivery $delivery): bool => $this->isFamilyServiceDelivery($delivery))
            ->values();
    }

    public function getCaseFileTotalsProperty(): array
    {
        if ($this->caseFileTotalsCache !== null) {
            return $this->caseFileTotalsCache;
        }

        return $this->caseFileTotalsCache = app(BeneficiaryCaseFileQuery::class)
            ->totals($this->selectedPerson);
    }

    public function getActivityAttendancesProperty(): Collection
    {
        if ($this->activityAttendancesCache !== null) {
            return $this->activityAttendancesCache;
        }

        return $this->activityAttendancesCache = app(BeneficiaryCaseFileQuery::class)
            ->activityAttendances($this->selectedPersonId);
    }

    public function getCaseRecordsProperty(): Collection
    {
        if ($this->caseRecordsCache !== null) {
            return $this->caseRecordsCache;
        }

        return $this->caseRecordsCache = app(BeneficiaryCaseFileQuery::class)
            ->caseRecords($this->selectedPersonId);
    }

    public function getTimelineProperty(): Collection
    {
        if ($this->timelineCache !== null) {
            return $this->timelineCache;
        }

        return $this->timelineCache = app(BeneficiaryCaseFileTimeline::class)->build(
            $this->selectedPerson,
            $this->serviceDeliveries,
            $this->activityAttendances,
            $this->caseRecords,
        );
    }

    public function getEditingCaseRecordProperty(): ?BeneficiaryCaseRecord
    {
        return app(BeneficiaryCaseFileQuery::class)->findEditingRecord(
            $this->selectedPersonId,
            $this->editingCaseRecordId,
        );
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return 'ثبت نشده';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }

    public function formatDateTime($date): string
    {
        if (! $date) {
            return 'ثبت نشده';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d H:i');
    }

    protected function isFamilyServiceDelivery(ServiceDelivery $delivery): bool
    {
        if (! $this->selectedPersonId) {
            return false;
        }

        $selectedPersonId = (int) $this->selectedPersonId;

        if ((int) $delivery->person_id === $selectedPersonId) {
            return false;
        }

        $selectedGuardianId = (int) ($this->selectedPerson?->guardian_id ?? 0);

        return $selectedGuardianId > 0 && (int) $delivery->guardian_id === $selectedGuardianId;
    }

    protected function resetRecordForm(): void
    {
        $this->recordType = BeneficiaryCaseRecord::TYPE_NOTE;
        $this->recordTitle = '';
        $this->recordDescription = '';
        $this->recordedAt = Jalalian::now()->format('Y/m/d');
        $this->recordAmount = null;
        $this->recordReferenceNumber = '';
        $this->recordAttachments = [];
        $this->resetValidation([
            'recordType',
            'recordTitle',
            'recordDescription',
            'recordedAt',
            'recordAmount',
            'recordReferenceNumber',
            'recordAttachments',
            'recordAttachments.*',
        ]);
    }

    protected function resetEditRecordForm(): void
    {
        $this->editingCaseRecordId = null;
        $this->editRecordType = BeneficiaryCaseRecord::TYPE_NOTE;
        $this->editRecordTitle = '';
        $this->editRecordDescription = '';
        $this->editRecordedAt = '';
        $this->editRecordAmount = null;
        $this->editRecordReferenceNumber = '';
        $this->editRecordAttachments = [];
        $this->editRemovedAttachmentIds = [];
        $this->editAttachmentRemovalConfirmed = false;
        $this->resetValidation([
            'editRecordType',
            'editRecordTitle',
            'editRecordDescription',
            'editRecordedAt',
            'editRecordAmount',
            'editRecordReferenceNumber',
            'editRecordAttachments',
            'editRecordAttachments.*',
            'editRemovedAttachmentIds',
        ]);
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', $this->normalizeJalaliDate($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->normalizeJalaliDate($date))->toCarbon()->toDateString();
    }

    protected function normalizeJalaliDate(?string $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        return strtr((string) str($normalized)->before(' ')->trim(), [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    protected function resetLoadedCaseFileData(): void
    {
        $this->selectedPersonCache = null;
        $this->selectedPersonCacheLoaded = false;
        $this->serviceDeliveriesCache = null;
        $this->activityAttendancesCache = null;
        $this->caseRecordsCache = null;
        $this->timelineCache = null;
        $this->caseFileTotalsCache = null;
    }

    protected function resetAiAnalysis(): void
    {
        $this->aiCaseSummary = null;
        $this->aiReminderSuggestions = [];
        $this->aiAssistantError = '';
        $this->aiGeneratedAt = null;
        $this->resetValidation('aiReminderSuggestions');
    }

    protected function resetFollowUpMessage(): void
    {
        $this->followUpRecipient = 'beneficiary';
        $this->followUpChannel = 'sms';
        $this->followUpPurpose = 'case_follow_up';
        $this->followUpTone = 'respectful';
        $this->followUpDetails = '';
        $this->resetFollowUpDraft();
        $this->resetValidation([
            'followUpRecipient',
            'followUpChannel',
            'followUpPurpose',
            'followUpTone',
            'followUpDetails',
        ]);
    }

    protected function resetFollowUpDraft(): void
    {
        $this->followUpDraft = '';
        $this->followUpReviewNote = '';
        $this->followUpError = '';
        $this->followUpGeneratedAt = null;
    }

    protected function createRecordRules(): array
    {
        return [
            'recordType' => ['required', Rule::in(array_keys(BeneficiaryCaseRecord::TYPE_OPTIONS))],
            'recordTitle' => ['required', 'string', 'max:255'],
            'recordDescription' => ['nullable', 'string', 'max:5000'],
            'recordedAt' => $this->jalaliDateRule(),
            'recordAmount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'recordReferenceNumber' => ['nullable', 'string', 'max:255'],
            'recordAttachments' => ['array', 'max:5'],
            'recordAttachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    protected function editRecordRules(): array
    {
        return [
            'editRecordType' => ['required', Rule::in(array_keys(BeneficiaryCaseRecord::TYPE_OPTIONS))],
            'editRecordTitle' => ['required', 'string', 'max:255'],
            'editRecordDescription' => ['nullable', 'string', 'max:5000'],
            'editRecordedAt' => $this->jalaliDateRule(),
            'editRecordAmount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'editRecordReferenceNumber' => ['nullable', 'string', 'max:255'],
            'editRecordAttachments' => [
                'array',
                'max:5',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existingCount = app(BeneficiaryCaseFileQuery::class)
                        ->attachmentCount($this->editingCaseRecordId);

                    $remainingCount = max(0, $existingCount - count($this->editRemovedAttachmentIds));
                    $newCount = count((array) $value);

                    if (($remainingCount + $newCount) > 5) {
                        $fail('هر رکورد حداکثر می‌تواند ۵ پیوست داشته باشد.');
                    }
                },
            ],
            'editRecordAttachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    protected function recordValidationAttributes(): array
    {
        return [
            'recordType' => 'نوع رکورد',
            'recordTitle' => 'عنوان',
            'recordDescription' => 'توضیحات',
            'recordedAt' => 'تاریخ',
            'recordAmount' => 'مبلغ',
            'recordReferenceNumber' => 'شماره مرجع',
            'recordAttachments' => 'پیوست‌ها',
            'recordAttachments.*' => 'پیوست',
            'editRecordType' => 'نوع رکورد',
            'editRecordTitle' => 'عنوان',
            'editRecordDescription' => 'توضیحات',
            'editRecordedAt' => 'تاریخ',
            'editRecordAmount' => 'مبلغ',
            'editRecordReferenceNumber' => 'شماره مرجع',
            'editRecordAttachments' => 'پیوست‌های جدید',
            'editRecordAttachments.*' => 'پیوست جدید',
        ];
    }

    protected function jalaliDateRule(): array
    {
        return [
            'nullable',
            'string',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! $this->isValidJalaliDate((string) $value)) {
                    $fail('تاریخ شمسی معتبر نیست.');
                }
            },
        ];
    }

    protected function resolveEditableCaseRecord(int $recordId): BeneficiaryCaseRecord
    {
        return app(BeneficiaryCaseFileQuery::class)
            ->findEditableRecordOrFail($this->selectedPersonId, $recordId);
    }

    protected function beneficiaryQrScanResponse(bool $ok, string $message, string $name = ''): array
    {
        return [
            'ok' => $ok,
            'status' => $ok ? 'paused' : 'scan_error',
            'message' => $message,
            'result' => [
                'ok' => $ok,
                'code' => $ok ? 'resolved' : 'invalid_qr',
                'message' => $message,
                'name' => $name,
            ],
        ];
    }

    protected function resolvePublicCode(string $token): ?QrIdentity
    {
        $identity = QrIdentity::query()
            ->where('public_code', strtoupper(trim($token)))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();

        if (! $identity) {
            return null;
        }

        $subject = $identity->subject;

        if (! $subject || method_exists($subject, 'trashed') && $subject->trashed()) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity->setRelation('subject', $subject);
    }

    protected function extractQrToken(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\\/qr\\/r\\/([^\\/\\?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.beneficiary-case-file', [
            'recordTypeOptions' => BeneficiaryCaseRecord::TYPE_OPTIONS,
        ]);
    }
}
