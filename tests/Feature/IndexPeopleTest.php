<?php

namespace Tests\Feature;

use App\Livewire\People\IndexPeople;
use App\Models\Person;
use App\Models\User;
use App\Queries\People\DeletingPersonDetailsQuery;
use App\Queries\People\PeopleIndexSearchQuery;
use App\Queries\People\SelectedPersonDetailsQuery;
use App\Queries\People\TrackingPersonDetailsQuery;
use App\Services\People\SoftDeletePerson;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function test_tracking_person_details_query_returns_null_without_valid_person(): void
    {
        $query = app(TrackingPersonDetailsQuery::class);

        $this->assertNull($query->find(null));
        $this->assertNull($query->find(999999));
    }

    public function test_tracking_person_details_query_loads_creator_and_updater(): void
    {
        $creator = User::factory()->create(['name' => 'creator-user']);
        $updater = User::factory()->create(['name' => 'updater-user']);
        $person = $this->person('P810004', '8100010004', [
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        $trackingPerson = app(TrackingPersonDetailsQuery::class)->find($person->id);

        $this->assertNotNull($trackingPerson);
        $this->assertTrue($trackingPerson->relationLoaded('creator'));
        $this->assertTrue($trackingPerson->relationLoaded('updater'));
        $this->assertSame('creator-user', $trackingPerson->creator->name);
        $this->assertSame('updater-user', $trackingPerson->updater->name);
    }

    public function test_deleting_person_details_query_returns_nullable_person_for_modal(): void
    {
        $query = app(DeletingPersonDetailsQuery::class);
        $person = $this->person('P810005', '8100010005');

        $this->assertNull($query->find(null));
        $this->assertNull($query->find(999999));
        $this->assertTrue($query->find($person->id)->is($person));
        $this->assertTrue($query->findOrFail($person->id)->is($person));
    }

    public function test_deleting_person_details_query_fails_for_missing_required_person(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(DeletingPersonDetailsQuery::class)->findOrFail(999999);
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

    public function test_soft_delete_person_service_can_delete_by_id(): void
    {
        $person = $this->person('P810006', '8100010006');

        app(SoftDeletePerson::class)->handleById($person->id, 'Confirmed duplicate');

        $this->assertSoftDeleted('people', [
            'id' => $person->id,
        ]);

        $this->assertSame(
            'Confirmed duplicate',
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

    public function test_full_name_search_matches_second_name_token_for_longer_terms(): void
    {
        $target = $this->person('P810007', '8100010007', [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
        ]);
        $this->person('P810008', '8100010008', [
            'first_name' => 'رضا',
            'last_name' => 'کاظمی',
        ]);

        $results = app(PeopleIndexSearchQuery::class)
            ->applyTo(Person::query(), 'رضایی', 'full_name')
            ->pluck('id');

        $this->assertTrue($results->contains($target->id));
        $this->assertCount(1, $results);
    }

    public function test_all_search_matches_longer_name_fragment_inside_full_name(): void
    {
        $target = $this->person('P810009', '8100010009', [
            'first_name' => 'محمدحسین',
            'last_name' => 'احمدی',
        ]);
        $this->person('P810010', '8100010010', [
            'first_name' => 'محمد',
            'last_name' => 'حسینی',
        ]);

        $results = app(PeopleIndexSearchQuery::class)
            ->applyTo(Person::query(), 'حسین', 'all')
            ->pluck('id');

        $this->assertTrue($results->contains($target->id));
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function person(string $personCode, string $nationalId, array $attributes = []): Person
    {
        return Person::query()->create(array_merge([
            'person_code' => $personCode,
            'national_id' => $nationalId,
            'first_name' => 'Test',
            'last_name' => 'Person',
        ], $attributes));
    }
}
