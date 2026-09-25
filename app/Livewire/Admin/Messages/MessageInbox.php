<?php

namespace App\Livewire\Admin\Messages;

use App\Helpers\Morilog\Jalalian;
use App\Models\Conversation;
use App\Models\Message;
use App\Traits\InteractsWithNotificationModal;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MessageInbox extends Component
{
    use InteractsWithNotificationModal, WithPagination;

    public string $statusFilter = 'all';

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 15;

    public ?int $selectedThreadId = null;

    public string $replyBody = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-messages'), 403);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function selectThread(int $threadId): void
    {
        Conversation::query()->findOrFail($threadId);

        $this->selectedThreadId = $threadId;
        $this->replyBody = '';

        Message::query()
            ->where('conversation_id', $threadId)
            ->where('is_from_staff', false)
            ->whereNull('staff_read_at')
            ->update(['staff_read_at' => now()]);

        $this->dispatch('messages-updated');
    }

    public function markAllAsRead(): void
    {
        Message::query()
            ->where('is_from_staff', false)
            ->whereNull('staff_read_at')
            ->update(['staff_read_at' => now()]);

        $this->dispatch('messages-updated');
    }

    public function closeThread(int $threadId): void
    {
        Conversation::query()->findOrFail($threadId)->update([
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->dispatch('messages-updated');
    }

    public function reopenThread(int $threadId): void
    {
        Conversation::query()->findOrFail($threadId)->update([
            'status' => Conversation::STATUS_PENDING,
        ]);

        $this->dispatch('messages-updated');
    }

    public function deleteThread(int $threadId): void
    {
        Conversation::query()->findOrFail($threadId)->delete();

        if ($this->selectedThreadId === $threadId) {
            $this->selectedThreadId = null;
        }

        $this->resetPage();
        $this->dispatch('messages-updated');
    }

    public function sendReply(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-messages'), 403);

        if (! $this->selectedThreadId) {
            return;
        }

        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
        ], [
            'replyBody.required' => 'متن پاسخ را وارد کنید.',
            'replyBody.max' => 'متن پاسخ حداکثر می‌تواند ۵۰۰۰ نویسه باشد.',
        ]);

        $thread = Conversation::query()->findOrFail($this->selectedThreadId);

        if ($thread->status === Conversation::STATUS_CLOSED) {
            $this->addError('replyBody', 'این گفتگو بسته شده است. برای پاسخ‌دهی، ابتدا گفتگو را بازگشایی کنید.');

            return;
        }

        $user = auth()->user();

        Message::query()->create([
            'conversation_id' => $thread->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'sender_name' => $user->full_name ?: $user->name,
            'is_from_staff' => true,
            'body' => trim($this->replyBody),
            'staff_read_at' => now(),
        ]);

        $thread->update([
            'status' => Conversation::STATUS_ANSWERED,
            'last_message_at' => now(),
        ]);

        $this->replyBody = '';
        $this->dispatch('messages-updated');
        $this->openNotificationModal([
            'type' => 'success',
            'title' => 'پاسخ ارسال شد',
            'message' => 'پاسخ شما در پنل فرستنده قابل مشاهده است.',
        ]);
    }

    #[On('messages-updated')]
    public function refreshList(): void
    {
        // فهرست در render بازخوانی می‌شود.
    }

    protected function jalaliToCarbon(string $jalaliDate): ?\Carbon\Carbon
    {
        $jalaliDate = trim($jalaliDate);

        if ($jalaliDate === '') {
            return null;
        }

        try {
            // Jalalian::toCarbon() نسخه داخلی Carbon را برمی‌گرداند؛ به Carbon استاندارد تبدیل می‌کنیم.
            return \Carbon\Carbon::parse(
                Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon()->format('Y-m-d')
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        $query = Conversation::query()
            ->with('lastMessage')
            ->withCount(['messages as unread_staff_count' => function ($query): void {
                $query->where('is_from_staff', false)->whereNull('staff_read_at');
            }])
            ->orderByDesc('last_message_at');

        $query = match ($this->statusFilter) {
            'unread' => $query->unreadForStaff(),
            Conversation::STATUS_PENDING => $query->where('status', Conversation::STATUS_PENDING),
            Conversation::STATUS_ANSWERED => $query->where('status', Conversation::STATUS_ANSWERED),
            Conversation::STATUS_CLOSED => $query->where('status', Conversation::STATUS_CLOSED),
            default => $query,
        };

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';

            $query->where(function ($query) use ($term): void {
                $query->where('subject', 'like', $term)
                    ->orWhere('sender_name', 'like', $term)
                    ->orWhere('sender_code', 'like', $term);
            });
        }

        if ($from = $this->jalaliToCarbon($this->dateFrom)) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->jalaliToCarbon($this->dateTo)) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $unreadCount = Message::query()
            ->where('is_from_staff', false)
            ->whereNull('staff_read_at')
            ->count();

        $selectedThread = $this->selectedThreadId
            ? Conversation::query()->with('messages.attachments')->find($this->selectedThreadId)
            : null;

        return view('livewire.admin.messages.message-inbox', [
            'threads' => $query->paginate($this->perPage),
            'selectedThread' => $selectedThread,
            'unreadCount' => $unreadCount,
        ]);
    }
}
