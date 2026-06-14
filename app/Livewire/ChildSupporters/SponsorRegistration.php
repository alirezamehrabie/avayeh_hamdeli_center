<?php

namespace App\Livewire\ChildSupporters;

use App\Models\SponsorProfile;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.child-supporter')]
class SponsorRegistration extends Component
{
    public bool $embedded = false;

    public string $firstName = '';
    public string $lastName = '';
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
            'firstName',
            'lastName',
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
        $mobile = $this->normalizeMobile($validated['mobile']);
        $firstName = $this->normalizeNamePart($validated['firstName']);
        $lastName = $this->normalizeNamePart($validated['lastName']);

        User::registerSponsorAccount([
            'mobile' => $mobile,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'monthly_donation_amount' => (int) preg_replace('/\D+/', '', $validated['monthlyDonationAmount']),
            'child_preferences' => filled($validated['childPreferences'] ?? null) ? trim($validated['childPreferences']) : null,
            'monthly_payment_reminder_methods' => array_values($validated['monthlyPaymentReminderMethods']),
            'is_social_media_active' => $validated['isSocialMediaActive'] === 'yes',
            'created_by' => auth()->id(),
        ]);

        $this->reset([
            'firstName',
            'lastName',
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
        $reminderKeys = array_keys(SponsorProfile::reminderMethodOptions());

        return [
            'firstName' => ['required', 'string', 'min:2', 'max:100'],
            'lastName' => ['required', 'string', 'min:2', 'max:100'],
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
            'mobile' => [
                'required',
                'digits:11',
                'regex:/^09\d{9}$/',
            ],
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
            'firstName.required' => 'نام الزامی است.',
            'firstName.min' => 'نام وارد شده کوتاه است.',
            'firstName.max' => 'نام وارد شده بیش از حد طولانی است.',
            'lastName.required' => 'نام خانوادگی الزامی است.',
            'lastName.min' => 'نام خانوادگی وارد شده کوتاه است.',
            'lastName.max' => 'نام خانوادگی وارد شده بیش از حد طولانی است.',
            'monthlyDonationAmount.required' => 'مبلغ واریزی ماهیانه الزامی است.',
            'monthlyDonationAmount.regex' => 'مبلغ را فقط با عدد وارد کنید.',
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.digits' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'mobile.regex' => 'شماره موبایل باید با ۰۹ شروع شود و دقیقاً ۱۱ رقم باشد.',
            'mobile.unique' => 'برای این شماره موبایل قبلا حساب کاربری ثبت شده است.',
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

    private function normalizeNamePart(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?: trim($value);
    }

    private function combineFullName(string $firstName, string $lastName): string
    {
        return trim($this->normalizeNamePart($firstName) . ' ' . $this->normalizeNamePart($lastName));
    }

    public function getFullNameProperty(): string
    {
        return $this->combineFullName($this->firstName, $this->lastName);
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
            'reminderMethods' => SponsorProfile::reminderMethodOptions(),
        ]);
    }
}
