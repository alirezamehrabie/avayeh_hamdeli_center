<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    public const SENDER_ROLE_MEMBER = 'member';

    public const SENDER_ROLE_CHILD_SUPPORTER = 'child_supporter';

    protected $fillable = [
        'subject',
        'status',
        'sender_type',
        'sender_id',
        'sender_name',
        'sender_role',
        'sender_code',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'در انتظار بررسی',
            self::STATUS_ANSWERED => 'پاسخ داده شد',
            self::STATUS_CLOSED => 'بسته شده',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * برچسب نمایشی نقش فرستنده در لحظهٔ ارسال پیام.
     */
    public function senderRoleLabel(): string
    {
        return $this->sender_role === self::SENDER_ROLE_CHILD_SUPPORTER ? 'حامی کودک' : 'عضو';
    }

    public function senderStatusClasses(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-100 text-amber-700',
            self::STATUS_ANSWERED => 'bg-emerald-100 text-emerald-700',
            default => 'bg-slate-200 text-slate-600',
        };
    }

    public function scopeForSender(Builder $query, string $senderType, int $senderId): Builder
    {
        return $query->where('sender_type', $senderType)->where('sender_id', $senderId);
    }

    public function scopeUnreadForStaff(Builder $query): Builder
    {
        return $query->whereHas('messages', function (Builder $query): void {
            $query->where('is_from_staff', false)->whereNull('staff_read_at');
        });
    }
}
