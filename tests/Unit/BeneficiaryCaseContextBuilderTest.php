<?php

namespace Tests\Unit;

use App\Models\BeneficiaryCaseRecord;
use App\Models\Guardian;
use App\Models\Person;
use App\Services\Ai\BeneficiaryCaseContextBuilder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class BeneficiaryCaseContextBuilderTest extends TestCase
{
    public function test_it_excludes_direct_identifiers_and_redacts_them_from_free_text(): void
    {
        $guardian = new Guardian([
            'first_name' => 'Maryam',
            'last_name' => 'Mehrabi',
            'national_code' => '1234567890',
            'guardian_phone_number' => '09121234567',
        ]);

        $person = new Person([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '0012345678',
            'phone_number' => '09121111111',
            'father_name' => 'Reza',
            'gender' => 'male',
            'client_case_history' => 'Ali Ahmadi با کد 0012345678 و ایمیل ali@example.com نیازمند پیگیری است.',
        ]);
        $person->setRelation('guardian', $guardian);

        $record = new BeneficiaryCaseRecord([
            'record_type' => BeneficiaryCaseRecord::TYPE_FOLLOW_UP,
            'title' => 'تماس با Maryam Mehrabi',
            'description' => 'شماره تماس 09121234567 ثبت شد.',
        ]);

        $context = (new BeneficiaryCaseContextBuilder)->build(
            $person,
            new Collection,
            new Collection,
            collect([$record]),
        );
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('Ali', $json);
        $this->assertStringNotContainsString('Ahmadi', $json);
        $this->assertStringNotContainsString('Maryam', $json);
        $this->assertStringNotContainsString('Mehrabi', $json);
        $this->assertStringNotContainsString('0012345678', $json);
        $this->assertStringNotContainsString('09121234567', $json);
        $this->assertStringNotContainsString('ali@example.com', $json);
        $this->assertStringContainsString('[نام حذف شد]', $json);
        $this->assertStringContainsString('[شناسه حذف شد]', $json);
        $this->assertStringContainsString('[ایمیل حذف شد]', $json);
    }
}
