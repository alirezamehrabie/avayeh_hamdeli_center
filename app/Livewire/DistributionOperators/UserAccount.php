<?php

namespace App\Livewire\DistributionOperators;

use App\Livewire\Admin\UserAccount as AdminUserAccount;
use Livewire\Attributes\Layout;

#[Layout('layouts.distribution-operator')]
class UserAccount extends AdminUserAccount
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        parent::mount();
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return view('livewire.distribution-operators.user-account', [
            'user' => auth()->user(),
            'profilePhotoUrl' => $this->profilePhotoPreviewUrl ?? (auth()->user()->profile_photo_path ? asset(auth()->user()->profile_photo_path) : null),
        ]);
    }
}
