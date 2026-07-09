<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\People\BeneficiaryCaseFile;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
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
            ->set('recordedAt', '1405/04/12')
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

    public function test_case_record_form_defaults_to_today_in_jalali(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(BeneficiaryCaseFile::class)
            ->assertSet('recordedAt', Jalalian::now()->format('Y/m/d'));
    }

    public function test_manual_case_record_rejects_invalid_jalali_date(): void
    {
        $admin = $this->admin();
        $person = $this->person();

        $this->actingAs($admin);

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->set('recordType', BeneficiaryCaseRecord::TYPE_INVOICE)
            ->set('recordTitle', 'ÙØ§Ú©ØªÙˆØ± Ø¯Ø§Ø±Ùˆ')
            ->set('recordedAt', '1405/13/40')
            ->call('saveCaseRecord')
            ->assertHasErrors(['recordedAt']);
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

    public function test_case_file_search_uses_shared_people_search_for_last_name_matches(): void
    {
        $admin = $this->admin();
        $person = $this->person([
            'first_name' => 'Ali',
            'last_name' => 'SharedSearchTarget',
            'person_code' => '14001',
        ]);

        $this->actingAs($admin);

        Livewire::test(BeneficiaryCaseFile::class)
            ->set('search', 'SharedSearchTarget')
            ->assertSee($person->person_code)
            ->assertSee('SharedSearchTarget');
    }

    public function test_case_file_search_allows_short_numeric_code_searches(): void
    {
        $admin = $this->admin();
        $person = $this->person([
            'person_code' => '14002',
        ]);

        $this->actingAs($admin);

        Livewire::test(BeneficiaryCaseFile::class)
            ->set('search', '1')
            ->assertSee($person->person_code);
    }

    public function test_activity_without_linked_service_shows_activity_only_label(): void
    {
        $person = $this->person();
        $activity = $this->activity('کلاس آموزشی بدون خدمت');

        ActivityAttendance::query()->create([
            'activity_id' => $activity->id,
            'person_id' => $person->id,
            'status' => 'present',
            'registration_method' => 'manual',
            'checked_in_at' => '2026-07-03 10:00:00',
            'recorded_by' => $this->admin()->id,
        ]);

        $this->actingAs($this->admin());

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->assertSee('کلاس آموزشی بدون خدمت')
            ->assertSee('فقط فعالیت / فاقد خدمت');
    }

    public function test_activity_with_linked_service_shows_category_breakdown_and_valuation(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        $activity = $this->activity('جشن خدماتی');
        $service = $this->activityService($activity, $admin, 'پذیرایی مراسم');
        $category = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'بسته پذیرایی',
            'quantity' => 20,
            'unit' => 'count',
            'value' => 150000,
            'created_by' => $admin->id,
        ]);
        $attendance = ActivityAttendance::query()->create([
            'activity_id' => $activity->id,
            'person_id' => $person->id,
            'status' => 'present',
            'registration_method' => 'manual',
            'checked_in_at' => '2026-07-03 10:00:00',
            'recorded_by' => $admin->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'activity_attendance_id' => $attendance->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_ACTIVITY,
            'person_id' => $person->id,
            'national_id' => $person->national_id,
            'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name),
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 150000,
            'delivered_total_value' => 300000,
            'delivered_at' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->assertSee('جشن خدماتی')
            ->assertSee('پذیرایی مراسم')
            ->assertSee('بسته پذیرایی')
            ->assertSee('300,000 ریال')
            ->assertDontSee('فقط فعالیت / فاقد خدمت');
        $timeline = Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->instance()
            ->getTimelineProperty();

        $this->assertSame(1, $timeline->count());
        $this->assertSame('activity', $timeline->first()['type']);
    }

    public function test_guardian_services_are_separated_from_direct_beneficiary_totals(): void
    {
        $admin = $this->admin();
        $guardian = Guardian::query()->create([
            'first_name' => 'Family',
            'last_name' => 'Guardian',
            'national_code' => '8811223344',
            'guardian_code' => 881,
        ]);
        $person = $this->person([
            'guardian_id' => $guardian->id,
        ]);

        [$directService, $directCategory] = $this->serviceWithCategory($admin, 'Direct Medicine');
        [$familyService, $familyCategory] = $this->serviceWithCategory($admin, 'Family Food Basket', 'family');

        ServiceDelivery::query()->create([
            'service_id' => $directService->id,
            'service_category_id' => $directCategory->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'person_id' => $person->id,
            'national_id' => $person->national_id,
            'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name),
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 100000,
            'delivered_total_value' => 100000,
            'delivered_at' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $familyService->id,
            'service_category_id' => $familyCategory->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'guardian_id' => $guardian->id,
            'national_id' => $guardian->national_code,
            'full_name' => $guardian->full_name,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 500000,
            'delivered_total_value' => 500000,
            'delivered_at' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->assertSee('خدمات مستقیم مددجو')
            ->assertSee('خدمات خانوار/سرپرست')
            ->assertSee('ارزش مستقیم + دستی')
            ->assertSee('ارزش خانوار/سرپرست')
            ->assertSee('خدمت خانوار');

        $instance = $component->instance();

        $this->assertSame(1, $instance->getDirectServiceDeliveriesProperty()->count());
        $this->assertSame(1, $instance->getFamilyServiceDeliveriesProperty()->count());
        $this->assertSame(100000, (int) $instance->getDirectServiceDeliveriesProperty()->sum('delivered_total_value'));
        $this->assertSame(500000, (int) $instance->getFamilyServiceDeliveriesProperty()->sum('delivered_total_value'));
    }

    public function test_case_file_summary_totals_are_not_limited_to_latest_timeline_rows(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        [$service, $category] = $this->serviceWithCategory($admin, 'Long Service History', 'individual', 100);

        for ($i = 0; $i < 55; $i++) {
            ServiceDelivery::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
                'person_id' => $person->id,
                'national_id' => $person->national_id,
                'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name),
                'delivered_quantity' => 1,
                'value_per_unit_snapshot' => 1000,
                'delivered_total_value' => 1000,
                'delivered_at' => now()->subDays($i)->toDateString(),
                'created_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin);

        $instance = Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
            ->assertSee('آخرین 50 رکورد')
            ->instance();

        $this->assertSame(50, $instance->getDirectServiceDeliveriesProperty()->count());
        $this->assertSame(50, $instance->getTimelineProperty()->count());

        $totals = $instance->getCaseFileTotalsProperty();

        $this->assertSame(55, $totals['direct_services_count']);
        $this->assertSame(55000, $totals['direct_services_value']);
    }

    public function test_uploaded_case_record_files_are_removed_when_database_save_fails(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $person = $this->person();

        BeneficiaryCaseRecordAttachment::creating(function (): void {
            throw new RuntimeException('Forced attachment failure.');
        });

        $this->actingAs($admin);

        try {
            Livewire::test(BeneficiaryCaseFile::class, ['personId' => $person->id])
                ->set('recordType', BeneficiaryCaseRecord::TYPE_INVOICE)
                ->set('recordTitle', 'فاکتور دارای خطا')
                ->set('recordedAt', '1405/04/12')
                ->set('recordAttachments', [
                    UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf'),
                ])
                ->call('saveCaseRecord');

            $this->fail('Expected forced attachment failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced attachment failure.', $exception->getMessage());
        } finally {
            BeneficiaryCaseRecordAttachment::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('public')->allFiles('beneficiary-case-records'));
        $this->assertDatabaseCount('beneficiary_case_records', 0);
        $this->assertDatabaseCount('beneficiary_case_record_attachments', 0);
    }

    public function test_admin_can_open_case_record_attachment_through_authorized_route(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $person = $this->person();
        $record = BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $admin->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_INVOICE,
            'title' => 'فاکتور تست',
            'recorded_at' => '2026-07-03',
        ]);

        $path = "beneficiary-case-records/{$person->id}/{$record->id}/invoice.pdf";
        Storage::disk('public')->put($path, 'secure invoice content');

        $attachment = BeneficiaryCaseRecordAttachment::query()->create([
            'beneficiary_case_record_id' => $record->id,
            'uploaded_by' => $admin->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => 22,
        ]);

        $this->assertSame(route('admin.people.case-file.attachments.show', ['attachment' => $attachment]), $attachment->url);
        $this->assertStringNotContainsString('/storage/', $attachment->url);

        $this->actingAs($admin)
            ->get($attachment->url)
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=invoice.pdf');
    }

    public function test_non_admin_cannot_open_case_record_attachment(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $person = $this->person();
        $record = BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $admin->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_INVOICE,
            'title' => 'فاکتور تست',
            'recorded_at' => '2026-07-03',
        ]);

        $path = "beneficiary-case-records/{$person->id}/{$record->id}/invoice.pdf";
        Storage::disk('public')->put($path, 'secure invoice content');

        $attachment = BeneficiaryCaseRecordAttachment::query()->create([
            'beneficiary_case_record_id' => $record->id,
            'uploaded_by' => $admin->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => 22,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->actingAs($user)
            ->get($attachment->url)
            ->assertForbidden();
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
            'first_name' => 'Case',
            'last_name' => 'Beneficiary',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(14000, 99999),
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ], $overrides));
    }

    private function activity(string $name): Activity
    {
        return Activity::query()->create([
            'name' => $name,
            'activity_type' => 'group_activity',
            'status' => 'ongoing',
            'created_by' => null,
        ]);
    }

    private function activityService(Activity $activity, User $creator, string $name): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);

        return Service::query()->create([
            'activity_id' => $activity->id,
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => false,
            'supports_activity_delivery' => true,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 20,
            'total_service_value' => 3000000,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
    }

    private function serviceWithCategory(User $creator, string $name, string $serviceType = 'individual', int $categoryQuantity = 20): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => $serviceType,
            'supports_gate_delivery' => false,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 20,
            'total_service_value' => 3000000,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name.' Category',
            'quantity' => $categoryQuantity,
            'unit' => 'count',
            'value' => 100000,
            'created_by' => $creator->id,
        ]);

        return [$service, $category];
    }
}
