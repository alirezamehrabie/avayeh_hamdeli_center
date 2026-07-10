<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardHome;
use App\Livewire\People\IncompleteCasesQueue;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class IncompleteCasesQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_shows_incomplete_people_and_hides_complete_people(): void
    {
        $this->actingAs($this->admin());

        $completePerson = $this->completePerson([
            'person_code' => '16001',
        ]);

        $missingGuardian = $this->person([
            'person_code' => '16002',
            'guardian_id' => null,
            'national_id' => '1234567891',
        ]);

        $missingResidence = $this->personWithGuardianAndSocialWorker([
            'person_code' => '16003',
            'national_id' => '1234567892',
        ]);
        DB::table('support_coverages')->insert([
            'person_id' => $missingResidence->id,
            'active_status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('needs_levels')->insert([
            'person_id' => $missingResidence->id,
            'need_level_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(IncompleteCasesQueue::class)
            ->assertSee('16002')
            ->assertSee('16003')
            ->assertDontSee($completePerson->person_code);
    }

    public function test_guardian_linked_residence_counts_as_complete_residence_coverage(): void
    {
        $this->actingAs($this->admin());

        $person = $this->personWithGuardianAndSocialWorker([
            'person_code' => '16004',
            'national_id' => '1234567893',
        ]);

        DB::table('residences')->insert([
            'guardian_id' => $person->guardian_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_coverages')->insert([
            'person_id' => $person->id,
            'active_status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('needs_levels')->insert([
            'person_id' => $person->id,
            'need_level_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(IncompleteCasesQueue::class)
            ->assertDontSee('residences.person_id / residences.guardian_id')
            ->assertDontSee('16004');
    }

    public function test_queue_assigns_critical_severity_to_missing_guardian_case(): void
    {
        $this->actingAs($this->admin());

        $person = $this->person([
            'guardian_id' => null,
            'national_id' => '2234567891',
        ]);

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();
        $severity = $component->severityFor($person->fresh());

        $this->assertSame('critical', $severity['key']);
    }

    public function test_queue_filters_by_reason_and_severity(): void
    {
        $this->actingAs($this->admin());

        $criticalPerson = $this->person([
            'person_code' => '16010',
            'guardian_id' => null,
            'national_id' => '3234567891',
        ]);

        $mediumPerson = $this->completePerson([
            'person_code' => '16011',
            'phone_number' => null,
        ]);
        $mediumPerson->guardian()->update(['guardian_phone_number' => null]);

        $component = Livewire::test(IncompleteCasesQueue::class)
            ->set('selectedReason', 'missing_contact_path')
            ->set('selectedSeverity', 'medium')
            ->assertSee('16011')
            ->assertDontSee('16010');

        $this->assertSame(1, $component->instance()->getSummaryProperty()['filtered_count']);
    }

    public function test_beneficiary_case_file_context_survives_mount_from_url(): void
    {
        $this->actingAs($this->admin());
        $person = $this->person();

        Livewire::withQueryParams([
            'section' => 'beneficiary-case-file',
            'id' => $person->id,
        ])
            ->test(DashboardHome::class)
            ->assertSet('activeSection', 'beneficiary-case-file')
            ->assertSet('sectionContextId', $person->id)
            ->assertSet('caseFilePersonId', $person->id);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function person(array $overrides = []): Person
    {
        return Person::query()->create(array_merge([
            'first_name' => 'Queue',
            'last_name' => 'Person',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(15000, 99999),
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ], $overrides));
    }

    private function personWithGuardianAndSocialWorker(array $overrides = []): Person
    {
        $socialWorker = SocialWorker::withoutGlobalScope('active')->create([
            'worker_code' => ((int) (SocialWorker::withoutGlobalScope('active')->max('worker_code') ?? 9)) + 1,
            'first_name' => 'Worker',
            'last_name' => 'Queue',
            'is_active' => true,
        ]);

        $guardian = Guardian::query()->create([
            'guardian_code' => random_int(100000, 999999),
            'first_name' => 'Guardian',
            'last_name' => 'Queue',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_phone_number' => '09120000000',
            'social_worker_id' => $socialWorker->id,
        ]);

        return $this->person(array_merge([
            'guardian_id' => $guardian->id,
            'phone_number' => '09121111111',
        ], $overrides));
    }

    private function completePerson(array $overrides = []): Person
    {
        $person = $this->personWithGuardianAndSocialWorker($overrides);

        DB::table('residences')->insert([
            'guardian_id' => $person->guardian_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_coverages')->insert([
            'person_id' => $person->id,
            'active_status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('needs_levels')->insert([
            'person_id' => $person->id,
            'need_level_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $person->fresh();
    }
}
