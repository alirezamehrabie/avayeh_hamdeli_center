<?php

namespace App\Livewire\Auth;

use App\Helpers\PersianText;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('ورود اعضا | آوای همدلی')]
class MemberLogin extends Component
{
    public string $nationalId = '';

    public string $personCode = '';

    public function mount()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }
    }

    public function login()
    {
        $this->nationalId = PersianText::digitsOnly($this->nationalId);
        $this->personCode = PersianText::digitsOnly($this->personCode);

        $this->validate(
            [
                'nationalId' => 'required|digits:10',
                'personCode' => 'required|digits_between:3,8',
            ],
            [
                'nationalId.required' => 'کد ملی را وارد کنید.',
                'nationalId.digits' => 'کد ملی باید ۱۰ رقم بدون فاصله باشد.',
                'personCode.required' => 'کد عضویت را وارد کنید.',
                'personCode.digits_between' => 'کد عضویت باید ارقام لاتین یا فارسی باشد.',
            ]
        );

        $this->ensureIsNotRateLimited();

        if (! Cache::add($this->loginAttemptKey(), true, 15)) {
            $this->addError('auth', 'در حال بررسی اطلاعات ورود هستیم. لطفا چند لحظه دیگر دوباره تلاش کنید.');

            return null;
        }

        try {
            $person = Person::query()
                ->where('national_id', $this->nationalId)
                ->where('person_code', $this->personCode)
                ->first();

            if (! $person) {
                RateLimiter::hit($this->throttleKey(), 120);
                Log::warning('auth.member_login.failed_credentials', [
                    'ip' => request()->ip(),
                    'member_identifier_hash' => $this->memberIdentifierHash(),
                ]);
                $this->addError('auth', 'کد ملی و کد عضویت وارد شده با سوابق یک مددجو در مرکز مطابقت ندارد.');

                return null;
            }

            Auth::guard('member')->login($person);
            session()->regenerate();
            RateLimiter::clear($this->throttleKey());

            Log::info('auth.member_login.success', [
                'ip' => request()->ip(),
                'person_id' => $person->id,
                'member_identifier_hash' => $this->memberIdentifierHash(),
            ]);

            return redirect()->route('member.dashboard');
        } finally {
            Cache::forget($this->loginAttemptKey());
        }
    }

    public function updatedNationalId(): void
    {
        $this->resetErrorBag('nationalId');
        $this->resetErrorBag('auth');
    }

    public function updatedPersonCode(): void
    {
        $this->resetErrorBag('personCode');
        $this->resetErrorBag('auth');
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'auth' => "تعداد تلاش‌های ورود بیش از حد مجاز است. لطفا {$seconds} ثانیه دیگر دوباره تلاش کنید.",
        ]);
    }

    protected function throttleKey(): string
    {
        return 'member-login:'.$this->nationalId.'|'.request()->ip();
    }

    protected function loginAttemptKey(): string
    {
        return 'member-login:attempting:'.$this->throttleKey();
    }

    protected function memberIdentifierHash(): string
    {
        return hash('sha256', $this->nationalId.'|'.$this->personCode);
    }

    public function render()
    {
        return view('livewire.auth.member-login');
    }
}
