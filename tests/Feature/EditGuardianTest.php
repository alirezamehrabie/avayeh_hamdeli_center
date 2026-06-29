<?php

namespace Tests\Feature;

use App\Livewire\Guardians\EditGuardian;
use App\Models\Guardian;
use App\Models\Person;
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

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
