<?php

namespace App\Livewire\ChildSupporters;

use App\Helpers\PersianNumber;
use App\Models\Person;
use App\Models\SponsorProfile;
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
        $this->resetErrorBag('beneficiaryCode');
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
