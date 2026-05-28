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
}
