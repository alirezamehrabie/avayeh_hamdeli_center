<?php

namespace Tests\Feature;

use App\Exports\ServiceReportExport;
use App\Helpers\Morilog\Jalalian;
use App\Livewire\Services\ServiceReports;
use App\Models\Guardian;
use App\Models\NeedLevelType;
use App\Models\NeedsLevel;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ServiceReportExportColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_export_columns_are_initialized_and_can_be_customized(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSet('exportColumns', ServiceReports::DEFAULT_EXPORT_COLUMNS);

        // Select all
        $component->call('selectAllExportColumns')
            ->assertSet('exportColumns', array_keys(ServiceReports::EXPORT_COLUMNS));

        // Deselect all
        $component->call('deselectAllExportColumns')
            ->assertSet('exportColumns', []);

        // Reset to default
        $component->call('resetDefaultExportColumns')
            ->assertSet('exportColumns', ServiceReports::DEFAULT_EXPORT_COLUMNS);
    }

    public function test_export_fails_when_no_columns_are_selected(): void
    {
        Excel::fake();

        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('exportColumns', [])
            ->call('exportToExcel')
            ->assertSee('حداقل یک ستون را برای خروجی اکسل انتخاب کنید.');

        $downloads = (new \ReflectionProperty(\Maatwebsite\Excel\Fakes\ExcelFake::class, 'downloads'))
            ->getValue(Excel::getFacadeRoot());

        $this->assertSame([], $downloads);
    }

    public function test_export_includes_need_level_and_entry_type_in_headings_and_data(): void
    {
        Excel::fake();

        [$user, $service, $person, $guardian] = $this->fixture();

        $this->actingAs($user);

        // Choose custom columns including need_level and entry_type
        $chosenColumns = [
            'recipient_name',
            'entry_type',
            'need_level',
            'service_category',
            'delivered_quantity',
        ];

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('exportColumns', $chosenColumns)
            ->call('exportToExcel');

        $serviceName = $service->serviceName->name;
        $filename = 'گزارش-خدمت-'.$serviceName.'-'.Jalalian::now()->format('Y-m-d').'.xlsx';

        Excel::assertDownloaded($filename, function (ServiceReportExport $export) {
            $headings = $export->headings();
            $this->assertSame([
                'نام گیرنده',
                'نوع ثبت در خدمت',
                'سطح نیاز مددجو',
                'دسته‌بندی خدمت',
                'مقدار تحویل',
            ], $headings);

            $rows = $export->array();
            $this->assertNotEmpty($rows);

            // Row 1 is the individual delivery for $person
            $personRow = collect($rows)->first(fn ($r) => str_contains($r[0], 'علی'));
            $this->assertNotNull($personRow);
            $this->assertSame('شخصی (مددجو)', $personRow[1]);
            $this->assertSame('سطح اول (بحرانی)', $personRow[2]);

            // Row 2 is the guardian delivery
            $guardianRow = collect($rows)->first(fn ($r) => str_contains($r[0], 'خانواده'));
            $this->assertNotNull($guardianRow);
            $this->assertSame('خانوادگی (سرپرست)', $guardianRow[1]);
            $this->assertSame('سطح دوم (متوسط)', $guardianRow[2]);

            return true;
        });
    }

    public function test_export_columns_persist_in_session(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        $custom = ['recipient_name', 'entry_type', 'need_level'];

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('exportColumns', $custom);

        $this->assertSame($custom, session('service_report_export_columns'));

        // Next time component mounts, it reads from session
        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSet('exportColumns', $custom);
    }

    /**
     * @return array{0: User, 1: Service, 2: Person, 3: Guardian}
     */
    private function fixture(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $worker = SocialWorker::query()->create([
            'worker_code' => (string) random_int(1000, 9999),
            'first_name' => 'مددکار',
            'last_name' => 'تستی',
            'is_active' => true,
        ]);

        $needLevel1 = NeedLevelType::query()->create([
            'code' => 'A',
            'title' => 'سطح اول (بحرانی)',
            'severity_order' => 1,
        ]);

        $needLevel2 = NeedLevelType::query()->create([
            'code' => 'B',
            'title' => 'سطح دوم (متوسط)',
            'severity_order' => 2,
        ]);

        $person = Person::query()->create([
            'person_code' => (string) random_int(100000, 999999),
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'national_id' => (string) random_int(1000000000, 9999999999),
        ]);

        NeedsLevel::query()->create([
            'person_id' => $person->id,
            'need_level_id' => $needLevel1->id,
        ]);

        $guardian = Guardian::query()->create([
            'guardian_code' => (string) random_int(10000, 99999),
            'first_name' => 'خانواده',
            'last_name' => 'احمدی',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'social_worker_id' => $worker->id,
        ]);

        $householdMember = Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => (string) random_int(100000, 999999),
            'first_name' => 'حسن',
            'last_name' => 'احمدی',
            'national_id' => (string) random_int(1000000000, 9999999999),
        ]);

        NeedsLevel::query()->create([
            'person_id' => $householdMember->id,
            'need_level_id' => $needLevel2->id,
        ]);

        $serviceName = ServiceName::query()->create([
            'name' => 'بسته معیشتی '.Str::random(6),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 100,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);

        $category = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'بسته اقلام',
            'quantity' => 100,
            'unit' => 'pack',
            'value' => 10000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        // Individual delivery
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'person_id' => $person->id,
            'national_id' => $person->national_id,
            'full_name' => $person->first_name.' '.$person->last_name,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 10000,
            'delivered_total_value' => 10000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        // Guardian delivery
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'guardian_id' => $guardian->id,
            'national_id' => $guardian->national_code,
            'full_name' => $guardian->first_name.' '.$guardian->last_name,
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 10000,
            'delivered_total_value' => 20000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        return [$user, $service, $person, $guardian];
    }
}
