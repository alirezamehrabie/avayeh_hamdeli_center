<?php

namespace App\Livewire\ChildSupporters;

use App\Helpers\PersianNumber;
use App\Models\Person;
use App\Models\SponsorProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.child-supporter')]
class SponsorList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sort = 'latest';

    #[Url(as: 'size', history: true)]
    public int $perPage = 10;

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
        $this->sort = in_array($this->sort, $this->sortOptions(), true) ? $this->sort : 'latest';
        $this->perPage = in_array($this->perPage, $this->perPageOptions(), true) ? $this->perPage : 10;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(string $value): void
    {
        if (! in_array($value, $this->sortOptions(), true)) {
            $this->sort = 'latest';
        }

        $this->resetPage();
    }

    public function updatedPerPage(int|string $value): void
    {
        $perPage = (int) $value;
        $this->perPage = in_array($perPage, $this->perPageOptions(), true) ? $perPage : 10;
        $this->resetPage();
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
        $this->resetErrorBag();
    }

    public function render()
    {
        $sponsors = SponsorProfile::query()
            ->with([
                'user:id,first_name,last_name,mobile,name',
            ])
            ->withCount('beneficiaries')
            ->whereHas('user')
            ->when($this->search !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';

                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('supporter_code', 'like', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('first_name', 'like', $search)
                                ->orWhere('last_name', 'like', $search)
                                ->orWhere('mobile', 'like', $search)
                                ->orWhere('name', 'like', $search);
                        });
                });
            });

        $this->applySorting($sponsors);

        $sponsors = $sponsors->paginate($this->perPage);

        return view('livewire.child-supporters.sponsor-list', [
            'sponsors' => $sponsors,
            'selectedSponsor' => $this->selectedSponsor,
            'sortOptions' => $this->sortOptions(),
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }

    private function formatSponsorDetails(SponsorProfile $sponsor): array
    {
        return [
            'id' => $sponsor->id,
            'supporterCode' => $sponsor->supporter_code ?: '-',
            'fullName' => trim(($sponsor->user?->first_name ?? '').' '.($sponsor->user?->last_name ?? '')) ?: '-',
            'mobile' => $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-'),
            'monthlyDonationAmount' => $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)).' ریال',
            'monthlyDonationAmountInWords' => $this->donationAmountInTomanWords((int) $sponsor->monthly_donation_amount),
            'reminderMethods' => collect((array) $sponsor->monthly_payment_reminder_methods)
                ->map(fn (string $method): string => SponsorProfile::reminderMethodOptions()[$method] ?? $method)
                ->values()
                ->all(),
            'childPreferences' => $sponsor->child_preferences ?: '-',
            'beneficiariesCount' => $sponsor->beneficiaries->count(),
            'beneficiaries' => $sponsor->beneficiaries
                ->map(fn (Person $beneficiary): array => [
                    'id' => $beneficiary->id,
                    'person_code' => $beneficiary->person_code,
                    'full_name' => trim($beneficiary->first_name.' '.$beneficiary->last_name),
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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<SponsorProfile>  $query
     */
    private function applySorting($query): void
    {
        match ($this->sort) {
            'donation_desc' => $query->orderByDesc('monthly_donation_amount')->latest('id'),
            'donation_asc' => $query->orderBy('monthly_donation_amount')->latest('id'),
            'beneficiaries_desc' => $query->orderByDesc('beneficiaries_count')->latest('id'),
            'beneficiaries_asc' => $query->orderBy('beneficiaries_count')->latest('id'),
            'name_asc' => $query
                ->join('users', 'users.id', '=', 'sponsor_profiles.user_id')
                ->select('sponsor_profiles.*')
                ->orderBy('users.first_name')
                ->orderBy('users.last_name')
                ->latest('sponsor_profiles.id'),
            default => $query->latest(),
        };
    }

    /**
     * @return array<int, int>
     */
    private function perPageOptions(): array
    {
        return [10, 25, 50];
    }

    /**
     * @return array<int, string>
     */
    private function sortOptions(): array
    {
        return [
            'latest',
            'name_asc',
            'donation_desc',
            'donation_asc',
            'beneficiaries_desc',
            'beneficiaries_asc',
        ];
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
                'full_name' => trim(($profile->user?->first_name ?? '').' '.($profile->user?->last_name ?? '')) ?: ($profile->user?->name ?: '-'),
            ])
            ->values()
            ->all();

        return [
            'id' => $beneficiary->id,
            'person_code' => $beneficiary->person_code,
            'full_name' => trim($beneficiary->first_name.' '.$beneficiary->last_name),
            'supporters_count' => count($supporters),
            'supporters' => $supporters,
        ];
    }
}
