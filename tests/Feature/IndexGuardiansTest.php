<?php

namespace Tests\Feature;

use App\Livewire\Guardians\IndexGuardians;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\User;
use App\Queries\Guardians\ExpandedGuardianPeopleQuery;
use App\Queries\Guardians\GuardianHouseholdStatsQuery;
use App\Services\Guardians\SoftDeleteGuardianFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexGuardiansTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardians_list_does_not_eager_load_people_for_every_row(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 710001,
            'first_name' => 'Guardian',
            'last_name' => 'List',
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P710001',
            'national_id' => '2234567890',
            'first_name' => 'Child',
            'last_name' => 'List',
        ]);

        Livewire::test(IndexGuardians::class)
            ->assertViewHas('totalGuardians', 1)
            ->tap(function ($component) {
                $guardians = $component->instance()->guardians;
                $this->assertCount(1, $guardians);
                $this->assertFalse($guardians->first()->relationLoaded('people'));
                $this->assertSame(1, $guardians->first()->people_count);
            });
    }

    public function test_expanded_guardian_people_loads_only_selected_guardian_people(): void
    {
        $this->actingAs($this->manager());

        $selectedGuardian = Guardian::query()->create([
            'guardian_code' => 710002,
            'first_name' => 'Selected',
            'last_name' => 'Guardian',
        ]);
        $otherGuardian = Guardian::query()->create([
            'guardian_code' => 710003,
            'first_name' => 'Other',
            'last_name' => 'Guardian',
        ]);

        Person::query()->create([
            'guardian_id' => $otherGuardian->id,
            'person_code' => 'P710099',
            'national_id' => '3234567899',
            'first_name' => 'Other',
            'last_name' => 'Child',
        ]);
        $first = Person::query()->create([
            'guardian_id' => $selectedGuardian->id,
            'person_code' => 'P710002',
            'national_id' => '3234567890',
            'first_name' => 'First',
            'last_name' => 'Child',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $latest = Person::query()->create([
            'guardian_id' => $selectedGuardian->id,
            'person_code' => 'P710003',
            'national_id' => '3234567891',
            'first_name' => 'Latest',
            'last_name' => 'Child',
            'created_at' => '2026-01-02 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);

        Livewire::test(IndexGuardians::class)
            ->call('toggleGuardian', $selectedGuardian->id)
            ->tap(function ($component) use ($latest, $first) {
                $people = $component->instance()->expandedGuardianPeople;

                $this->assertEqualsCanonicalizing([$latest->id, $first->id], $people->pluck('id')->all());
            });
    }

    public function test_expanded_guardian_people_query_returns_empty_collection_without_valid_guardian(): void
    {
        $query = app(ExpandedGuardianPeopleQuery::class);

        $this->assertTrue($query->get(null)->isEmpty());
        $this->assertTrue($query->get(999999)->isEmpty());
    }

    public function test_guardians_list_does_not_auto_refresh_stats_on_load(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(IndexGuardians::class)
            ->assertDontSee('wire:init="refreshStatsOnLoad"', false);
    }

    public function test_manual_refresh_updates_stats_and_dispatches_toast(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 710004,
            'first_name' => 'Manual',
            'last_name' => 'Refresh',
            'children_count' => 1,
            'children_in_house' => 0,
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P710004',
            'national_id' => '4234567890',
            'first_name' => 'Child',
            'last_name' => 'Refresh',
        ]);

        Livewire::test(IndexGuardians::class)
            ->call('refreshStats')
            ->assertDispatched('guardian-stats-refreshed');

        $this->assertSame(2, $guardian->refresh()->children_in_house);
    }

    public function test_guardian_household_stats_query_groups_by_household_size(): void
    {
        Guardian::query()->create([
            'guardian_code' => 710005,
            'national_code' => '1000000001',
            'first_name' => 'Small',
            'last_name' => 'Family',
            'children_in_house' => 2,
        ]);
        Guardian::query()->create([
            'guardian_code' => 710006,
            'national_code' => '1000000002',
            'first_name' => 'Large',
            'last_name' => 'Family',
            'children_in_house' => 4,
        ]);
        Guardian::query()->create([
            'guardian_code' => 710007,
            'national_code' => null,
            'first_name' => 'NoCode',
            'last_name' => 'Family',
            'children_in_house' => 4,
        ]);

        $stats = app(GuardianHouseholdStatsQuery::class)->byHouseholdSize()->all();

        $this->assertSame([
            [
                'household_size' => 2,
                'households_count' => 1,
                'national_codes' => ['1000000001'],
            ],
            [
                'household_size' => 4,
                'households_count' => 2,
                'national_codes' => ['1000000002'],
            ],
        ], $stats);
    }

    public function test_guardian_household_stats_query_groups_by_coverage_count(): void
    {
        Guardian::query()->create([
            'guardian_code' => 710008,
            'national_code' => '2000000001',
            'first_name' => 'One',
            'last_name' => 'Coverage',
            'children_count' => 1,
        ]);
        Guardian::query()->create([
            'guardian_code' => 710009,
            'national_code' => '2000000002',
            'first_name' => 'Three',
            'last_name' => 'Coverage',
            'children_count' => 3,
        ]);

        $stats = app(GuardianHouseholdStatsQuery::class)->byCoverageCount()->all();

        $this->assertSame([
            [
                'coverage_count' => 1,
                'households_count' => 1,
                'national_codes' => ['2000000001'],
            ],
            [
                'coverage_count' => 3,
                'households_count' => 1,
                'national_codes' => ['2000000002'],
            ],
        ], $stats);
    }

    public function test_household_stats_view_data_loads_only_when_modal_is_open(): void
    {
        $this->actingAs($this->manager());

        Guardian::query()->create([
            'guardian_code' => 710010,
            'national_code' => '3000000001',
            'first_name' => 'Modal',
            'last_name' => 'Stats',
            'children_count' => 2,
            'children_in_house' => 3,
        ]);

        Livewire::test(IndexGuardians::class)
            ->assertViewHas('householdSizeStats', fn ($stats) => $stats->isEmpty())
            ->assertViewHas('coverageCountStats', fn ($stats) => $stats->isEmpty())
            ->call('showHouseholdSizeDetails')
            ->assertViewHas('householdSizeStats', fn ($stats) => $stats->pluck('household_size')->contains(3))
            ->assertViewHas('coverageCountStats', fn ($stats) => $stats->pluck('coverage_count')->contains(2));
    }

    public function test_guardians_list_searches_across_default_fields(): void
    {
        $this->actingAs($this->manager());

        $matchingGuardian = Guardian::query()->create([
            'guardian_code' => 710011,
            'national_code' => '1111111111',
            'first_name' => 'Matching',
            'last_name' => 'Guardian',
            'guardian_phone_number' => '09121111111',
        ]);
        Guardian::query()->create([
            'guardian_code' => 710012,
            'national_code' => '2222222222',
            'first_name' => 'Other',
            'last_name' => 'Guardian',
            'guardian_phone_number' => '09122222222',
        ]);

        Livewire::test(IndexGuardians::class)
            ->set('search', 'Matching')
            ->tap(function ($component) use ($matchingGuardian) {
                $guardians = $component->instance()->guardians;

                $this->assertSame([$matchingGuardian->id], $guardians->pluck('id')->all());
            });
    }

    public function test_guardians_list_searches_by_selected_field(): void
    {
        $this->actingAs($this->manager());

        $matchingGuardian = Guardian::query()->create([
            'guardian_code' => 710013,
            'national_code' => '3333333333',
            'first_name' => 'Phone',
            'last_name' => 'Match',
            'guardian_phone_number' => '09123333333',
        ]);
        Guardian::query()->create([
            'guardian_code' => 710014,
            'national_code' => '4444444444',
            'first_name' => 'Phone',
            'last_name' => 'NameOnly',
            'guardian_phone_number' => '09124444444',
        ]);

        Livewire::test(IndexGuardians::class)
            ->set('searchField', 'mobile')
            ->set('search', '333333')
            ->tap(function ($component) use ($matchingGuardian) {
                $guardians = $component->instance()->guardians;

                $this->assertSame([$matchingGuardian->id], $guardians->pluck('id')->all());
            });
    }

    public function test_soft_delete_guardian_family_service_deletes_guardian_and_related_people(): void
    {
        $guardian = Guardian::query()->create([
            'guardian_code' => 710015,
            'first_name' => 'Service',
            'last_name' => 'Delete',
        ]);
        $firstPerson = $this->person($guardian, 'P710015', '5234567890');
        $secondPerson = $this->person($guardian, 'P710016', '5234567891');

        app(SoftDeleteGuardianFamily::class)->handle($guardian, 'Family moved out');

        $this->assertSoftDeleted('guardians', [
            'id' => $guardian->id,
        ]);
        $this->assertSoftDeleted('people', [
            'id' => $firstPerson->id,
        ]);
        $this->assertSoftDeleted('people', [
            'id' => $secondPerson->id,
        ]);

        $this->assertSame(
            'Family moved out',
            Guardian::withTrashed()->findOrFail($guardian->id)->deletion_reason
        );
        $this->assertSame(
            'Family moved out',
            Person::withTrashed()->findOrFail($firstPerson->id)->deletion_reason
        );
        $this->assertSame(
            'Family moved out',
            Person::withTrashed()->findOrFail($secondPerson->id)->deletion_reason
        );
    }

    public function test_delete_guardian_family_delegates_service_and_resets_delete_state(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 710017,
            'first_name' => 'Livewire',
            'last_name' => 'Delete',
        ]);
        $person = $this->person($guardian, 'P710017', '6234567890');

        Livewire::test(IndexGuardians::class)
            ->call('toggleGuardian', $guardian->id)
            ->call('openDeleteModal', $guardian->id)
            ->assertSet('showDeleteModal', true)
            ->set('deletionReason', 'Confirmed family block')
            ->call('deleteGuardianFamily')
            ->assertHasNoErrors()
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingGuardianId', null)
            ->assertSet('deletionReason', '')
            ->assertSet('expandedGuardianId', null);

        $this->assertSoftDeleted('guardians', [
            'id' => $guardian->id,
        ]);
        $this->assertSoftDeleted('people', [
            'id' => $person->id,
        ]);

        $this->assertSame(
            'Confirmed family block',
            Guardian::withTrashed()->findOrFail($guardian->id)->deletion_reason
        );
        $this->assertSame(
            'Confirmed family block',
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

    private function person(Guardian $guardian, string $personCode, string $nationalId): Person
    {
        return Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => $personCode,
            'national_id' => $nationalId,
            'first_name' => 'Child',
            'last_name' => 'Delete',
        ]);
    }
}
