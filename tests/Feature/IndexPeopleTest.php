<?php

namespace Tests\Feature;

use App\Livewire\People\IndexPeople;
use App\Models\Person;
use App\Models\User;
use App\Queries\People\SelectedPersonDetailsQuery;
use App\Services\People\SoftDeletePerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexPeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_person_details_query_returns_null_without_valid_person(): void
    {
        $query = app(SelectedPersonDetailsQuery::class);

        $this->assertNull($query->find(null));
        $this->assertNull($query->find(999999));
    }

    public function test_selected_person_details_query_loads_modal_relationships(): void
    {
        $person = $this->person('P810003', '8100010003');

        $selectedPerson = app(SelectedPersonDetailsQuery::class)->find($person->id);

        $this->assertNotNull($selectedPerson);
        $this->assertTrue($selectedPerson->relationLoaded('guardian'));
        $this->assertTrue($selectedPerson->relationLoaded('education'));
        $this->assertTrue($selectedPerson->relationLoaded('supportCoverage'));
        $this->assertTrue($selectedPerson->relationLoaded('disabilityType'));
        $this->assertTrue($selectedPerson->relationLoaded('familyStatus'));
        $this->assertTrue($selectedPerson->relationLoaded('skills'));
        $this->assertTrue($selectedPerson->relationLoaded('harmTypes'));
        $this->assertTrue($selectedPerson->relationLoaded('needsLevel'));
    }

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
