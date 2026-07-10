<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceArchive;
use App\Livewire\Services\ServiceList;
use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceListArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_archive_service_from_service_list_and_preserve_history(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager);

        [$service, $category, $delivery] = $this->serviceWithCategoryAndDelivery($manager);

        $initialQuantity = (float) $service->total_quantity;
        $initialValue = (int) $service->total_service_value;

        Livewire::test(ServiceList::class)
            ->call('openDeleteServiceConfirmation', $service->id)
            ->assertDispatched('open-notification-modal')
            ->call('deleteService', $service->id)
            ->assertViewHas('services', fn ($services): bool => ! $services->contains('id', $service->id));

        $archivedService = Service::withTrashed()->findOrFail($service->id);
        $archivedCategory = ServiceCategory::withTrashed()->findOrFail($category->id);
        $historicalDelivery = ServiceDelivery::query()->findOrFail($delivery->id);

        $this->assertTrue($archivedService->trashed());
        $this->assertTrue($archivedCategory->trashed());
        $this->assertSame($initialQuantity, (float) $archivedService->total_quantity);
        $this->assertSame($initialValue, (int) $archivedService->total_service_value);
        $this->assertTrue($historicalDelivery->service?->trashed() ?? false);
        $this->assertTrue($historicalDelivery->serviceCategory?->trashed() ?? false);
    }

    public function test_archived_service_is_listed_in_archive_and_can_be_restored_with_categories(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager);

        [$service, $category] = $this->serviceWithCategoryAndDelivery($manager);

        $service->delete();

        Livewire::test(ServiceArchive::class)
            ->assertViewHas('archivedServices', fn ($services): bool => $services->contains('id', $service->id))
            ->call('openRestoreServiceConfirmation', $service->id)
            ->assertDispatched('open-notification-modal')
            ->call('restoreService', $service->id)
            ->assertViewHas('archivedServices', fn ($services): bool => ! $services->contains('id', $service->id));

        $this->assertFalse(Service::withTrashed()->findOrFail($service->id)->trashed());
        $this->assertFalse(ServiceCategory::withTrashed()->findOrFail($category->id)->trashed());
    }

    public function test_service_reports_can_load_archived_service_by_selected_id(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager);

        [$service, $category, $delivery] = $this->serviceWithCategoryAndDelivery($manager);

        $service->delete();

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertViewHas('selectedService', function (?Service $selectedService) use ($service, $category): bool {
                return $selectedService !== null
                    && $selectedService->trashed()
                    && (int) $selectedService->id === (int) $service->id
                    && $selectedService->categories->contains('id', $category->id);
            })
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->contains('id', $delivery->id));
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @return array{0: Service, 1: ServiceCategory, 2: ServiceDelivery}
     */
    private function serviceWithCategoryAndDelivery(User $manager): array
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Archive Ready Service',
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $manager->id,
        ]);

        $service = Service::query()->create([
            'code' => 'SN-90001',
            'service_name_id' => $serviceName->id,
            'name' => 'Archive Ready Service',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'total_quantity' => 10,
            'total_service_value' => 300000,
            'status' => 'approved',
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->addWeek()->toDateString(),
            'created_by' => $manager->id,
        ]);

        $category = ServiceCategory::query()->create([
            'service_name_id' => $serviceName->id,
            'service_id' => $service->id,
            'code' => 'SN-90001-CAT001',
            'name' => 'Archive Category',
            'quantity' => 10,
            'unit' => 'count',
            'value' => 30000,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $delivery = ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'national_id' => '1234567890',
            'full_name' => 'Archive Recipient',
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 30000,
            'delivered_total_value' => 60000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $manager->id,
        ]);

        return [
            $service->fresh(),
            $category->fresh(),
            $delivery->fresh(),
        ];
    }
}
