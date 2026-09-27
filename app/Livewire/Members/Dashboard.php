<?php

namespace App\Livewire\Members;

use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('پنل اعضا | آوای همدلی')]
class Dashboard extends Component
{
    public function render()
    {
        $person = Auth::guard('member')->user();

        $unreadCount = $person === null ? 0 : Message::query()
            ->where('is_from_staff', true)
            ->whereNull('member_read_at')
            ->whereHas('conversation', fn (Builder $query) => $query->forSender($person->getMorphClass(), $person->id))
            ->count();

        return view('livewire.members.dashboard', [
            'person' => $person,
            'unreadCount' => $unreadCount,
        ]);
    }
}
