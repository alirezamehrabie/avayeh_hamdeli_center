<?php

namespace App\Livewire\ChildSupporters;

use App\Models\Sponsor;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.child-supporter')]
class SponsorRegistration extends Component
{
    public bool $embedded = false;

    public string $fullName = '';
    public string $monthlyDonationAmount = '';
    public string $mobile = '';
    public string $childPreferences = '';
    public array $monthlyPaymentReminderMethods = [];
    public ?string $isSocialMediaActive = null;

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorizeAccess();
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'fullName',
            'monthlyDonationAmount',
            'mobile',
            'monthlyPaymentReminderMethods',
            'isSocialMediaActive',
        ], true)) {
            $this->validateOnly($property, $this->rules(), $this->messages());
        }
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $validated = $this->validate($this->rules(), $this->messages());

        Sponsor::create([
            'full_name' => trim($validated['fullName']),
            'monthly_donation_amount' => (int) preg_replace('/\D+/', '', $validated['monthlyDonationAmount']),
            'mobile' => $this->normalizeMobile($validated['mobile']),
            'child_preferences' => filled($validated['childPreferences'] ?? null) ? trim($validated['childPreferences']) : null,
            'monthly_payment_reminder_methods' => array_values($validated['monthlyPaymentReminderMethods']),
            'is_social_media_active' => $validated['isSocialMediaActive'] === 'yes',
            'created_by' => auth()->id(),
        ]);

        $this->reset([
            'fullName',
            'monthlyDonationAmount',
            'mobile',
            'childPreferences',
            'monthlyPaymentReminderMethods',
            'isSocialMediaActive',
        ]);

        session()->flash('success', 'ثبت نام حامی با موفقیت انجام شد.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $reminderKeys = array_keys(Sponsor::reminderMethodOptions());

        return [
            'fullName' => ['required', 'string', 'min:3', 'max:150'],
            'monthlyDonationAmount' => [
                'required',
                'regex:/^[\d,\s]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $amount = (int) preg_replace('/\D+/', '', (string) $value);

                    if ($amount < 1000) {
                        $fail('مبلغ واریزی ماهیانه معتبر نیست.');
                    }
                },
            ],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'childPreferences' => ['nullable', 'string', 'max:1000'],
            'monthlyPaymentReminderMethods' => ['required', 'array', 'min:1'],
            'monthlyPaymentReminderMethods.*' => ['string', Rule::in($reminderKeys)],
            'isSocialMediaActive' => ['required', Rule::in(['yes', 'no'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'fullName.required' => 'نام و نام خانوادگی الزامی است.',
            'fullName.min' => 'نام وارد شده کوتاه است.',
            'fullName.max' => 'نام وارد شده بیش از حد طولانی است.',
            'monthlyDonationAmount.required' => 'مبلغ واریزی ماهیانه الزامی است.',
            'monthlyDonationAmount.regex' => 'مبلغ را فقط با عدد وارد کنید.',
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل باید با فرمت 09xxxxxxxxx باشد.',
            'childPreferences.max' => 'توضیحات کودک حداکثر ۱۰۰۰ کاراکتر باشد.',
            'monthlyPaymentReminderMethods.required' => 'حداقل یک روش یادآوری را انتخاب کنید.',
            'monthlyPaymentReminderMethods.min' => 'حداقل یک روش یادآوری را انتخاب کنید.',
            'monthlyPaymentReminderMethods.*.in' => 'روش یادآوری انتخاب شده معتبر نیست.',
            'isSocialMediaActive.required' => 'وضعیت فعالیت در فضای مجازی را مشخص کنید.',
            'isSocialMediaActive.in' => 'گزینه انتخاب شده معتبر نیست.',
        ];
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->check()
            && (auth()->user()->can('access-child-supporter-panel') || auth()->user()->can('access-admin-panel')),
            403
        );
    }

    private function normalizeMobile(string $mobile): string
    {
        return preg_replace('/\D+/', '', $mobile) ?: $mobile;
    }

    public function getFormattedDonationProperty(): string
    {
        $amount = (int) preg_replace('/\D+/', '', $this->monthlyDonationAmount);

        return $amount > 0 ? number_format($amount) . ' ریال' : '';
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.child-supporters.sponsor-registration', [
            'reminderMethods' => Sponsor::reminderMethodOptions(),
        ]);
    }
}
