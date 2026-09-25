<?php

namespace App\Livewire\Admin\Messages;

use App\Models\Message;
use Livewire\Component;

class MessageBadge extends Component
{
    public function render()
    {
        $unreadCount = Message::query()
            ->where('is_from_staff', false)
            ->whereNull('staff_read_at')
            ->count();

        return view('livewire.admin.messages.message-badge', [
            'unreadCount' => $unreadCount,
        ]);
    }
}
