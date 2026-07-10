<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardHome;
use App\Livewire\People\IncompleteCasesQueue;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\SupportOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class IncompleteCasesQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_hides_catalog_complete_people_and_lists_people_with_field_level_defects(): void
    {
        $this->actingAs($this->admin());

        $completePerson = $this->createCatalogCompletePerson([
            'person_code' => '16001',
            'national_id' => '1234567890',
        ]);

        $incompletePerson = $this->createCatalogCompletePerson([
            'person_code' => '16002',
            'national_id' => '1234567891',
            'profile_photo' => null,
            'photo_id_card' => null,
            'client_case_history' => null,
        ]);

        Livewire::test(IncompleteCasesQueue::class)
            ->assertSee('16002')
            ->assertSee('profile_photo')
            ->assertSee('photo_id_card')
            ->assertSee('client_case_history')
            ->assertDontSee($completePerson->person_code);

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();
        $reasonKeys = $component->reasonsFor($incompletePerson->fresh())->pluck('key')->all();

        $this->assertSame(['profile_photo', 'photo_id_card', 'client_case_history'], $reasonKeys);
    }

    public function test_queue_respects_conditional_and_household_scoped_fields(): void
    {
        $this->actingAs($this->admin());

        $otherOrganization = SupportOrganization::query()->where('slug', 'other')->firstOrFail();

        $person = $this->createCatalogCompletePerson([
            'person_code' => '16003',
            'national_id' => '1234567892',
            'sadaat_status' => 'sadaat',
            'sadaat_relation_id' => null,
            'has_disability' => true,
            'disability_type_id' => null,
            'disability_description' => null,
        ], [
            'education' => [
                'is_studying' => false,
                'reason_for_not_studying' => 'dropped_out',
                'education_degree' => null,
            ],
            'familyStatus' => [
                'has_parent_disability' => true,
                'parent_disability_description' => null,
            ],
            'supportCoverage' => [
                'support_organization_id' => $otherOrganization->id,
                'other_organization_name' => null,
            ],
            'residenceOwner' => 'guardian',
            'contactOwner' => 'guardian',
            'removePersonContact' => true,
        ]);

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();
        $reasons = $component->reasonsFor($person->fresh());
        $reasonKeys = $reasons->pluck('key')->all();

        $this->assertContains('sadaat_relation_id', $reasonKeys);
        $this->assertContains('disability_type_id', $reasonKeys);
        $this->assertContains('disability_description', $reasonKeys);
        $this->assertContains('education_degree', $reasonKeys);
        $this->assertContains('parent_disability_description', $reasonKeys);
        $this->assertContains('other_organization_name', $reasonKeys);
        $this->assertNotContains('landline_phone', $reasonKeys);
        $this->assertNotContains('trusted_person_phone', $reasonKeys);
        $this->assertNotContains('residence_status_id', $reasonKeys);
        $this->assertNotContains('district_id', $reasonKeys);
        $this->assertNotContains('address', $reasonKeys);
    }

    public function test_queue_builds_expected_summary_and_filters_for_exact_catalog_fields(): void
    {
        $this->actingAs($this->admin());

        $criticalPerson = $this->createCatalogCompletePerson([
            'person_code' => '16010',
            'national_id' => '',
        ], [
            'guardian' => [
                'social_worker_id' => null,
            ],
        ]);

        $highPerson = $this->createCatalogCompletePerson([
            'person_code' => '16011',
            'national_id' => '2234567892',
            'photo_id_card' => null,
            'photo_birth_certificate' => null,
        ]);

        $lowPerson = $this->createCatalogCompletePerson([
            'person_code' => '16012',
            'national_id' => '2234567893',
        ]);
        DB::table('contacts')->where('person_id', $lowPerson->id)->update([
            'trusted_person_phone' => null,
            'updated_at' => now(),
        ]);

        $component = Livewire::test(IncompleteCasesQueue::class);
        $summary = $component->instance()->summary;

        $this->assertSame(3, $summary['incomplete_count']);
        $this->assertSame(1, $summary['reason_counts']['national_id']);
        $this->assertSame(1, $summary['reason_counts']['social_worker_id']);
        $this->assertSame(1, $summary['reason_counts']['photo_id_card']);
        $this->assertSame(1, $summary['reason_counts']['photo_birth_certificate']);
        $this->assertSame(1, $summary['reason_counts']['trusted_person_phone']);
        $this->assertSame(1, $summary['severity_counts']['critical']);
        $this->assertSame(1, $summary['severity_counts']['high']);
        $this->assertSame(1, $summary['severity_counts']['low']);

        Livewire::test(IncompleteCasesQueue::class)
            ->set('selectedReason', 'photo_id_card')
            ->set('selectedSeverity', 'high')
            ->assertSee('16011')
            ->assertDontSee('16010')
            ->assertDontSee('16012');
    }

    public function test_queue_search_matches_beneficiary_guardian_and_social_worker_fields(): void
    {
        $this->actingAs($this->admin());

        $beneficiaryMatch = $this->createCatalogCompletePerson([
            'person_code' => '16110',
            'first_name' => 'رضا',
            'last_name' => 'جستجو',
            'national_id' => '3311111111',
            'profile_photo' => null,
        ]);

        $guardianMatch = $this->createCatalogCompletePerson([
            'person_code' => '16111',
            'national_id' => '3311111112',
            'profile_photo' => null,
        ], [
            'guardian' => [
                'first_name' => 'سرپرست',
                'last_name' => 'نمونه',
            ],
        ]);

        $workerMatch = $this->createCatalogCompletePerson([
            'person_code' => '16112',
            'national_id' => '3311111113',
            'profile_photo' => null,
        ], [
            'socialWorker' => [
                'first_name' => 'مددکار',
                'last_name' => 'نمونه',
            ],
        ]);

        Livewire::test(IncompleteCasesQueue::class)
            ->set('search', 'رضا')
            ->assertSee('16110')
            ->assertDontSee('16111')
            ->assertDontSee('16112');

        Livewire::test(IncompleteCasesQueue::class)
            ->set('search', 'سرپرست نمونه')
            ->assertSee('16111')
            ->assertDontSee('16110')
            ->assertDontSee('16112');

        Livewire::test(IncompleteCasesQueue::class)
            ->set('search', 'مددکار نمونه')
            ->assertSee('16112')
            ->assertDontSee('16110')
            ->assertDontSee('16111');
    }

    public function test_queue_sort_can_prioritize_severity_and_missing_count(): void
    {
        $this->actingAs($this->admin());

        $criticalPerson = $this->createCatalogCompletePerson([
            'person_code' => '16120',
            'national_id' => '3322222221',
            'profile_photo' => null,
        ], [
            'guardian' => [
                'social_worker_id' => null,
            ],
        ]);

        $highCountPerson = $this->createCatalogCompletePerson([
            'person_code' => '16121',
            'national_id' => '3322222222',
            'photo_id_card' => null,
            'photo_birth_certificate' => null,
            'profile_photo' => null,
            'client_case_history' => null,
        ]);

        $mediumPerson = $this->createCatalogCompletePerson([
            'person_code' => '16122',
            'national_id' => '3322222223',
            'photo_id_card' => null,
        ]);

        $severitySorted = Livewire::test(IncompleteCasesQueue::class)
            ->set('selectedSort', 'severity')
            ->instance()
            ->incompletePeople;

        $this->assertSame(
            ['16120', '16121', '16122'],
            $severitySorted->pluck('person_code')->all()
        );

        $missingCountSorted = Livewire::test(IncompleteCasesQueue::class)
            ->set('selectedSort', 'missing_count')
            ->instance()
            ->incompletePeople;

        $this->assertSame(
            ['16121', '16120', '16122'],
            $missingCountSorted->pluck('person_code')->all()
        );
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

    public function test_queue_reuses_cached_scan_results_across_requests(): void
    {
        $this->actingAs($this->admin());

        $person = $this->createCatalogCompletePerson([
            'person_code' => '16020',
            'national_id' => '',
        ]);

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();
        $cacheKey = $this->invokeProtectedMethod($component, 'scanCacheKey');

        Cache::put($cacheKey, [
            'incomplete_count' => 99,
            'reason_counts' => array_fill_keys(array_keys($component->reasonOptions()), 0),
            'severity_counts' => [
                'critical' => 9,
                'high' => 8,
                'medium' => 7,
                'low' => 6,
            ],
            'filtered_ids' => [],
        ], now()->addMinutes(5));

        Livewire::test(IncompleteCasesQueue::class)
            ->assertSee('99')
            ->assertDontSee($person->person_code);
    }

    public function test_queue_reuses_paginated_people_within_same_request(): void
    {
        $this->actingAs($this->admin());

        $person = $this->createCatalogCompletePerson([
            'person_code' => '16021',
            'national_id' => '',
        ]);

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();

        $component->summary;

        DB::flushQueryLog();
        DB::enableQueryLog();

        $firstPaginator = $component->incompletePeople;
        $queriesAfterFirstAccess = DB::getQueryLog();

        $secondPaginator = $component->incompletePeople;
        $queriesAfterSecondAccess = DB::getQueryLog();

        $this->assertSame($firstPaginator, $secondPaginator);
        $this->assertCount(count($queriesAfterFirstAccess), $queriesAfterSecondAccess);
        $this->assertSame('16021', $secondPaginator->first()?->person_code);

        DB::disableQueryLog();
    }

    public function test_queue_uses_lighter_relations_for_scan_than_for_page_rows(): void
    {
        $this->actingAs($this->admin());

        $component = Livewire::test(IncompleteCasesQueue::class)->instance();

        $scanRelations = $this->invokeProtectedMethod($component, 'scanRelations');
        $pageRelations = $this->invokeProtectedMethod($component, 'pageRelations');

        $this->assertNotContains('guardian.socialWorker:id,first_name,last_name', $scanRelations);
        $this->assertContains('guardian.socialWorker:id,first_name,last_name', $pageRelations);
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

    private function createCatalogCompletePerson(array $personOverrides = [], array $relationOverrides = []): Person
    {
        $guardianRelationTypeId = DB::table('guardian_relation_types')->insertGetId([
            'title' => 'مادر',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $harmTypeId = DB::table('harm_types')->insertGetId([
            'title' => 'آسیب تست',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $districtId = DB::table('districts')->insertGetId([
            'name' => 'ناحیه تست '.random_int(1, 99999),
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residenceStatusId = DB::table('residence_status_types')->insertGetId([
            'name' => 'ملکی '.random_int(1, 99999),
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $needLevelTypeId = DB::table('need_level_types')->insertGetId([
            'code' => chr(65 + (int) (DB::table('need_level_types')->count() % 26)),
            'title' => 'سطح تست '.random_int(1, 99999),
            'severity_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicLevelId = DB::table('academic_levels')->insertGetId([
            'title' => 'دیپلم '.random_int(1, 99999),
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $socialWorker = SocialWorker::withoutGlobalScope('active')->create(array_merge([
            'worker_code' => ((int) (SocialWorker::withoutGlobalScope('active')->max('worker_code') ?? 9)) + 1,
            'first_name' => 'Worker',
            'last_name' => 'Queue',
            'is_active' => true,
        ], $relationOverrides['socialWorker'] ?? []));

        $guardian = Guardian::query()->create(array_merge([
            'guardian_code' => random_int(100000, 999999),
            'first_name' => 'Guardian',
            'last_name' => 'Queue',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_phone_number' => '09120000000',
            'social_worker_id' => $socialWorker->id,
            'insurance_status' => false,
        ], $relationOverrides['guardian'] ?? []));

        $person = $this->person(array_merge([
            'guardian_id' => $guardian->id,
            'first_name' => 'Beneficiary',
            'last_name' => 'Queue',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(15000, 99999),
            'shenasnameh_serial' => '123456',
            'shenasnameh_series_number' => '12',
            'shenasnameh_series_letter' => '1',
            'father_name' => 'Father Queue',
            'father_national_id' => '1111111111',
            'mother_national_id' => '2222222222',
            'phone_number' => '09121111111',
            'gender' => 'male',
            'role' => 'child',
            'sadaat_status' => 'general',
            'sadaat_relation_id' => null,
            'has_disability' => false,
            'disability_type_id' => null,
            'disability_description' => null,
            'profile_photo' => 'profile/test.jpg',
            'photo_id_card' => 'documents/id-card.jpg',
            'photo_birth_certificate' => 'documents/birth-certificate.jpg',
            'client_case_history' => 'شرح کامل وضعیت مددجو.',
        ], $personOverrides));

        DB::table('harm_type_person')->insert([
            'person_id' => $person->id,
            'harm_type_id' => $harmTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('family_statuses')->insert(array_merge([
            'person_id' => $person->id,
            'guardian_relation_type_id' => $guardianRelationTypeId,
            'has_parent_disability' => false,
            'parent_disability_description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $relationOverrides['familyStatus'] ?? []));

        DB::table('educations')->insert(array_merge([
            'person_id' => $person->id,
            'is_studying' => true,
            'reason_for_not_studying' => null,
            'education_degree' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $relationOverrides['education'] ?? []));

        $residencePayload = array_merge([
            'residence_status_id' => $residenceStatusId,
            'district_id' => $districtId,
            'address' => 'آدرس کامل تست',
            'created_at' => now(),
            'updated_at' => now(),
        ], $relationOverrides['residence'] ?? []);

        if (($relationOverrides['residenceOwner'] ?? 'person') === 'guardian') {
            DB::table('residences')->insert($residencePayload + [
                'person_id' => null,
                'guardian_id' => $guardian->id,
            ]);
        } else {
            DB::table('residences')->insert($residencePayload + [
                'person_id' => $person->id,
                'guardian_id' => null,
            ]);
        }

        if (! ($relationOverrides['removePersonContact'] ?? false)) {
            DB::table('contacts')->insert(array_merge([
                'person_id' => $person->id,
                'guardian_id' => null,
                'landline_phone' => '02112345678',
                'trusted_person_phone' => '09123334444',
                'created_at' => now(),
                'updated_at' => now(),
            ], $relationOverrides['personContact'] ?? []));
        }

        if (($relationOverrides['contactOwner'] ?? null) === 'guardian') {
            DB::table('contacts')->insert(array_merge([
                'person_id' => null,
                'guardian_id' => $guardian->id,
                'landline_phone' => '02187654321',
                'trusted_person_phone' => '09125556666',
                'created_at' => now(),
                'updated_at' => now(),
            ], $relationOverrides['guardianContact'] ?? []));
        }

        $supportOrganizationId = SupportOrganization::query()
            ->where('slug', 'emdad')
            ->value('id');

        DB::table('support_coverages')->insert(array_merge([
            'person_id' => $person->id,
            'support_organization_id' => $supportOrganizationId,
            'description' => 'شرح پوشش حمایتی',
            'other_organization_name' => null,
            'support_card_image' => 'support/card.jpg',
            'active_status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $relationOverrides['supportCoverage'] ?? []));

        DB::table('needs_levels')->insert(array_merge([
            'person_id' => $person->id,
            'need_level_id' => $needLevelTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $relationOverrides['needsLevel'] ?? []));

        return $person->fresh();
    }

    private function invokeProtectedMethod(object $target, string $method): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target);
    }
}
