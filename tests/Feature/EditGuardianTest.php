<?php

namespace Tests\Feature;

use App\Livewire\Guardians\EditGuardian;
use App\Models\Guardian;
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

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
