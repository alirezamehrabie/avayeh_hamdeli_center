<?php

namespace App\Livewire\ChildSupporters;

use App\Helpers\PersianNumber;
use App\Models\Person;
use App\Models\SponsorProfile;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.child-supporter')]
class SponsorList extends Component
{
    use WithPagination;

    public bool $embedded = false;
    public ?int $selectedSponsorId = null;
    public ?array $selectedSponsor = null;
    public string $beneficiaryCode = '';
    public ?array $beneficiaryPreview = null;
    public bool $isEditing = false;
    public string $editFirstName = '';
    public string $editLastName = '';
    public string $editMobile = '';
    public string $editMonthlyDonationAmount = '';
    public string $editChildPreferences = '';
    public array $editMonthlyPaymentReminderMethods = [];
    public ?string $editIsSocialMediaActive = null;

    public function persianNumber(mixed $value): string
    {
        return strtr((string) $value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
            ',' => '٬',
        ]);
    }

    public function donationAmountInTomanWords(int $rialAmount): string
    {
        return PersianNumber::rialToTomanWords($rialAmount);
    }

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorizeAccess();
    }

    public function showDetails(int $sponsorId): void
    {
        $this->authorizeAccess();

        $sponsor = SponsorProfile::query()
            ->with([
                'user:id,first_name,last_name,mobile,name',
                'beneficiaries:id,first_name,last_name,person_code,role',
            ])
            ->findOrFail($sponsorId);

        $this->selectedSponsorId = $sponsor->id;
        $this->selectedSponsor = $this->formatSponsorDetails($sponsor);
        $this->beneficiaryCode = '';
        $this->beneficiaryPreview = null;
        $this->isEditing = false;
        $this->resetErrorBag('beneficiaryCode');
    }

    public function editSponsor(int $sponsorId): void
    {
        $this->authorizeAccess();

        $sponsor = SponsorProfile::query()
            ->with([
                'user:id,first_name,last_name,mobile,name',
                'beneficiaries:id,first_name,last_name,person_code,role',
            ])
            ->findOrFail($sponsorId);

        $this->selectedSponsorId = $sponsor->id;
        $this->selectedSponsor = $this->formatSponsorDetails($sponsor);
        $this->isEditing = true;
        $this->beneficiaryCode = '';
        $this->beneficiaryPreview = null;
        $this->editFirstName = (string) ($sponsor->user?->first_name ?? '');
        $this->editLastName = (string) ($sponsor->user?->last_name ?? '');
        $this->editMobile = (string) ($sponsor->user?->mobile ?: $sponsor->user?->name ?: '');
        $this->editMonthlyDonationAmount = number_format((int) $sponsor->monthly_donation_amount);
        $this->editChildPreferences = (string) ($sponsor->child_preferences ?? '');
        $this->editMonthlyPaymentReminderMethods = array_values((array) $sponsor->monthly_payment_reminder_methods);
        $this->editIsSocialMediaActive = $sponsor->is_social_media_active ? 'yes' : 'no';
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->resetEditFields();
        $this->resetErrorBag();
    }

    public function updateSponsor(): void
    {
        $this->authorizeAccess();

        if (! $this->selectedSponsorId) {
            return;
        }

        $sponsor = SponsorProfile::query()
            ->with('user')
            ->findOrFail($this->selectedSponsorId);

        $validated = $this->validate($this->editRules($sponsor), $this->editMessages());
        $mobile = $this->normalizeMobile($validated['editMobile']);

        $sponsor->user?->update([
            'name' => $mobile,
            'first_name' => $this->normalizeNamePart($validated['editFirstName']),
            'last_name' => $this->normalizeNamePart($validated['editLastName']),
            'email' => $mobile . '@local.system',
            'mobile' => $mobile,
            'access_level' => User::ACCESS_LEVEL_CHILD_SUPPORTER,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $sponsor->update([
            'monthly_donation_amount' => (int) preg_replace('/\D+/', '', $validated['editMonthlyDonationAmount']),
            'child_preferences' => filled($validated['editChildPreferences'] ?? null) ? trim($validated['editChildPreferences']) : null,
            'monthly_payment_reminder_methods' => array_values($validated['editMonthlyPaymentReminderMethods']),
            'is_social_media_active' => $validated['editIsSocialMediaActive'] === 'yes',
        ]);

        $this->isEditing = false;
        $this->resetEditFields();
        $this->showDetails($sponsor->id);
        session()->flash('success', 'Supporter information updated successfully.');
    }

    public function lookupBeneficiary(): void
    {
        $this->authorizeAccess();
        $this->resetErrorBag('beneficiaryCode');

        $code = $this->normalizeBeneficiaryCode($this->beneficiaryCode);
        $this->beneficiaryCode = $code;
        $this->beneficiaryPreview = null;

        if ($code === '') {
            $this->addError('beneficiaryCode', 'کد مددجو را وارد کنید.');

            return;
        }

        $beneficiary = $this->findAssignableBeneficiary($code);

        if (! $beneficiary) {
            $this->addError('beneficiaryCode', 'مددجوی کودک با این کد پیدا نشد.');

            return;
        }

        $this->beneficiaryPreview = $this->formatBeneficiary($beneficiary);
    }

    public function addBeneficiaryToSelectedSponsor(): void
    {
        $this->authorizeAccess();

        if (! $this->selectedSponsorId) {
            return;
        }

        $this->lookupBeneficiary();

        if (! $this->beneficiaryPreview) {
            return;
        }

        $sponsor = SponsorProfile::query()->findOrFail($this->selectedSponsorId);
        $sponsor->beneficiaries()->syncWithoutDetaching([(int) $this->beneficiaryPreview['id']]);

        $this->showDetails($sponsor->id);
    }

    public function removeBeneficiaryFromSelectedSponsor(int $beneficiaryId): void
    {
        $this->authorizeAccess();

        if (! $this->selectedSponsorId) {
            return;
        }

        $sponsor = SponsorProfile::query()->findOrFail($this->selectedSponsorId);
        $sponsor->beneficiaries()->detach($beneficiaryId);

        $this->showDetails($sponsor->id);
    }

    public function closeDetails(): void
    {
        $this->selectedSponsorId = null;
        $this->selectedSponsor = null;
        $this->beneficiaryCode = '';
        $this->beneficiaryPreview = null;
        $this->isEditing = false;
        $this->resetEditFields();
        $this->resetErrorBag();
    }

    public function render()
    {
        $this->authorizeAccess();

        $sponsors = SponsorProfile::query()
            ->with('user')
            ->whereHas('user')
            ->latest()
            ->paginate(10);

        return view('livewire.child-supporters.sponsor-list', [
            'sponsors' => $sponsors,
            'selectedSponsor' => $this->selectedSponsor,
        ]);
    }

    private function formatSponsorDetails(SponsorProfile $sponsor): array
    {
        return [
            'id' => $sponsor->id,
            'supporterCode' => $sponsor->supporter_code ?: '-',
            'fullName' => trim(($sponsor->user?->first_name ?? '') . ' ' . ($sponsor->user?->last_name ?? '')) ?: '-',
            'mobile' => $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-'),
            'monthlyDonationAmount' => $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) . ' ریال',
            'monthlyDonationAmountInWords' => $this->donationAmountInTomanWords((int) $sponsor->monthly_donation_amount),
            'reminderMethods' => collect((array) $sponsor->monthly_payment_reminder_methods)
                ->map(fn (string $method): string => SponsorProfile::reminderMethodOptions()[$method] ?? $method)
                ->values()
                ->all(),
            'childPreferences' => $sponsor->child_preferences ?: '-',
            'beneficiaries' => $sponsor->beneficiaries
                ->map(fn (Person $beneficiary): array => [
                    'id' => $beneficiary->id,
                    'person_code' => $beneficiary->person_code,
                    'full_name' => trim($beneficiary->first_name . ' ' . $beneficiary->last_name),
                ])
                ->values()
                ->all(),
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

    private function editRules(SponsorProfile $sponsor): array
    {
        $userId = $sponsor->user_id;
        $reminderKeys = array_keys(SponsorProfile::reminderMethodOptions());

        return [
            'editFirstName' => ['required', 'string', 'min:2', 'max:100'],
            'editLastName' => ['required', 'string', 'min:2', 'max:100'],
            'editMobile' => [
                'required',
                'digits:11',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'name')->ignore($userId),
                Rule::unique('users', 'mobile')->ignore($userId),
            ],
            'editMonthlyDonationAmount' => [
                'required',
                'regex:/^[\d,\s]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $amount = (int) preg_replace('/\D+/', '', (string) $value);

                    if ($amount < 1000) {
                        $fail('Monthly donation amount is invalid.');
                    }
                },
            ],
            'editChildPreferences' => ['nullable', 'string', 'max:1000'],
            'editMonthlyPaymentReminderMethods' => ['required', 'array', 'min:1'],
            'editMonthlyPaymentReminderMethods.*' => ['string', Rule::in($reminderKeys)],
            'editIsSocialMediaActive' => ['required', Rule::in(['yes', 'no'])],
        ];
    }

    private function editMessages(): array
    {
        return [
            'editFirstName.required' => 'First name is required.',
            'editLastName.required' => 'Last name is required.',
            'editMobile.required' => 'Mobile number is required.',
            'editMobile.digits' => 'Mobile number must be exactly 11 digits.',
            'editMobile.regex' => 'Mobile number must start with 09.',
            'editMobile.unique' => 'This mobile number is already used by another account.',
            'editMonthlyDonationAmount.required' => 'Monthly donation amount is required.',
            'editMonthlyPaymentReminderMethods.required' => 'Select at least one reminder method.',
            'editMonthlyPaymentReminderMethods.min' => 'Select at least one reminder method.',
            'editIsSocialMediaActive.required' => 'Select social media status.',
        ];
    }

    private function resetEditFields(): void
    {
        $this->editFirstName = '';
        $this->editLastName = '';
        $this->editMobile = '';
        $this->editMonthlyDonationAmount = '';
        $this->editChildPreferences = '';
        $this->editMonthlyPaymentReminderMethods = [];
        $this->editIsSocialMediaActive = null;
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
}
