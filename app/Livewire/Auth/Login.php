<?php

namespace App\Livewire\Auth;

use App\Services\LoginRedirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('ورود به سامانه آوای همدلی')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected $rules = [
        'email' => 'required|string',
        'password' => 'required|min:6',
    ];

    protected $messages = [
        'email.required' => 'نام کاربری یا ایمیل را وارد کنید.',
        'password.required' => 'رمز عبور را وارد کنید.',
        'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
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

        if (! Cache::add($this->loginAttemptKey(), true, 15)) {
            Log::warning('auth.login.duplicate_in_flight', $this->loginLogContext([
                'login_identifier_hash' => $this->loginIdentifierHash(),
            ]));

            $this->addError('auth', 'در حال بررسی اطلاعات ورود هستیم. لطفا چند لحظه دیگر دوباره تلاش کنید.');

            return null;
        }

        try {
            if (Auth::attempt($credentials, $this->remember)) {
                $redirector = app(LoginRedirector::class);

                if (! $redirector->hasAuthorizedPanel(auth()->user())) {
                    Log::warning('auth.login.no_authorized_panel', $this->loginLogContext([
                        'user_id' => auth()->id(),
                        'access_level' => auth()->user()?->access_level,
                    ]));

                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    $this->addError('auth', 'حساب کاربری شما هنوز به پنل فعال متصل نشده است. لطفا با پشتیبانی تماس بگیرید.');

                    return null;
                }

                session()->regenerate();

                RateLimiter::clear($this->throttleKey());
                $redirectPath = $redirector->pathFor(auth()->user());
                $intendedUrl = session('url.intended');
                session()->forget('url.intended');

                Log::info('auth.login.redirect_resolved', $this->loginLogContext([
                    'user_id' => auth()->id(),
                    'access_level' => auth()->user()?->access_level,
                    'redirect_url' => $redirectPath,
                    'had_intended_url' => filled($intendedUrl),
                ]));

                return redirect()->to($redirectPath);
            }

            RateLimiter::hit($this->throttleKey(), 120);
            Log::warning('auth.login.failed_credentials', $this->loginLogContext([
                'login_identifier_hash' => $this->loginIdentifierHash(),
            ]));
            $this->addError('auth', 'اطلاعات ورود صحیح نیست.');
        } finally {
            Cache::forget($this->loginAttemptKey());
        }

        return null;
    }

    public function updatedEmail(): void
    {
        $this->resetErrorBag('email');
        $this->resetErrorBag('auth');
    }

    public function updatedPassword(): void
    {
        $this->resetErrorBag('password');
        $this->resetErrorBag('auth');
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'auth' => "تعداد تلاش های ورود بیش از حد مجاز است. لطفا {$seconds} ثانیه دیگر دوباره تلاش کنید.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }

    protected function loginAttemptKey(): string
    {
        return 'login:attempting:'.$this->throttleKey();
    }

    protected function loginIdentifierHash(): string
    {
        return hash('sha256', $this->email);
    }

    protected function loginLogContext(array $context = []): array
    {
        return array_merge([
            'ip' => request()->ip(),
            'remember' => $this->remember,
        ], $context);
    }

    /**
     * @return array{href: ?string, label: ?string}
     */
    private function supportPhone(): array
    {
        $phone = trim((string) config('services.support.phone', ''));
        $label = trim((string) config('services.support.phone_label', $phone));

        if ($phone === '') {
            return [
                'href' => null,
                'label' => null,
            ];
        }

        $dialablePhone = preg_replace('/[^\d+]/', '', $phone);

        return [
            'href' => $dialablePhone !== '' ? 'tel:'.$dialablePhone : null,
            'label' => $label !== '' ? $label : $phone,
        ];
    }

    public function render()
    {
        return view('livewire.auth.login', [
            'supportPhone' => $this->supportPhone(),
        ]);
    }
}
