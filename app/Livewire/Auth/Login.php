<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')] // یا هر لایوتی که برای صفحات عمومی دارید
#[Title('ورود به سامانه آوای همدلی')]
class Login extends Component
{
    public $email;
    public $password;
    public $remember = false;
    public $portal = 'admin';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
        'portal' => 'required|in:admin,user',
    ];

    public function login()
    {
        $this->email = Str::lower(trim((string) $this->email));
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            $isAdmin = (bool) auth()->user()->is_admin;
            if (($this->portal === 'admin' && ! $isAdmin) || ($this->portal === 'user' && $isAdmin)) {
                Auth::logout();
                session()->regenerate();
                RateLimiter::hit($this->throttleKey(), 120);
                $this->addError('email', 'حساب انتخاب شده با نوع پنل همخوانی ندارد.');
                return;
            }

            RateLimiter::clear($this->throttleKey());
            if ($isAdmin) {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended('/');
        }

        RateLimiter::hit($this->throttleKey(), 120);
        $this->addError('email', 'اطلاعات ورود صحیح نیست.');
    }

    public function updatedPortal(): void
    {
        $this->resetErrorBag('email');
    }

    public function updatedEmail(): void
    {
        $this->resetErrorBag('email');
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => "تعداد تلاش های ورود بیش از حد مجاز است. لطفا {$seconds} ثانیه دیگر دوباره تلاش کنید.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
