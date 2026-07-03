<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\People\BeneficiaryCaseFile;
use App\Models\BeneficiaryCaseRecord;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class BeneficiaryCaseFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_manual_case_record_for_selected_beneficiary(): void
    {
        $admin = $this->admin();
        $person = $this->person();

        $this->actingAs($admin);

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->set('recordType', BeneficiaryCaseRecord::TYPE_INVOICE)
            ->set('recordTitle', 'فاکتور دارو')
            ->set('recordDescription', 'پرداخت کمک‌هزینه دارو')
            ->set('recordedAt', '2026-07-03')
            ->set('recordAmount', '2500000')
            ->set('recordReferenceNumber', 'INV-100')
            ->call('saveCaseRecord')
            ->assertHasNoErrors()
            ->assertSee('رکورد پرونده با موفقیت ثبت شد.');

        $record = BeneficiaryCaseRecord::query()->firstOrFail();

        $this->assertSame($person->id, $record->person_id);
        $this->assertSame($admin->id, $record->created_by);
        $this->assertSame(BeneficiaryCaseRecord::TYPE_INVOICE, $record->record_type);
        $this->assertSame('فاکتور دارو', $record->title);
        $this->assertSame('پرداخت کمک‌هزینه دارو', $record->description);
        $this->assertSame('2026-07-03', $record->recorded_at?->toDateString());
        $this->assertSame(2500000, $record->amount);
        $this->assertSame('INV-100', $record->reference_number);
    }

    public function test_manual_case_record_is_rendered_in_case_file_timeline(): void
    {
        $person = $this->person();

        BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $this->admin()->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_FOLLOW_UP,
            'title' => 'پیگیری درمان',
            'description' => 'هماهنگی با مرکز درمانی',
            'recorded_at' => '2026-07-03',
            'amount' => null,
            'reference_number' => 'REF-200',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->assertSee('پیگیری درمان')
            ->assertSee('هماهنگی با مرکز درمانی')
            ->assertSee('REF-200');
    }

    public function test_admin_can_export_selected_beneficiary_case_file_to_excel(): void
    {
        Excel::fake();

        $person = $this->person();

        BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $this->admin()->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_INVOICE,
            'title' => 'فاکتور دارو',
            'description' => 'پرداخت کمک‌هزینه دارو',
            'recorded_at' => '2026-07-03',
            'amount' => 2500000,
            'reference_number' => 'INV-100',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->call('exportToExcel');

        Excel::assertDownloaded('پرونده-مددجو-'.$person->person_code.'-'.Jalalian::now()->format('Y-m-d').'.xlsx');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function person(): Person
    {
        return Person::query()->create([
            'first_name' => 'Case',
            'last_name' => 'Beneficiary',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(14000, 99999),
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ]);
    }
}
