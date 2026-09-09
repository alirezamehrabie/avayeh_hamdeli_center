<?php

namespace Tests\Feature;

use App\Exports\ServiceCategoryBreakdownExport;
use App\Helpers\Morilog\Jalalian;
use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Fakes\ExcelFake;
use ReflectionProperty;
use Tests\TestCase;

class ServiceCategoryBreakdownExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_export_downloads_category_breakdown_rows(): void
    {
        Excel::fake();

        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->call('exportCategoryBreakdownToExcel');

        Excel::assertDownloaded(
            'جزئیات-تحویل-'.$service->serviceName->name.'-'.Jalalian::now()->format('Y-m-d').'.xlsx',
            function (ServiceCategoryBreakdownExport $export) use ($service): bool {
                $rows = collect($export->array());

                $packRow = $rows->firstWhere(0, 'برنج بسته‌ای');
                $kgRow = $rows->firstWhere(0, 'روغن کیلویی');

                return $rows->count() === 2
                    && $rows[0][0] === 'روغن کیلویی'
                    && $rows[1][0] === 'برنج بسته‌ای'
                    && $packRow[1] === 4.0
                    && $packRow[2] === (Service::unitOptions()['pack'] ?? 'pack')
                    && $packRow[3] === 2
                    && $kgRow[1] === 5.0
                    && $kgRow[3] === 1
                    && $export->title() === mb_substr('جزئیات تحویل '.$service->serviceName->name, 0, 31);
            }
        );
    }

    public function test_modal_export_respects_delivery_filters(): void
    {
        Excel::fake();

        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliverySearch', '2222222222')
            ->call('exportCategoryBreakdownToExcel');

        Excel::assertDownloaded(
            'جزئیات-تحویل-'.$service->serviceName->name.'-'.Jalalian::now()->format('Y-m-d').'.xlsx',
            function (ServiceCategoryBreakdownExport $export): bool {
                $rows = collect($export->array());

                return $rows->count() === 1 && $rows->first()[0] === 'روغن کیلویی';
            }
        );
    }

    public function test_modal_export_flashes_error_when_nothing_matches_filters(): void
    {
        Excel::fake();

        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliverySearch', 'No Match')
            ->call('exportCategoryBreakdownToExcel')
            ->assertSee('رکوردی برای خروجی گرفتن یافت نشد.');

        $downloads = (new ReflectionProperty(ExcelFake::class, 'downloads'))
            ->getValue(Excel::getFacadeRoot());

        $this->assertSame([], $downloads);
    }

    public function test_modal_footer_renders_export_button(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSee('exportCategoryBreakdownToExcel', false);
    }

    public function test_modal_rows_show_remaining_against_category_total(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        $packLabel = Service::unitOptions()['pack'] ?? 'pack';

        // Pack category: quantity 30, delivered 2 + 2 = 4 → 26 remaining; no record-count prefix.
        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSee('26 '.$packLabel.' باقی‌مانده از 30', false)
            ->assertDontSee('رکورد تحویل |', false);
    }

    /**
     * Three deliveries across two categories: the pack category is delivered
     * twice (2 + 2 = 4) and the kg category once (5).
     *
     * @return array{0: User, 1: Service}
     */
    private function serviceWithDeliveries(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $service = $this->createService($user);

        $packCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'برنج بسته‌ای',
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $kgCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'روغن کیلویی',
            'quantity' => 30,
            'unit' => 'kg',
            'value' => 1000,
            'sort_id' => 2,
            'created_by' => $user->id,
        ]);

        $this->deliver($service, $packCategory, $user, 2, 'تحویل اول', '1111111111');
        $this->deliver($service, $packCategory, $user, 2, 'تحویل دوم', '1111111111');
        // Older delivery date on the bigger total: the list must order by
        // delivered quantity (5 > 4), NOT by recency as before.
        $this->deliver($service, $kgCategory, $user, 5, 'تحویل روغن', '2222222222', now()->subDays(3)->toDateString());

        return [$user, $service->fresh()];
    }

    private function deliver(Service $service, ServiceCategory $category, User $user, int $quantity, string $notes, string $nationalId, ?string $deliveredAt = null): ServiceDelivery
    {
        return ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'national_id' => $nationalId,
            'full_name' => 'گیرنده '.$nationalId,
            'delivered_quantity' => $quantity,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => $quantity * 1000,
            'delivered_at' => $deliveredAt ?? now()->toDateString(),
            'notes' => $notes,
            'created_by' => $user->id,
        ]);
    }

    private function createService(User $user): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Breakdown Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 60,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);
    }
}
