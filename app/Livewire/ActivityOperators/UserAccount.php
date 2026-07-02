<?php

namespace App\Livewire\ActivityOperators;

use App\Livewire\Admin\UserAccount as AdminUserAccount;
use Livewire\Attributes\Layout;

#[Layout('layouts.activity-operator')]
class UserAccount extends AdminUserAccount
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);

        parent::mount();
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-activity-operator-panel'), 403);

        return view('livewire.activity-operators.user-account', [
            'user' => auth()->user(),
            'profilePhotoUrl' => $this->profilePhotoPreviewUrl ?? (auth()->user()->profile_photo_path ? asset(auth()->user()->profile_photo_path) : null),
        ]);
    }
}
