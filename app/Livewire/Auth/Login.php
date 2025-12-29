<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')] // یا هر لایوتی که برای صفحات عمومی دارید
#[Title('ورود به سامانه آوای همدلی')]
class Login extends Component
{
    public $email;
    public $password;
    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            // اگر ادمین بود برو به داشبورد، در غیر این صورت (مثلاً مددکار بود) به مسیر دیگر
            if (auth()->user()->is_admin) {
                return redirect()->intended(route('admin.dashboard'));
            }

            // اگر ادمین نبود (بسته به نیاز پروژه)
            return redirect()->intended('/');
        }

        $this->addError('email', 'اطلاعات ورود صحیح نیست.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
