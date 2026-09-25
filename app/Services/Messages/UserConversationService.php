<?php

namespace App\Services\Messages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Notifications\ManagerNotificationDispatcher;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * ثبت پیام‌های ارسالی از سمت کاربران (اعضای پنل یا حامیان کودک) و
 * اتصال آن‌ها به خط اعلان مدیریت. پیام‌های سمت مدیریت (پاسخ‌ها) از
 * همین مسیر عبور نمی‌کنند و در کامپوننت صندوق مدیریت ساخته می‌شوند.
 */
class UserConversationService
{
    /** حداکثر تعداد پیوست تصویری برای هر پیام */
    public const MAX_ATTACHMENTS = 3;

    /** حداکثر تعداد گفتگوی جدید هر فرستنده در ۲۴ ساعت */
    public const THREADS_PER_DAY = 3;

    /** حداکثر تعداد پیام (پاسخ) هر فرستنده در یک گفتگو در هر ساعت */
    public const MESSAGES_PER_HOUR = 10;

    public function __construct(private readonly ManagerNotificationDispatcher $dispatcher) {}

    /**
     * @param  array<int, TemporaryUploadedFile>  $photos
     */
    public function createThread(
        Model $sender,
        string $senderRole,
        string $senderName,
        ?string $senderCode,
        string $subject,
        string $body,
        array $photos = []
    ): Conversation {
        $conversation = Conversation::query()->create([
            'subject' => $subject,
            'status' => Conversation::STATUS_PENDING,
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_name' => $senderName,
            'sender_role' => $senderRole,
            'sender_code' => $senderCode,
            'last_message_at' => now(),
        ]);

        $message = $this->storeMessage($conversation, $sender, $senderName, $body, $photos);

        $this->notifyManagers($conversation, $message, $sender);

        return $conversation;
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $photos
     */
    public function addReply(Conversation $conversation, Model $sender, string $senderName, string $body, array $photos = []): Message
    {
        $message = $this->storeMessage($conversation, $sender, $senderName, $body, $photos);

        $conversation->update(['last_message_at' => now()]);

        $this->notifyManagers($conversation, $message, $sender);

        return $message;
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $photos
     */
    private function storeMessage(Conversation $conversation, Model $sender, string $senderName, string $body, array $photos): Message
    {
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_name' => $senderName,
            'is_from_staff' => false,
            'body' => $body,
        ]);

        foreach ($photos as $photo) {
            $path = $photo->store('message-attachments/'.$conversation->id, 'local');

            MessageAttachment::query()->create([
                'message_id' => $message->id,
                'original_name' => $photo->getClientOriginalName(),
                'path' => $path,
                'mime' => (string) $photo->getMimeType(),
                'size' => (int) $photo->getSize(),
            ]);
        }

        return $message;
    }

    private function notifyManagers(Conversation $conversation, Message $message, Model $sender): void
    {
        $roleLabel = $conversation->senderRoleLabel();
        $codeSuffix = filled($conversation->sender_code) ? ' - کد '.$conversation->sender_code : '';

        $this->dispatcher->dispatch(
            NotificationEventRegistry::EVENT_MESSAGE_RECEIVED,
            $sender instanceof User ? $sender : null,
            'پیام جدید: '.$conversation->subject,
            $conversation->sender_name.' ('.$roleLabel.$codeSuffix.'): '.Str::limit($message->body, 120),
            $message,
            [
                'conversation_id' => $conversation->id,
                'sender_role' => $conversation->sender_role,
                'sender_code' => $conversation->sender_code,
            ],
        );
    }
}
