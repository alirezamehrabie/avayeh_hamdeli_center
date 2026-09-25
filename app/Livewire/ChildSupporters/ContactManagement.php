<?php

namespace App\Livewire\ChildSupporters;

use App\Helpers\PersianText;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messages\UserConversationService;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.child-supporter')]
#[Title('ارتباط با مدیریت | آوای همدلی')]
class ContactManagement extends Component
{
    use InteractsWithNotificationModal, WithFileUploads;

    public ?int $selectedThreadId = null;

    public bool $showNewForm = false;

    public string $newSubject = '';

    public string $newBody = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newPhotos = [];

    public string $replyBody = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $replyPhotos = [];

    public ?string $successMessage = null;

    public function mount()
    {
        abort_unless(auth()->check(), 403);

        if (! auth()->user()->can('access-child-supporter-panel')) {
            return redirect()->to(auth()->user()->getPanelRedirectPath());
        }
    }

    public function backToList(): void
    {
        $this->selectedThreadId = null;
        $this->reset('replyBody', 'replyPhotos', 'successMessage');
    }

    public function createThread(): void
    {
        $user = $this->supporter();
        abort_unless($user !== null, 403);

        $this->newSubject = trim($this->newSubject);
        $this->newBody = trim($this->newBody);

        $this->validate([
            'newSubject' => ['required', 'string', 'max:190'],
            'newBody' => ['required', 'string', 'max:5000'],
            'newPhotos' => ['array', 'max:'.UserConversationService::MAX_ATTACHMENTS],
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'newSubject.required' => 'موضوع پیام را وارد کنید.',
            'newSubject.max' => 'موضوع پیام حداکثر می‌تواند ۱۹۰ نویسه باشد.',
            'newBody.required' => 'متن پیام را وارد کنید.',
            'newBody.max' => 'متن پیام حداکثر می‌تواند ۵۰۰۰ نویسه باشد.',
            'newPhotos.max' => 'حداکثر '.PersianText::normalizeDigits((string) UserConversationService::MAX_ATTACHMENTS).' تصویر می‌توانید پیوست کنید.',
            'newPhotos.*.image' => 'فایل پیوست باید تصویر باشد.',
            'newPhotos.*.mimes' => 'فرمت تصویر باید jpg، jpeg، png یا webp باشد.',
            'newPhotos.*.max' => 'حجم هر تصویر حداکثر ۲ مگابایت است.',
        ]);

        if (RateLimiter::tooManyAttempts($this->threadThrottleKey(), UserConversationService::THREADS_PER_DAY)) {
            $minutes = (int) ceil(RateLimiter::availableIn($this->threadThrottleKey()) / 60);
            $this->addError('newSubject', 'تعداد پیام‌های ارسالی امروز شما به حداکثر رسیده است. لطفا '.$minutes.' دقیقه دیگر دوباره تلاش کنید.');

            return;
        }

        $conversation = app(UserConversationService::class)->createThread(
            $user,
            Conversation::SENDER_ROLE_CHILD_SUPPORTER,
            $user->full_name ?: $user->name,
            $user->sponsorProfile?->supporter_code,
            $this->newSubject,
            $this->newBody,
            $this->newPhotos,
        );

        RateLimiter::hit($this->threadThrottleKey(), 86400);

        $this->reset('newSubject', 'newBody', 'newPhotos', 'showNewForm', 'successMessage');
        $this->selectedThreadId = $conversation->id;
        $this->successMessage = 'پیام شما با موفقیت ارسال شد. پاسخ مدیریت در همین صفحه نمایش داده می‌شود.';

        $this->openNotificationModal([
            'type' => 'success',
            'title' => 'پیام ارسال شد',
            'message' => 'پیام شما برای مدیریت مرکز ارسال شد.',
        ]);
    }

    public function openThread(int $threadId): void
    {
        $conversation = $this->ownedThreads()->find($threadId);

        if (! $conversation) {
            return;
        }

        $this->selectedThreadId = $threadId;
        $this->reset('replyBody', 'replyPhotos', 'successMessage');

        // پاسخ‌های مدیریت با مشاهدهٔ گفتگو در سمت حامی خوانده‌شده می‌شوند.
        Message::query()
            ->where('conversation_id', $threadId)
            ->where('is_from_staff', true)
            ->whereNull('member_read_at')
            ->update(['member_read_at' => now()]);
    }

    public function sendReply(): void
    {
        $user = $this->supporter();
        abort_unless($user !== null, 403);

        $conversation = $this->ownedThreads()->find($this->selectedThreadId);

        if (! $conversation) {
            return;
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            $this->addError('replyBody', 'این گفتگو بسته شده است. برای پیگیری جدید، پیام تازه‌ای ارسال کنید.');

            return;
        }

        $this->replyBody = trim($this->replyBody);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
            'replyPhotos' => ['array', 'max:'.UserConversationService::MAX_ATTACHMENTS],
            'replyPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'replyBody.required' => 'متن پاسخ را وارد کنید.',
            'replyBody.max' => 'متن پاسخ حداکثر می‌تواند ۵۰۰۰ نویسه باشد.',
            'replyPhotos.max' => 'حداکثر '.PersianText::normalizeDigits((string) UserConversationService::MAX_ATTACHMENTS).' تصویر می‌توانید پیوست کنید.',
            'replyPhotos.*.image' => 'فایل پیوست باید تصویر باشد.',
            'replyPhotos.*.mimes' => 'فرمت تصویر باید jpg، jpeg، png یا webp باشد.',
            'replyPhotos.*.max' => 'حجم هر تصویر حداکثر ۲ مگابایت است.',
        ]);

        $replyKey = 'supporter-conversation-replies:'.$user->id.':'.$conversation->id;

        if (RateLimiter::tooManyAttempts($replyKey, UserConversationService::MESSAGES_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($replyKey) / 60);
            $this->addError('replyBody', 'تعداد پیام‌های این گفتگو در یک ساعت گذشته به حداکثر رسیده است. لطفا '.$minutes.' دقیقه دیگر تلاش کنید.');

            return;
        }

        app(UserConversationService::class)->addReply($conversation, $user, $user->full_name ?: $user->name, $this->replyBody, $this->replyPhotos);

        RateLimiter::hit($replyKey, 3600);

        $this->reset('replyBody', 'replyPhotos', 'successMessage');
        $this->successMessage = 'پاسخ شما ارسال شد.';
    }

    private function supporter(): ?User
    {
        $user = auth()->user();

        return $user !== null && $user->can('access-child-supporter-panel') ? $user : null;
    }

    private function threadThrottleKey(): string
    {
        return 'supporter-conversations:'.$this->supporter()->id;
    }

    private function ownedThreads(): Builder
    {
        $user = $this->supporter();

        return Conversation::query()->forSender($user->getMorphClass(), $user->id);
    }

    public function render()
    {
        $user = $this->supporter();
        abort_unless($user !== null, 403);

        $threads = Conversation::query()
            ->forSender('user', $user->id)
            ->with('lastMessage')
            ->withCount(['messages as unread_member_count' => function ($query): void {
                $query->where('is_from_staff', true)->whereNull('member_read_at');
            }])
            ->orderByDesc('last_message_at')
            ->get();

        $selectedThread = $this->selectedThreadId
            ? $this->ownedThreads()->with('messages.attachments')->find($this->selectedThreadId)
            : null;

        return view('livewire.child-supporters.contact-management', [
            'threads' => $threads,
            'selectedThread' => $selectedThread,
        ]);
    }
}
