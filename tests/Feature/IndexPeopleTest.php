<?php

namespace Tests\Feature;

use App\Livewire\People\IndexPeople;
use App\Models\Person;
use App\Models\User;
use App\Services\People\SoftDeletePerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexPeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_person_service_stores_reason_and_soft_deletes_person(): void
    {
        $person = $this->person('P810001', '8100010001');

        app(SoftDeletePerson::class)->handle($person, 'Duplicate registration');

        $this->assertSoftDeleted('people', [
            'id' => $person->id,
        ]);

        $this->assertSame(
            'Duplicate registration',
            Person::withTrashed()->findOrFail($person->id)->deletion_reason
        );
    }

    public function test_confirm_delete_person_delegates_soft_delete_and_closes_modal(): void
    {
        $this->actingAs($this->manager());
        $person = $this->person('P810002', '8100010002');

        Livewire::test(IndexPeople::class)
            ->call('openDeleteModal', $person->id)
            ->assertSet('showDeleteModal', true)
            ->set('deletionReason', 'No longer eligible')
            ->call('confirmDeletePerson')
            ->assertHasNoErrors()
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingPersonId', null)
            ->assertSet('deletionReason', '');

        $this->assertSoftDeleted('people', [
            'id' => $person->id,
        ]);

        $this->assertSame(
            'No longer eligible',
            Person::withTrashed()->findOrFail($person->id)->deletion_reason
        );
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function person(string $personCode, string $nationalId): Person
    {
        return Person::query()->create([
            'person_code' => $personCode,
            'national_id' => $nationalId,
            'first_name' => 'Test',
            'last_name' => 'Person',
        ]);
    }
}
