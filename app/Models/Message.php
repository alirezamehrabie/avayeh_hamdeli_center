<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'is_from_staff',
        'body',
        'staff_read_at',
        'member_read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_from_staff' => 'boolean',
            'staff_read_at' => 'datetime',
            'member_read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function markStaffRead(): void
    {
        if ($this->staff_read_at === null) {
            $this->forceFill(['staff_read_at' => now()])->save();
        }
    }

    public function markMemberRead(): void
    {
        if ($this->member_read_at === null) {
            $this->forceFill(['member_read_at' => now()])->save();
        }
    }
}
