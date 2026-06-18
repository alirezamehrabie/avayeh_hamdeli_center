<?php

namespace App\Livewire\Shared;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Livewire\Attributes\On;
use Livewire\Component;

class QrIdentityModal extends Component
{
    public bool $showQrModal = false;

    public ?int $qrSubjectId = null;

    public string $qrSubjectType = '';

    public ?string $subjectName = null;

    public ?string $subjectLabel = null;

    public ?string $subjectCode = null;

    public ?string $publicCode = null;

    public ?string $scanUrl = null;

    public ?string $qrMarkup = null;

    public ?string $issuedQrToken = null;

    public bool $confirmingQrLifecycleAction = false;

    public string $qrLifecycleAction = '';

    public string $qrLifecycleReason = '';

    #[On('open-qr-identity-modal')]
    public function open(string $subjectType, int $subjectId): void
    {
        abort_unless(in_array($subjectType, [QrIdentity::SUBJECT_PERSON, QrIdentity::SUBJECT_GUARDIAN], true), 422);
        abort_unless(auth()->check() && auth()->user()->can($this->openPermission($subjectType)), 403);

        $subject = $this->resolveSubject($subjectType, $subjectId);
        app(QrIdentityService::class)->ensureActiveFor($subject, auth()->id());

        $this->qrSubjectType = $subjectType;
        $this->qrSubjectId = $subject->id;
        $this->subjectName = $this->subjectName($subject);
        $this->subjectLabel = $subjectType === QrIdentity::SUBJECT_GUARDIAN ? 'سرپرست' : 'مددجو';
        $this->subjectCode = $subjectType === QrIdentity::SUBJECT_GUARDIAN
            ? (string) ($subject->guardian_code ?? '-')
            : (string) ($subject->person_code ?? '-');
        $this->issuedQrToken = null;
        $this->confirmingQrLifecycleAction = false;
        $this->qrLifecycleAction = '';
        $this->qrLifecycleReason = '';
        $this->showQrModal = true;

        $this->refreshQrIdentityState();
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrSubjectId = null;
        $this->qrSubjectType = '';
        $this->subjectName = null;
        $this->subjectLabel = null;
        $this->subjectCode = null;
        $this->publicCode = null;
        $this->scanUrl = null;
        $this->qrMarkup = null;
        $this->issuedQrToken = null;
        $this->confirmingQrLifecycleAction = false;
        $this->qrLifecycleAction = '';
        $this->qrLifecycleReason = '';
        $this->resetValidation(['qrLifecycleReason']);
    }

    public function requestQrLifecycleAction(string $action): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(in_array($action, ['reissue', 'revoke'], true), 422);

        $this->qrLifecycleAction = $action;
        $this->qrLifecycleReason = '';
        $this->confirmingQrLifecycleAction = true;
        $this->resetValidation(['qrLifecycleReason']);
    }

    public function cancelQrLifecycleAction(): void
    {
        $this->confirmingQrLifecycleAction = false;
        $this->qrLifecycleAction = '';
        $this->qrLifecycleReason = '';
        $this->resetValidation(['qrLifecycleReason']);
    }

    public function confirmQrLifecycleAction(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(in_array($this->qrLifecycleAction, ['reissue', 'revoke'], true), 422);
        abort_unless($this->qrSubjectId && $this->qrSubjectType, 422);

        $validated = $this->validate([
            'qrLifecycleReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'qrLifecycleReason.required' => 'ثبت علت برای تغییر وضعیت QR الزامی است.',
            'qrLifecycleReason.min' => 'علت تغییر وضعیت QR باید حداقل ۱۰ کاراکتر باشد.',
        ]);

        $reason = trim($validated['qrLifecycleReason']);

        if ($this->qrLifecycleAction === 'reissue') {
            $subject = $this->resolveSubject($this->qrSubjectType, $this->qrSubjectId);
            $issued = app(QrIdentityService::class)->replaceFor($subject, auth()->id(), $reason);
            $this->issuedQrToken = $issued['token'];
            session()->flash('success', "QR {$this->subjectLabel} با ثبت علت، دوباره صادر شد.");
        } else {
            $identity = $this->activeQrIdentity();

            if ($identity) {
                app(QrIdentityService::class)->revoke($identity, auth()->id(), $reason);
            }

            $this->issuedQrToken = null;
            session()->flash('success', "QR {$this->subjectLabel} با ثبت علت، ابطال شد.");
        }

        $this->cancelQrLifecycleAction();
        $this->refreshQrIdentityState();
    }

    public function render()
    {
        return view('livewire.shared.qr-identity-modal');
    }

    private function refreshQrIdentityState(): void
    {
        $identity = $this->activeQrIdentity();

        $this->publicCode = $identity?->public_code;
        $this->scanUrl = $identity?->scan_url;
        $this->qrMarkup = $identity?->qr_svg;
    }

    private function activeQrIdentity(): ?QrIdentity
    {
        if (! $this->qrSubjectId || ! $this->qrSubjectType) {
            return null;
        }

        return QrIdentity::query()
            ->where('subject_type', $this->qrSubjectType)
            ->where('subject_id', $this->qrSubjectId)
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    private function openPermission(string $subjectType): string
    {
        return $subjectType === QrIdentity::SUBJECT_GUARDIAN ? 'full-access' : 'people-edit';
    }

    private function resolveSubject(string $subjectType, int $subjectId): Person|Guardian
    {
        return match ($subjectType) {
            QrIdentity::SUBJECT_PERSON => Person::query()->findOrFail($subjectId),
            QrIdentity::SUBJECT_GUARDIAN => Guardian::query()->findOrFail($subjectId),
            default => abort(422),
        };
    }

    private function subjectName(Person|Guardian $subject): string
    {
        return $subject->full_name ?: trim($subject->first_name.' '.$subject->last_name) ?: '-';
    }
}
