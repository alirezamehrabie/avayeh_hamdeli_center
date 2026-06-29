<?php

namespace Tests\Feature;

use App\Livewire\Guardians\EditGuardian;
use App\Livewire\Guardians\IndexGuardians;
use App\Models\Guardian;
use App\Models\BankInfo;
use App\Models\InsuranceType;
use App\Models\VehicleType;
use App\Models\Person;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditGuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_recalculates_household_size_instead_of_using_manual_state(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700001,
            'first_name' => 'Guardian',
            'last_name' => 'Household',
            'children_count' => 1,
            'children_in_house' => 1,
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P700001',
            'national_id' => '1234567890',
            'first_name' => 'Child',
            'last_name' => 'Household',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('children_in_house', 99)
            ->set('first_name', 'Updated')
            ->call('save')
            ->assertHasNoErrors();

        $guardian->refresh();

        $this->assertSame('Updated', $guardian->first_name);
        $this->assertSame(2, $guardian->children_in_house);
    }

    public function test_save_persists_extra_household_members_and_recalculates_household_size(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700002,
            'first_name' => 'Guardian',
            'last_name' => 'Extra',
            'children_count' => 1,
            'children_in_house' => 1,
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P700002',
            'national_id' => '1234567891',
            'first_name' => 'Child',
            'last_name' => 'Extra',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('new_extra_household_member_description', 'Grandmother')
            ->call('addExtraHouseholdMember')
            ->set('new_extra_household_member_description', 'Adult sibling')
            ->call('addExtraHouseholdMember')
            ->call('save')
            ->assertHasNoErrors();

        $guardian->refresh();

        $this->assertSame([
            ['description' => 'Grandmother'],
            ['description' => 'Adult sibling'],
        ], $guardian->extra_household_members);
        $this->assertSame(4, $guardian->children_in_house);
    }

    public function test_save_removes_blank_extra_household_member_rows(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700003,
            'first_name' => 'Guardian',
            'last_name' => 'Blank Extra',
            'children_count' => 0,
            'extra_household_members' => [
                ['description' => 'Existing member'],
            ],
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('extra_household_members', [
                ['description' => ' Existing member '],
                ['description' => '   '],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $guardian->refresh();

        $this->assertSame([
            ['description' => 'Existing member'],
        ], $guardian->extra_household_members);
        $this->assertSame(2, $guardian->children_in_house);
    }

    public function test_save_includes_pending_extra_household_member_input(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700004,
            'first_name' => 'Guardian',
            'last_name' => 'Pending Extra',
            'children_count' => 0,
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('new_extra_household_member_description', 'Pending member')
            ->call('save')
            ->assertHasNoErrors();

        $guardian->refresh();

        $this->assertSame([
            ['description' => 'Pending member'],
        ], $guardian->extra_household_members);
        $this->assertSame(2, $guardian->children_in_house);
    }

    public function test_save_stores_residence_money_amounts_with_single_scaling(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700005,
            'first_name' => 'Guardian',
            'last_name' => 'Money',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('deposit_amount', 12500000)
            ->set('monthly_rent', 3500000)
            ->call('save')
            ->assertHasNoErrors();

        $rawResidence = Residence::query()
            ->where('guardian_id', $guardian->id)
            ->toBase()
            ->first();

        $this->assertSame(125000, (int) $rawResidence->deposit_amount);
        $this->assertSame(35000, (int) $rawResidence->monthly_rent);

        $residence = Residence::query()->where('guardian_id', $guardian->id)->first();

        $this->assertSame(12500000, $residence->deposit_amount);
        $this->assertSame(3500000, $residence->monthly_rent);
    }

    public function test_save_normalizes_divorced_child_at_home_alias_to_canonical_value(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700006,
            'first_name' => 'Guardian',
            'last_name' => 'Divorce Alias',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('divorced_child_at_home', 'son')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Guardian::DIVORCED_CHILD_BOY, $guardian->refresh()->divorced_child_at_home);
    }

    public function test_save_stores_iban_placeholders_as_null(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700007,
            'first_name' => 'Guardian',
            'last_name' => 'Iban Placeholder',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->assertSet('sheba_number', 'IR')
            ->assertSet('subsidy_sheba_number', 'IR')
            ->call('save')
            ->assertHasNoErrors();

        $bankInfo = BankInfo::query()->where('guardian_id', $guardian->id)->firstOrFail();

        $this->assertNull($bankInfo->sheba_number);
        $this->assertNull($bankInfo->subsidy_sheba_number);
    }

    public function test_sheba_number_is_normalized_and_copied_to_subsidy_when_empty(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700008,
            'first_name' => 'Guardian',
            'last_name' => 'Iban Normalize',
        ]);

        $iban = 'IR'.str_repeat('1', 24);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('sheba_number', '  '.strtolower(substr($iban, 2)).'  ')
            ->assertSet('sheba_number', $iban)
            ->assertSet('subsidy_sheba_number', $iban)
            ->call('save')
            ->assertHasNoErrors();

        $bankInfo = BankInfo::query()->where('guardian_id', $guardian->id)->firstOrFail();

        $this->assertSame($iban, $bankInfo->sheba_number);
        $this->assertSame($iban, $bankInfo->subsidy_sheba_number);
    }

    public function test_invalid_subsidy_sheba_number_is_rejected(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700009,
            'first_name' => 'Guardian',
            'last_name' => 'Invalid Iban',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('subsidy_sheba_number', 'IR123')
            ->call('save')
            ->assertHasErrors(['subsidy_sheba_number']);
    }

    public function test_turning_off_parent_toggles_clears_dependent_state(): void
    {
        $this->actingAs($this->manager());

        $insuranceType = InsuranceType::query()->create(['name' => 'Basic Insurance']);
        $vehicleType = VehicleType::query()->create(['name' => 'Car']);
        $guardian = Guardian::query()->create([
            'guardian_code' => 700010,
            'first_name' => 'Guardian',
            'last_name' => 'Dependent State',
            'insurance_status' => true,
            'insurance_type_id' => $insuranceType->id,
            'any_family_employed' => true,
            'any_family_employed_description' => 'Part-time work',
            'has_vehicle' => true,
            'vehicle_type_id' => $vehicleType->id,
            'vehicle_ownership_type' => 'personal',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('insurance_status', '0')
            ->assertSet('insurance_type_id', null)
            ->set('any_family_employed', '0')
            ->assertSet('any_family_employed_description', null)
            ->set('has_vehicle', '0')
            ->assertSet('vehicle_type_id', null)
            ->assertSet('vehicle_ownership_type', null);
    }

    public function test_save_does_not_persist_stale_dependent_values_when_toggles_are_off(): void
    {
        $this->actingAs($this->manager());

        $insuranceType = InsuranceType::query()->create(['name' => 'Supplemental Insurance']);
        $vehicleType = VehicleType::query()->create(['name' => 'Motorcycle']);
        $guardian = Guardian::query()->create([
            'guardian_code' => 700011,
            'first_name' => 'Guardian',
            'last_name' => 'Stale Dependents',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian])
            ->set('insurance_status', '0')
            ->set('insurance_type_id', $insuranceType->id)
            ->set('any_family_employed', '0')
            ->set('any_family_employed_description', 'Should be removed')
            ->set('has_vehicle', '0')
            ->set('vehicle_type_id', $vehicleType->id)
            ->set('vehicle_ownership_type', 'personal')
            ->call('save')
            ->assertHasNoErrors();

        $guardian->refresh();

        $this->assertFalse($guardian->insurance_status);
        $this->assertNull($guardian->insurance_type_id);
        $this->assertFalse($guardian->any_family_employed);
        $this->assertNull($guardian->any_family_employed_description);
        $this->assertFalse($guardian->has_vehicle);
        $this->assertNull($guardian->vehicle_type_id);
        $this->assertNull($guardian->vehicle_ownership_type);
    }

    public function test_embedded_save_dispatches_guardians_list_success_event(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 700012,
            'first_name' => 'Guardian',
            'last_name' => 'Embedded Success',
        ]);

        Livewire::test(EditGuardian::class, ['guardian' => $guardian, 'embedded' => true])
            ->set('first_name', 'Updated Embedded')
            ->call('save')
            ->assertDispatched('open-dashboard-section', section: 'guardians-list')
            ->assertDispatched('guardians-list-success');
    }

    public function test_guardians_list_success_event_opens_toast(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(IndexGuardians::class)
            ->call('showSuccessMessage', 'Saved guardian')
            ->assertDispatched('guardians-list-toast', message: 'Saved guardian');
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
