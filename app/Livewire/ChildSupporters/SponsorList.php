<?php

namespace App\Livewire\ChildSupporters;

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

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorizeAccess();
    }

    public function showDetails(int $sponsorId): void
    {
        $this->authorizeAccess();

        $sponsor = SponsorProfile::query()
            ->with('user:id,first_name,last_name,mobile,name')
            ->findOrFail($sponsorId);

        $this->selectedSponsorId = $sponsor->id;
        $this->selectedSponsor = [
            'fullName' => trim(($sponsor->user?->first_name ?? '') . ' ' . ($sponsor->user?->last_name ?? '')) ?: '-',
            'mobile' => $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-'),
            'monthlyDonationAmount' => $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) . ' ریال',
            'reminderMethods' => collect((array) $sponsor->monthly_payment_reminder_methods)
                ->map(fn (string $method): string => SponsorProfile::reminderMethodOptions()[$method] ?? $method)
                ->values()
                ->all(),
            'childPreferences' => $sponsor->child_preferences ?: '-',
        ];
    }

    public function closeDetails(): void
    {
        $this->selectedSponsorId = null;
        $this->selectedSponsor = null;
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

    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->check()
            && (auth()->user()->can('access-child-supporter-panel') || auth()->user()->can('access-admin-panel')),
            403
        );
    }
}
