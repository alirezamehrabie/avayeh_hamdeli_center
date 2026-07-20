<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Guardian;
use App\Models\Person;
use App\Services\People\BeneficiaryCompletenessEvaluator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class BeneficiaryCompletenessEvaluatorTest extends TestCase
{
    public function test_evaluator_applies_conditional_fields_and_builds_severity(): void
    {
        $person = $this->personWithLoadedContext([
            'has_disability' => false,
            'disability_type_id' => null,
        ]);

        $field = $this->field(
            key: 'disability_type_id',
            severity: 'high',
            requirementType: 'conditional',
        );

        $notApplicable = $this->evaluator()->evaluate($person, [$field]);

        $this->assertSame([], $notApplicable['reasons']);
        $this->assertSame('low', $notApplicable['severity']['key']);

        $person->has_disability = true;

        $applicable = $this->evaluator()->evaluate($person, [$field]);

        $this->assertSame(['disability_type_id'], array_keys($applicable['reasons']));
        $this->assertSame('high', $applicable['severity']['key']);
        $this->assertNotSame('', $applicable['reasons']['disability_type_id']['basis']);
    }

    public function test_evaluator_prefers_person_contact_before_guardian_contact(): void
    {
        $person = $this->personWithLoadedContext(['guardian_id' => 42]);
        $person->setAttribute('id', 10);
        $person->setRelation('guardian', $this->guardianWithLoadedResidence(42));

        $personContact = new Contact([
            'person_id' => 10,
            'trusted_person_phone' => null,
        ]);
        $guardianContact = new Contact([
            'guardian_id' => 42,
            'trusted_person_phone' => '09120000000',
        ]);

        $state = $this->evaluator()->evaluate(
            $person,
            [$this->field('trusted_person_phone', 'low')],
            collect([10 => $personContact]),
            collect([42 => $guardianContact]),
        );

        $this->assertArrayHasKey('trusted_person_phone', $state['reasons']);
    }

    public function test_evaluator_falls_back_to_guardian_contact(): void
    {
        $person = $this->personWithLoadedContext(['guardian_id' => 42]);
        $person->setAttribute('id', 10);
        $person->setRelation('guardian', $this->guardianWithLoadedResidence(42));

        $guardianContact = new Contact([
            'guardian_id' => 42,
            'trusted_person_phone' => '09120000000',
        ]);

        $state = $this->evaluator()->evaluate(
            $person,
            [$this->field('trusted_person_phone', 'low')],
            collect(),
            collect([42 => $guardianContact]),
        );

        $this->assertSame([], $state['reasons']);
    }

    public function test_next_step_prioritizes_severity_then_section(): void
    {
        $reasons = [
            'guardian_phone_number' => [
                'label' => 'شماره تماس سرپرست',
                'section' => 'مشخصات سرپرست',
                'severity' => 'high',
            ],
            'national_id' => [
                'label' => 'کد ملی',
                'section' => 'اطلاعات هویتی مددجو',
                'severity' => 'high',
            ],
            'profile_photo' => [
                'label' => 'تصویر پروفایل',
                'section' => 'شرح پرونده و مدارک',
                'severity' => 'medium',
            ],
        ];

        $nextStep = $this->evaluator()->nextStep($reasons);

        $this->assertSame('اطلاعات هویتی مددجو', $nextStep['section']);
        $this->assertSame('کد ملی', $nextStep['focus_label']);
        $this->assertStringContainsString('اطلاعات هویتی مددجو', $nextStep['primary_action_label']);
    }

    private function evaluator(): BeneficiaryCompletenessEvaluator
    {
        return new BeneficiaryCompletenessEvaluator;
    }

    private function personWithLoadedContext(array $attributes = []): Person
    {
        $person = new Person($attributes);

        $person->setRelations([
            'guardian' => null,
            'education' => null,
            'familyStatus' => null,
            'residenceContact' => null,
            'supportCoverage' => null,
            'needsLevel' => null,
            'harmTypes' => new EloquentCollection,
        ]);

        return $person;
    }

    private function guardianWithLoadedResidence(int $id): Guardian
    {
        $guardian = new Guardian;
        $guardian->setAttribute('id', $id);
        $guardian->setRelation('residence', null);

        return $guardian;
    }

    private function field(
        string $key,
        string $severity,
        string $requirementType = 'required',
    ): array {
        return [
            'key' => $key,
            'label' => $key,
            'path' => 'test.'.$key,
            'section_label' => 'Test section',
            'requirement_type' => $requirementType,
            'severity' => $severity,
        ];
    }
}
