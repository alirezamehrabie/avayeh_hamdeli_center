<?php

namespace App\Livewire\ChildSupporters;

use App\Models\SponsorProfile;
use App\Models\Person;
use App\Models\User;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.child-supporter')]
class SponsorRegistration extends Component
{
    use InteractsWithNotificationModal;

    public bool $embedded = false;

    #[Url(as: 'sponsor')]
    public ?int $sponsorId = null;

    public bool $isEditing = false;

    public string $firstName = '';
    public string $lastName = '';
    public string $monthlyDonationAmount = '';
    public string $mobile = '';
    public string $childPreferences = '';
    public array $monthlyPaymentReminderMethods = [];
    public ?string $isSocialMediaActive = null;
    public string $beneficiaryCode = '';
    public array $assignedBeneficiaries = [];
    public ?array $beneficiaryPreview = null;
    public int $currentStep = 1;
    public array $reminderMethods = [];

    public function mount(bool $embedded = false, ?int $sponsorId = null): void
    {
        $this->embedded = $embedded;
        $this->reminderMethods = SponsorProfile::reminderMethodOptions();
        $this->sponsorId = $sponsorId ?: $this->sponsorId;
        $this->authorizeAccess();

        if ($this->sponsorId) {
            $this->loadSponsorForEditing($this->sponsorId);
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'beneficiaryCode') {
            $this->refreshBeneficiaryPreview();
        }
    }

    public function lookupBeneficiary(): void
    {
        $this->authorizeAccess();
        $this->refreshBeneficiaryPreview(showErrors: true);
    }

    public function addBeneficiary(): void
    {
        $this->authorizeAccess();
        $this->refreshBeneficiaryPreview(showErrors: true);

        if (! $this->beneficiaryPreview) {
            return;
        }

        $beneficiaryId = (int) $this->beneficiaryPreview['id'];

        if (collect($this->assignedBeneficiaries)->contains(fn (array $beneficiary): bool => (int) $beneficiary['id'] === $beneficiaryId)) {
            $this->addError('beneficiaryCode', 'این مددجو قبلا به لیست اضافه شده است.');

            return;
        }

        $this->assignedBeneficiaries[] = $this->beneficiaryPreview;
        $this->beneficiaryCode = '';
        $this->beneficiaryPreview = null;
        $this->resetErrorBag('beneficiaryCode');
    }

    public function removeBeneficiary(int $beneficiaryId): void
    {
        $this->assignedBeneficiaries = collect($this->assignedBeneficiaries)
            ->reject(fn (array $beneficiary): bool => (int) $beneficiary['id'] === $beneficiaryId)
            ->values()
            ->all();
    }

    public function goToStep(int $step): void
    {
        $this->setCurrentStep($step);
    }

    public function nextStep(): void
    {
        $this->setCurrentStep($this->currentStep + 1);
    }

    public function skipStep(): void
    {
        $this->nextStep();
    }

    public function previousStep(): void
    {
        $this->setCurrentStep($this->currentStep - 1);
    }

    public function save(): void
    {
        $this->authorizeAccess();

        if ($this->isEditing) {
            $this->updateSponsor();

            return;
        }

        try {
            $validated = $this->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            $this->handleFinalValidationFailure($exception);

            throw $exception;
        }

        $mobile = $this->normalizeMobile($validated['mobile']);
        $firstName = $this->normalizeNamePart($validated['firstName']);
        $lastName = $this->normalizeNamePart($validated['lastName']);

        $user = User::registerSponsorAccount([
            'mobile' => $mobile,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'monthly_donation_amount' => (int) preg_replace('/\D+/', '', $validated['monthlyDonationAmount']),
            'child_preferences' => filled($validated['childPreferences'] ?? null) ? trim($validated['childPreferences']) : null,
            'monthly_payment_reminder_methods' => array_values($validated['monthlyPaymentReminderMethods']),
            'is_social_media_active' => $validated['isSocialMediaActive'] === 'yes',
            'created_by' => auth()->id(),
        ]);

        $profile = $user->sponsorProfile()->firstOrFail();
        $beneficiaryIds = collect($this->assignedBeneficiaries)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($beneficiaryIds !== []) {
            $profile->beneficiaries()->syncWithoutDetaching($beneficiaryIds);
        }

        $supporterCode = $profile->supporter_code ?: '-';

        $this->reset([
            'firstName',
            'lastName',
            'monthlyDonationAmount',
            'mobile',
            'childPreferences',
            'monthlyPaymentReminderMethods',
            'isSocialMediaActive',
            'beneficiaryCode',
            'assignedBeneficiaries',
            'beneficiaryPreview',
        ]);
        $this->currentStep = 1;

        session()->flash('success', 'ثبت نام حامی با موفقیت انجام شد.');
    }

    private function updateSponsor(): void
    {
        if (! $this->sponsorId) {
            return;
        }

        $sponsor = SponsorProfile::query()
            ->with('user')
            ->findOrFail($this->sponsorId);

        try {
            $validated = $this->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            $this->handleFinalValidationFailure($exception);

            throw $exception;
        }

        $mobile = $this->normalizeMobile($validated['mobile']);

        $sponsor->user?->update([
            'name' => $mobile,
            'first_name' => $this->normalizeNamePart($validated['firstName']),
            'last_name' => $this->normalizeNamePart($validated['lastName']),
            'email' => $mobile.'@local.system',
            'mobile' => $mobile,
            'access_level' => User::ACCESS_LEVEL_CHILD_SUPPORTER,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $sponsor->update([
            'monthly_donation_amount' => (int) preg_replace('/\D+/', '', $validated['monthlyDonationAmount']),
            'child_preferences' => filled($validated['childPreferences'] ?? null) ? trim($validated['childPreferences']) : null,
            'monthly_payment_reminder_methods' => array_values($validated['monthlyPaymentReminderMethods']),
            'is_social_media_active' => $validated['isSocialMediaActive'] === 'yes',
        ]);

        $beneficiaryIds = collect($this->assignedBeneficiaries)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $sponsor->beneficiaries()->sync($beneficiaryIds);

        $this->loadSponsorForEditing($sponsor->id);

        session()->flash('success', 'اطلاعات حامی با موفقیت بروزرسانی شد.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $reminderKeys = array_keys(SponsorProfile::reminderMethodOptions());

        $mobileRules = [
            'required',
            'digits:11',
            'regex:/^09\d{9}$/',
        ];

        if ($this->isEditing) {
            $mobileRules[] = Rule::unique('users', 'name')->ignore($this->editingUserId());
            $mobileRules[] = Rule::unique('users', 'mobile')->ignore($this->editingUserId());
        }

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
            'mobile' => $mobileRules,
            'childPreferences' => ['nullable', 'string', 'max:1000'],
            'monthlyPaymentReminderMethods' => ['required', 'array', 'min:1'],
            'monthlyPaymentReminderMethods.*' => ['string', Rule::in($reminderKeys)],
            'isSocialMediaActive' => ['required', Rule::in(['yes', 'no'])],
            'beneficiaryCode' => ['nullable', 'string', 'max:32'],
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
            && auth()->user()->can('access-admin-panel'),
            403
        );
    }

    private function loadSponsorForEditing(int $sponsorId): void
    {
        $sponsor = SponsorProfile::query()
            ->with([
                'user:id,first_name,last_name,mobile,name',
                'beneficiaries.sponsorProfiles.user:id,first_name,last_name,name,mobile',
            ])
            ->findOrFail($sponsorId);

        $this->sponsorId = $sponsor->id;
        $this->isEditing = true;
        $this->firstName = (string) ($sponsor->user?->first_name ?? '');
        $this->lastName = (string) ($sponsor->user?->last_name ?? '');
        $this->mobile = (string) ($sponsor->user?->mobile ?: $sponsor->user?->name ?: '');
        $this->monthlyDonationAmount = number_format((int) $sponsor->monthly_donation_amount);
        $this->childPreferences = (string) ($sponsor->child_preferences ?? '');
        $this->monthlyPaymentReminderMethods = array_values((array) $sponsor->monthly_payment_reminder_methods);
        $this->isSocialMediaActive = $sponsor->is_social_media_active ? 'yes' : 'no';
        $this->assignedBeneficiaries = $sponsor->beneficiaries
            ->map(fn (Person $beneficiary): array => $this->formatBeneficiary($beneficiary))
            ->values()
            ->all();
        $this->beneficiaryCode = '';
        $this->beneficiaryPreview = null;
        $this->resetErrorBag();
    }

    private function editingUserId(): ?int
    {
        if (! $this->isEditing || ! $this->sponsorId) {
            return null;
        }

        return SponsorProfile::query()
            ->whereKey($this->sponsorId)
            ->value('user_id');
    }

    private function handleFinalValidationFailure(ValidationException $exception): void
    {
        $this->currentStep = $this->stepForValidationErrors(array_keys($exception->errors()));
        $this->openValidationErrorModal(
            $exception->errors(),
            'اطلاعات ثبت نام کامل نیست'
        );
    }

    private function setCurrentStep(int $step): void
    {
        $step = min(max($step, 1), 5);

        if ($step === $this->currentStep) {
            return;
        }

        $this->currentStep = $step;
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function stepForValidationErrors(array $fields): int
    {
        foreach ($fields as $field) {
            if (in_array($field, ['firstName', 'lastName', 'mobile', 'isSocialMediaActive'], true)) {
                return 1;
            }

            if ($field === 'monthlyDonationAmount') {
                return 2;
            }

            if (str_starts_with($field, 'monthlyPaymentReminderMethods') || $field === 'childPreferences') {
                return 4;
            }
        }

        return 5;
    }

    private function refreshBeneficiaryPreview(bool $showErrors = false): void
    {
        $this->resetErrorBag('beneficiaryCode');

        $code = $this->normalizeBeneficiaryCode($this->beneficiaryCode);
        $this->beneficiaryCode = $code;
        $this->beneficiaryPreview = null;

        if ($code === '') {
            if ($showErrors) {
                $this->addError('beneficiaryCode', 'کد مددجو را وارد کنید.');
            }

            return;
        }

        $beneficiary = $this->findAssignableBeneficiary($code);

        if (! $beneficiary) {
            if ($showErrors) {
                $this->addError('beneficiaryCode', 'مددجوی کودک با این کد پیدا نشد.');
            }

            return;
        }

        $this->beneficiaryPreview = $this->formatBeneficiary($beneficiary);
    }

    private function normalizeMobile(string $mobile): string
    {
        return preg_replace('/\D+/', '', $mobile) ?: $mobile;
    }

    private function normalizeNamePart(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?: trim($value);
    }

    private function normalizeBeneficiaryCode(string $code): string
    {
        return preg_replace('/\s+/u', '', trim($code)) ?: trim($code);
    }

    private function findAssignableBeneficiary(string $code): ?Person
    {
        return Person::query()
            ->with(['sponsorProfiles.user:id,first_name,last_name,name,mobile'])
            ->where('person_code', $code)
            ->where('role', 'child')
            ->first();
    }

    private function formatBeneficiary(Person $beneficiary): array
    {
        $supporters = $beneficiary->sponsorProfiles
            ->map(fn (SponsorProfile $profile): array => [
                'id' => $profile->id,
                'supporter_code' => $profile->supporter_code ?: '-',
                'full_name' => trim(($profile->user?->first_name ?? '') . ' ' . ($profile->user?->last_name ?? '')) ?: ($profile->user?->name ?: '-'),
            ])
            ->values()
            ->all();

        return [
            'id' => $beneficiary->id,
            'person_code' => $beneficiary->person_code,
            'full_name' => trim($beneficiary->first_name . ' ' . $beneficiary->last_name),
            'supporters_count' => count($supporters),
            'supporters' => $supporters,
        ];
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

    public function getIsReadyForReviewProperty(): bool
    {
        return $this->combineFullName($this->firstName, $this->lastName) !== ''
            && preg_match('/^09\d{9}$/', $this->normalizeMobile($this->mobile)) === 1
            && (int) preg_replace('/\D+/', '', $this->monthlyDonationAmount) >= 1000
            && in_array($this->isSocialMediaActive, ['yes', 'no'], true)
            && count($this->monthlyPaymentReminderMethods) > 0;
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.child-supporters.sponsor-registration');
    }
}
