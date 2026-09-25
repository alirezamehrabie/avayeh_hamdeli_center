<?php

namespace App\Livewire\Members;

use App\Helpers\PersianText;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Person;
use App\Services\Messages\UserConversationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.auth')]
#[Title('ارتباط با مدیریت | آوای همدلی')]
class ContactManagement extends Component
{
    use WithFileUploads;

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
        if (! Auth::guard('member')->check()) {
            return redirect()->route('member.login');
        }
    }

    public function backToList(): void
    {
        $this->selectedThreadId = null;
        $this->reset('replyBody', 'replyPhotos', 'successMessage');
    }

    public function createThread(): void
    {
        $person = $this->person();
        abort_unless($person !== null, 403);

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
            $person,
            Conversation::SENDER_ROLE_MEMBER,
            $this->personName($person),
            $person->person_code,
            $this->newSubject,
            $this->newBody,
            $this->newPhotos,
        );

        RateLimiter::hit($this->threadThrottleKey(), 86400);

        $this->reset('newSubject', 'newBody', 'newPhotos', 'showNewForm', 'successMessage');
        $this->selectedThreadId = $conversation->id;
        $this->successMessage = 'پیام شما با موفقیت ارسال شد. پاسخ مدیریت در همین صفحه نمایش داده می‌شود.';
    }

    public function openThread(int $threadId): void
    {
        $conversation = $this->ownedThreads()->find($threadId);

        if (! $conversation) {
            return;
        }

        $this->selectedThreadId = $threadId;
        $this->reset('replyBody', 'replyPhotos', 'successMessage');

        // پاسخ‌های مدیریت با مشاهدهٔ گفتگو در سمت عضو خوانده‌شده می‌شوند.
        Message::query()
            ->where('conversation_id', $threadId)
            ->where('is_from_staff', true)
            ->whereNull('member_read_at')
            ->update(['member_read_at' => now()]);
    }

    public function sendReply(): void
    {
        $person = $this->person();
        abort_unless($person !== null, 403);

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

        $replyKey = 'member-conversation-replies:'.$person->id.':'.$conversation->id;

        if (RateLimiter::tooManyAttempts($replyKey, UserConversationService::MESSAGES_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($replyKey) / 60);
            $this->addError('replyBody', 'تعداد پیام‌های این گفتگو در یک ساعت گذشته به حداکثر رسیده است. لطفا '.$minutes.' دقیقه دیگر تلاش کنید.');

            return;
        }

        app(UserConversationService::class)->addReply($conversation, $person, $this->personName($person), $this->replyBody, $this->replyPhotos);

        RateLimiter::hit($replyKey, 3600);

        $this->reset('replyBody', 'replyPhotos', 'successMessage');
        $this->successMessage = 'پاسخ شما ارسال شد.';
    }

    private function person(): ?Person
    {
        return Auth::guard('member')->user();
    }

    /**
     * full_name در جدول people ستون generated است و در نمونهٔ تازه‌ساخته‌شده
     * مقدار ندارد؛ نام از اجزا ساخته می‌شود.
     */
    private function personName(Person $person): string
    {
        return trim($person->first_name.' '.$person->last_name);
    }

    private function threadThrottleKey(): string
    {
        return 'member-conversations:'.$this->person()->id;
    }

    private function ownedThreads(): Builder
    {
        $person = $this->person();

        return Conversation::query()->forSender($person->getMorphClass(), $person->id);
    }

    public function render()
    {
        $person = $this->person();
        abort_unless($person !== null, 403);

        $threads = Conversation::query()
            ->forSender('person', $person->id)
            ->with('lastMessage')
            ->withCount(['messages as unread_member_count' => function ($query): void {
                $query->where('is_from_staff', true)->whereNull('member_read_at');
            }])
            ->orderByDesc('last_message_at')
            ->get();

        $selectedThread = $this->selectedThreadId
            ? $this->ownedThreads()->with('messages.attachments')->find($this->selectedThreadId)
            : null;

        return view('livewire.members.contact-management', [
            'threads' => $threads,
            'selectedThread' => $selectedThread,
        ]);
    }
}
