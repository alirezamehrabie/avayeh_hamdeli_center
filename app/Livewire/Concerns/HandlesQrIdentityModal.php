<?php

namespace App\Livewire\Concerns;

use App\Models\QrIdentity;
use App\Services\QrIdentityService;

trait HandlesQrIdentityModal
{
    public bool $showQrModal = false;
    public ?int $qrSubjectId = null;
    public ?string $issuedQrToken = null;
    public bool $confirmingQrLifecycleAction = false;
    public string $qrLifecycleAction = '';
    public string $qrLifecycleReason = '';

    abstract protected function qrSubjectType(): string;

    abstract protected function qrOpenPermission(): string;

    abstract protected function qrManagePermission(): string;

    abstract protected function resolveQrSubject(int $subjectId): mixed;

    protected function qrIssueOrReissueLabel(): string
    {
        return 'QR';
    }

    public function openQrModal(int $subjectId): void
    {
        abort_unless(auth()->check() && auth()->user()->can($this->qrOpenPermission()), 403);

        $subject = $this->resolveQrSubject($subjectId);
        app(QrIdentityService::class)->ensureActiveFor($subject, auth()->id());

        $this->qrSubjectId = $subject->id;
        $this->issuedQrToken = null;
        $this->confirmingQrLifecycleAction = false;
        $this->qrLifecycleAction = '';
        $this->qrLifecycleReason = '';
        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrSubjectId = null;
        $this->issuedQrToken = null;
        $this->confirmingQrLifecycleAction = false;
        $this->qrLifecycleAction = '';
        $this->qrLifecycleReason = '';
        $this->resetValidation(['qrLifecycleReason']);
    }

    public function requestQrLifecycleAction(string $action): void
    {
        abort_unless(auth()->check() && auth()->user()->can($this->qrManagePermission()), 403);
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
        abort_unless(auth()->check() && auth()->user()->can($this->qrManagePermission()), 403);
        abort_unless(in_array($this->qrLifecycleAction, ['reissue', 'revoke'], true), 422);

        $validated = $this->validate([
            'qrLifecycleReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'qrLifecycleReason.required' => 'ثبت علت برای تغییر وضعیت QR الزامی است.',
            'qrLifecycleReason.min' => 'علت تغییر وضعیت QR باید حداقل ۱۰ کاراکتر باشد.',
        ]);

        $reason = trim($validated['qrLifecycleReason']);

        if ($this->qrLifecycleAction === 'reissue') {
            $subject = $this->resolveQrSubject((int) $this->qrSubjectId);
            $issued = app(QrIdentityService::class)->replaceFor($subject, auth()->id(), $reason);
            $this->issuedQrToken = $issued['token'];
            session()->flash('success', "{$this->qrIssueOrReissueLabel()} {$this->qrSubjectLabel()} با ثبت علت، دوباره صادر شد.");
        } else {
            $identity = $this->selectedQrIdentity;

            if ($identity) {
                app(QrIdentityService::class)->revoke($identity, auth()->id(), $reason);
            }

            $this->issuedQrToken = null;
            session()->flash('success', "{$this->qrIssueOrReissueLabel()} {$this->qrSubjectLabel()} با ثبت علت، ابطال شد.");
        }

        $this->cancelQrLifecycleAction();
    }

    public function getSelectedQrIdentityProperty(): ?QrIdentity
    {
        if (! $this->qrSubjectId) {
            return null;
        }

        return QrIdentity::query()
            ->where('subject_type', $this->qrSubjectType())
            ->where('subject_id', $this->qrSubjectId)
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public function getQrSubjectProperty(): mixed
    {
        return $this->qrSubjectId ? $this->resolveQrSubject($this->qrSubjectId) : null;
    }

    protected function qrSubjectLabel(): string
    {
        return 'مورد';
    }
}
