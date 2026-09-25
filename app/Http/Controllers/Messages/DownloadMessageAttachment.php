<?php

namespace App\Http\Controllers\Messages;

use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * دانلود پیوست‌های پیام از دیسک خصوصی (local).
 * دسترسی فقط برای مدیریت مجاز یا فرستندهٔ خود گفتگو؛ بررسی گاردها
 * صریح انجام می‌شود چون فرستنده می‌تواند از دو گارد مختلف باشد
 * (عضو با گارد member و حامی با گارد web).
 */
class DownloadMessageAttachment extends Controller
{
    public function __invoke(MessageAttachment $attachment): StreamedResponse
    {
        abort_unless($this->canViewAttachment($attachment), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function canViewAttachment(MessageAttachment $attachment): bool
    {
        $staff = Auth::guard('web')->user();

        if ($staff instanceof User && $staff->can('manage-messages')) {
            return true;
        }

        $member = Auth::guard('member')->user();

        if ($member && $this->senderMatches($attachment, $member)) {
            return true;
        }

        return $staff !== null && $this->senderMatches($attachment, $staff);
    }

    private function senderMatches(MessageAttachment $attachment, Authenticatable $user): bool
    {
        $conversation = $attachment->message?->conversation;

        if (! $conversation) {
            return false;
        }

        return $conversation->sender_type === $user->getMorphClass()
            && (int) $conversation->sender_id === (int) $user->getAuthIdentifier();
    }
}
