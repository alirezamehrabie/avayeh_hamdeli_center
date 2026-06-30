<?php

namespace Tests\Feature;

use App\Models\FamilyStatus;
use App\Models\HarmType;
use App\Models\Person;
use App\Services\People\SyncPersonFamilyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPersonFamilyContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_selected_harm_types_and_propagates_family_harms_to_matching_siblings(): void
    {
        $fatherDeath = HarmType::query()->create(['id' => 1, 'title' => 'Father death']);
        $motherDeath = HarmType::query()->create(['id' => 2, 'title' => 'Mother death']);

        $person = $this->person('P900001', '9000010001', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '2222222222',
        ]);
        $fullSibling = $this->person('P900002', '9000010002', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '2222222222',
        ]);
        $paternalSibling = $this->person('P900003', '9000010003', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '3333333333',
        ]);
        $unrelated = $this->person('P900004', '9000010004', [
            'father_national_id' => '4444444444',
            'mother_national_id' => '5555555555',
        ]);

        app(SyncPersonFamilyContext::class)->syncHarmTypesWithFamilyPropagation($person, [
            (string) $fatherDeath->id,
            $motherDeath->id,
        ]);

        $this->assertTrue($person->fresh()->harmTypes->contains($fatherDeath));
        $this->assertTrue($person->fresh()->harmTypes->contains($motherDeath));
        $this->assertTrue($fullSibling->fresh()->harmTypes->contains($fatherDeath));
        $this->assertTrue($fullSibling->fresh()->harmTypes->contains($motherDeath));
        $this->assertTrue($paternalSibling->fresh()->harmTypes->contains($fatherDeath));
        $this->assertFalse($paternalSibling->fresh()->harmTypes->contains($motherDeath));
        $this->assertFalse($unrelated->fresh()->harmTypes->contains($fatherDeath));
        $this->assertFalse($unrelated->fresh()->harmTypes->contains($motherDeath));

        app(SyncPersonFamilyContext::class)->syncHarmTypesWithFamilyPropagation($person, [$motherDeath->id]);

        $this->assertFalse($fullSibling->fresh()->harmTypes->contains($fatherDeath));
        $this->assertTrue($fullSibling->fresh()->harmTypes->contains($motherDeath));
        $this->assertFalse($paternalSibling->fresh()->harmTypes->contains($fatherDeath));
    }

    public function test_it_syncs_family_status_only_to_full_siblings(): void
    {
        $person = $this->person('P900005', '9000010005', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '2222222222',
        ]);
        $fullSibling = $this->person('P900006', '9000010006', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '2222222222',
        ]);
        $paternalSibling = $this->person('P900007', '9000010007', [
            'father_national_id' => '1111111111',
            'mother_national_id' => '3333333333',
        ]);

        app(SyncPersonFamilyContext::class)->syncSiblingFamilyStatuses($person, [
            'remarried_parent' => 'father',
            'children_from_previous_marriage' => 2,
            'has_parent_disability' => true,
            'parent_disability_description' => 'Shared family status',
            'father_left_home' => true,
            'mother_left_home' => false,
        ]);

        $fullSiblingStatus = FamilyStatus::query()->where('person_id', $fullSibling->id)->first();

        $this->assertNotNull($fullSiblingStatus);
        $this->assertSame('father', $fullSiblingStatus->remarried_parent);
        $this->assertSame(2, $fullSiblingStatus->children_from_previous_marriage);
        $this->assertTrue($fullSiblingStatus->has_parent_disability);
        $this->assertSame('Shared family status', $fullSiblingStatus->parent_disability_description);
        $this->assertTrue($fullSiblingStatus->father_left_home);
        $this->assertFalse($fullSiblingStatus->mother_left_home);
        $this->assertDatabaseMissing('family_statuses', [
            'person_id' => $paternalSibling->id,
        ]);
    }

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
