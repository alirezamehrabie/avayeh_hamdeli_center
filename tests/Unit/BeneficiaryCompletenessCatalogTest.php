<?php

namespace Tests\Unit;

use App\Services\People\BeneficiaryCompletenessCatalog;
use Tests\TestCase;

class BeneficiaryCompletenessCatalogTest extends TestCase
{
    public function test_catalog_exposes_all_ten_registration_sections(): void
    {
        $catalog = new BeneficiaryCompletenessCatalog();

        $this->assertCount(10, $catalog->sections());
        $this->assertSame('اطلاعات فردی', $catalog->sections()[1]['label']);
        $this->assertSame('نیازمندی و پوشش حمایتی', $catalog->sections()[10]['label']);
    }

    public function test_catalog_contains_core_required_form_fields(): void
    {
        $catalog = new BeneficiaryCompletenessCatalog();

        $this->assertSame('people.national_id', $catalog->find('national_id')['path']);
        $this->assertSame('critical', $catalog->find('national_id')['severity']);
        $this->assertSame('family_statuses.guardian_relation_type_id', $catalog->find('guardian_relation_type_id')['path']);
        $this->assertSame('guardians.social_worker_id', $catalog->find('social_worker_id')['path']);
    }

    public function test_catalog_marks_conditional_fields_with_explicit_notes(): void
    {
        $catalog = new BeneficiaryCompletenessCatalog();

        $this->assertSame('conditional', $catalog->find('disability_type_id')['requirement_type']);
        $this->assertStringContainsString('has_disability = 1', (string) $catalog->find('disability_type_id')['note']);

        $this->assertSame('conditional', $catalog->find('insurance_type_id')['requirement_type']);
        $this->assertStringContainsString('insurance_status = 1', (string) $catalog->find('insurance_type_id')['note']);

        $this->assertSame('conditional', $catalog->find('reason_for_not_studying')['requirement_type']);
        $this->assertStringContainsString('is_studying = 0', (string) $catalog->find('reason_for_not_studying')['note']);
    }

    public function test_catalog_includes_management_quality_media_and_case_history_fields(): void
    {
        $catalog = new BeneficiaryCompletenessCatalog();

        $this->assertSame('management', $catalog->find('profile_photo')['requirement_type']);
        $this->assertSame('high', $catalog->find('photo_id_card')['severity']);
        $this->assertSame('high', $catalog->find('photo_birth_certificate')['severity']);
        $this->assertSame('management', $catalog->find('client_case_history')['requirement_type']);
    }

    public function test_catalog_marks_household_owned_residence_fields_as_household_scope(): void
    {
        $catalog = new BeneficiaryCompletenessCatalog();

        $residenceStatus = $catalog->find('residence_status_id');
        $address = $catalog->find('address');

        $this->assertSame('household', $residenceStatus['owner']);
        $this->assertStringContainsString('person_id', (string) $residenceStatus['note']);
        $this->assertStringContainsString('guardian_id', (string) $address['note']);
    }
}
