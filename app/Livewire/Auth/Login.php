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
        'email' => 'required|string',
        'password' => 'required|min:6',
        'portal' => 'required|in:admin,social_worker',
    ];

    public function login()
    {
        $loginInput = Str::lower(trim((string) $this->email));
        $this->email = $loginInput;
        $this->validate();
        $this->ensureIsNotRateLimited();

        $credentials = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? ['email' => $loginInput, 'password' => $this->password]
            : ['name' => $loginInput, 'password' => $this->password];

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();

            $isAdmin = auth()->user()->isAdmin();
            $isSocialWorker = auth()->user()->canAccessSocialWorkerPanel();

            if (($this->portal === 'admin' && ! $isAdmin) || ($this->portal === 'social_worker' && ! $isSocialWorker)) {
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

            if ($isSocialWorker) {
                return redirect()->intended(route('social-worker.dashboard'));
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
