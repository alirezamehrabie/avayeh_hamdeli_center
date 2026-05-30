<?php

namespace Tests\Feature;

use App\Livewire\People\AdvancedFilterBuilder;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdvancedFilterBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_city_filter_counts_all_beneficiaries_in_a_household(): void
    {
        $user = User::factory()->create();

        $guardian = Guardian::create([
            'guardian_code' => 1001,
            'first_name' => 'سرپرست',
            'last_name' => 'خانواده',
        ]);

        $firstBeneficiary = Person::create([
            'person_code' => '10000001',
            'national_id' => '1234567890',
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'guardian_id' => $guardian->id,
            'gender' => 'male',
            'role' => 'child',
        ]);

        $secondBeneficiary = Person::create([
            'person_code' => '10000002',
            'national_id' => '1234567891',
            'first_name' => 'زهرا',
            'last_name' => 'رضایی',
            'guardian_id' => $guardian->id,
            'gender' => 'female',
            'role' => 'child',
        ]);

        Residence::create([
            'guardian_id' => $guardian->id,
            'person_id' => $secondBeneficiary->id,
            'is_local_to_city' => true,
        ]);

        Livewire::actingAs($user)
            ->test(AdvancedFilterBuilder::class)
            ->set('filters', [
                ['field' => 'is_local_to_city', 'type' => 'select', 'value' => '1'],
            ])
            ->assertSee($firstBeneficiary->full_name)
            ->assertSee($secondBeneficiary->full_name)
            ->assertSee('2');
    }

    public function test_guardian_beneficiary_with_code_column_can_be_selected(): void
    {
        $user = User::factory()->create();

        $guardian = Guardian::create([
            'guardian_code' => 1001,
            'first_name' => 'سرپرست',
            'last_name' => 'خانواده',
        ]);

        Person::create([
            'person_code' => '10000001',
            'national_id' => '1234567890',
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'guardian_id' => $guardian->id,
            'gender' => 'male',
            'role' => 'child',
        ]);

        Livewire::actingAs($user)
            ->test(AdvancedFilterBuilder::class)
            ->set('visibleColumns', ['guardian_beneficiary_with_code'])
            ->assertSee('سرپرست و کد خانوار')
            ->assertSee('سرپرست خانواده - کد: 1001');
    }

    public function test_guardian_household_column_sort_groups_family_members_together(): void
    {
        $user = User::factory()->create();

        $secondGuardian = Guardian::create([
            'guardian_code' => 1002,
            'first_name' => 'خانواده',
            'last_name' => 'ب',
        ]);

        Person::create([
            'person_code' => '10000003',
            'national_id' => '1234567892',
            'first_name' => 'مریم',
            'last_name' => 'ب',
            'guardian_id' => $secondGuardian->id,
            'gender' => 'female',
            'role' => 'child',
        ]);

        $firstGuardian = Guardian::create([
            'guardian_code' => 1001,
            'first_name' => 'خانواده',
            'last_name' => 'الف',
        ]);

        Person::create([
            'person_code' => '10000001',
            'national_id' => '1234567890',
            'first_name' => 'علی',
            'last_name' => 'الف',
            'guardian_id' => $firstGuardian->id,
            'gender' => 'male',
            'role' => 'child',
        ]);

        Person::create([
            'person_code' => '10000002',
            'national_id' => '1234567891',
            'first_name' => 'زهرا',
            'last_name' => 'الف',
            'guardian_id' => $firstGuardian->id,
            'gender' => 'female',
            'role' => 'child',
        ]);

        Livewire::actingAs($user)
            ->test(AdvancedFilterBuilder::class)
            ->set('visibleColumns', ['guardian_beneficiary_with_code', 'full_name'])
            ->call('toggleSort', 'guardian_beneficiary_with_code')
            ->assertSeeInOrder([
                'خانواده الف - کد: 1001',
                'علی الف',
                'خانواده الف - کد: 1001',
                'زهرا الف',
                'خانواده ب - کد: 1002',
                'مریم ب',
            ]);
    }

    public function test_guardian_household_column_filter_limits_results_by_household(): void
    {
        $user = User::factory()->create();

        $matchingGuardian = Guardian::create([
            'guardian_code' => 1001,
            'first_name' => 'خانواده',
            'last_name' => 'الف',
        ]);

        $matchingPerson = Person::create([
            'person_code' => '10000001',
            'national_id' => '1234567890',
            'first_name' => 'علی',
            'last_name' => 'الف',
            'guardian_id' => $matchingGuardian->id,
            'gender' => 'male',
            'role' => 'child',
        ]);

        $otherGuardian = Guardian::create([
            'guardian_code' => 1002,
            'first_name' => 'خانواده',
            'last_name' => 'ب',
        ]);

        $otherPerson = Person::create([
            'person_code' => '10000002',
            'national_id' => '1234567891',
            'first_name' => 'مریم',
            'last_name' => 'ب',
            'guardian_id' => $otherGuardian->id,
            'gender' => 'female',
            'role' => 'child',
        ]);

        Livewire::actingAs($user)
            ->test(AdvancedFilterBuilder::class)
            ->set('visibleColumns', ['guardian_beneficiary_with_code', 'full_name'])
            ->set('columnFilters.guardian_beneficiary_with_code', '1001')
            ->assertSee($matchingPerson->full_name)
            ->assertDontSee($otherPerson->full_name)
            ->assertSee('خانواده الف - کد: 1001');
    }
}
