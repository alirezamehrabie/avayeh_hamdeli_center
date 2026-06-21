<?php

namespace Tests\Feature;

use App\Livewire\ChildSupporters\SponsorList;
use App\Livewire\ChildSupporters\SponsorRegistration;
use App\Models\Person;
use App\Models\SponsorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChildSupporterRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_generates_unique_supporter_codes(): void
    {
        $this->actingAs($this->manager());

        $this->registerSponsor('09120000001', 'Ali');
        $this->registerSponsor('09120000002', 'Sara');

        $this->assertDatabaseHas('sponsor_profiles', [
            'supporter_code' => 'CS-0001',
        ]);
        $this->assertDatabaseHas('sponsor_profiles', [
            'supporter_code' => 'CS-0002',
        ]);
    }

    public function test_registration_assigns_beneficiaries_by_person_code(): void
    {
        $this->actingAs($this->manager());
        $beneficiary = $this->child('98000', 'Child', 'One');

        Livewire::test(SponsorRegistration::class)
            ->set('firstName', 'Ali')
            ->set('lastName', 'Supporter')
            ->set('monthlyDonationAmount', '100000')
            ->set('mobile', '09120000001')
            ->set('monthlyPaymentReminderMethods', [SponsorProfile::REMINDER_SMS])
            ->set('isSocialMediaActive', 'yes')
            ->set('beneficiaryCode', $beneficiary->person_code)
            ->call('addBeneficiary')
            ->assertSet('assignedBeneficiaries.0.person_code', '98000')
            ->call('save');

        $profile = SponsorProfile::query()->firstOrFail();

        $this->assertDatabaseHas('person_sponsor_profile', [
            'person_id' => $beneficiary->id,
            'sponsor_profile_id' => $profile->id,
        ]);
    }

    public function test_lookup_shows_existing_supporters_before_assignment(): void
    {
        $this->actingAs($this->manager());
        $beneficiary = $this->child('98001', 'Child', 'Two');
        $existingProfile = $this->sponsorProfile('09120000009', 'Existing', 'Supporter');
        $existingProfile->beneficiaries()->attach($beneficiary->id);

        Livewire::test(SponsorRegistration::class)
            ->set('beneficiaryCode', $beneficiary->person_code)
            ->call('lookupBeneficiary')
            ->assertSet('beneficiaryPreview.supporters_count', 1)
            ->assertSet('beneficiaryPreview.supporters.0.supporter_code', 'CS-0001')
            ->assertSet('beneficiaryPreview.supporters.0.full_name', 'Existing Supporter');
    }

    public function test_non_child_beneficiary_code_is_rejected(): void
    {
        $this->actingAs($this->manager());
        Person::query()->create([
            'person_code' => '98002',
            'national_id' => '1000000002',
            'first_name' => 'Guardian',
            'last_name' => 'Person',
            'role' => 'guardian',
        ]);

        Livewire::test(SponsorRegistration::class)
            ->set('beneficiaryCode', '98002')
            ->call('addBeneficiary')
            ->assertHasErrors(['beneficiaryCode']);
    }

    public function test_sponsor_list_can_add_and_remove_assignments(): void
    {
        $this->actingAs($this->manager());
        $profile = $this->sponsorProfile('09120000010', 'List', 'Supporter');
        $beneficiary = $this->child('98003', 'Child', 'Three');

        Livewire::test(SponsorList::class)
            ->call('showDetails', $profile->id)
            ->set('beneficiaryCode', $beneficiary->person_code)
            ->call('addBeneficiaryToSelectedSponsor')
            ->assertSet('selectedSponsor.beneficiaries.0.person_code', '98003')
            ->call('removeBeneficiaryFromSelectedSponsor', $beneficiary->id)
            ->assertSet('selectedSponsor.beneficiaries', []);

        $this->assertDatabaseMissing('person_sponsor_profile', [
            'person_id' => $beneficiary->id,
            'sponsor_profile_id' => $profile->id,
        ]);
    }

    public function test_sponsor_list_can_edit_supporter_information(): void
    {
        $this->actingAs($this->manager());
        $profile = $this->sponsorProfile('09120000011', 'Old', 'Supporter');

        Livewire::test(SponsorList::class)
            ->call('editSponsor', $profile->id)
            ->assertSet('isEditing', true)
            ->set('editFirstName', 'Updated')
            ->set('editLastName', 'Person')
            ->set('editMobile', '09120000012')
            ->set('editMonthlyDonationAmount', '250000')
            ->set('editChildPreferences', 'Updated preference')
            ->set('editMonthlyPaymentReminderMethods', [SponsorProfile::REMINDER_SMS, SponsorProfile::REMINDER_PHONE])
            ->set('editIsSocialMediaActive', 'no')
            ->call('updateSponsor')
            ->assertSet('isEditing', false)
            ->assertSet('selectedSponsor.fullName', 'Updated Person');

        $profile->refresh();
        $profile->user->refresh();

        $this->assertSame('CS-0001', $profile->supporter_code);
        $this->assertSame(250000, (int) $profile->monthly_donation_amount);
        $this->assertSame('Updated preference', $profile->child_preferences);
        $this->assertFalse((bool) $profile->is_social_media_active);
        $this->assertSame(['sms', 'phone'], $profile->monthly_payment_reminder_methods);
        $this->assertSame('Updated', $profile->user->first_name);
        $this->assertSame('Person', $profile->user->last_name);
        $this->assertSame('09120000012', $profile->user->mobile);
        $this->assertSame('09120000012', $profile->user->name);
    }

    public function test_sponsor_edit_rejects_mobile_used_by_another_user(): void
    {
        $this->actingAs($this->manager());
        $profile = $this->sponsorProfile('09120000013', 'First', 'Supporter');
        $this->sponsorProfile('09120000014', 'Second', 'Supporter');

        Livewire::test(SponsorList::class)
            ->call('editSponsor', $profile->id)
            ->set('editMobile', '09120000014')
            ->call('updateSponsor')
            ->assertHasErrors(['editMobile']);
    }

    private function registerSponsor(string $mobile, string $firstName): void
    {
        Livewire::test(SponsorRegistration::class)
            ->set('firstName', $firstName)
            ->set('lastName', 'Supporter')
            ->set('monthlyDonationAmount', '100000')
            ->set('mobile', $mobile)
            ->set('monthlyPaymentReminderMethods', [SponsorProfile::REMINDER_SMS])
            ->set('isSocialMediaActive', 'yes')
            ->call('save');
    }

    private function sponsorProfile(string $mobile, string $firstName, string $lastName): SponsorProfile
    {
        return User::registerSponsorAccount([
            'mobile' => $mobile,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'monthly_donation_amount' => 100000,
            'monthly_payment_reminder_methods' => [SponsorProfile::REMINDER_SMS],
            'is_social_media_active' => true,
            'created_by' => auth()->id(),
        ])->sponsorProfile()->firstOrFail();
    }

    private function child(string $personCode, string $firstName, string $lastName): Person
    {
        return Person::query()->create([
            'person_code' => $personCode,
            'national_id' => str_pad((string) ((int) $personCode + 1000000000), 10, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'child',
        ]);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
