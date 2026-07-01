<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_duplicate_category_name_is_rejected_within_same_service(): void
    {
        [$manager, $service, $serviceName] = $this->serviceContext();

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Family Pack',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->expectException(QueryException::class);

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Family Pack',
            'quantity' => 3,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);
    }

    public function test_soft_deleted_category_name_can_be_reused_within_same_service(): void
    {
        [$manager, $service, $serviceName] = $this->serviceContext();

        $original = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Family Pack',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $original->delete();

        $replacement = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Family Pack',
            'quantity' => 3,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);

        $this->assertNotSame($original->id, $replacement->id);
        $this->assertSoftDeleted('service_categories', ['id' => $original->id]);
        $this->assertDatabaseHas('service_categories', [
            'id' => $replacement->id,
            'service_id' => $service->id,
            'name' => 'Family Pack',
            'deleted_at' => null,
        ]);
    }

    public function test_active_duplicate_category_name_is_rejected_after_persian_normalization_within_same_service(): void
    {
        [$manager, $service, $serviceName] = $this->serviceContext();

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'بسته ي معيشتي',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->expectException(QueryException::class);

        $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'بسته‌ی  معیشتی',
            'quantity' => 3,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);
    }

    private function serviceContext(): array
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => false,
        ]);

        $serviceName = ServiceName::query()->create([
            'name' => 'Integrity Service',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Integrity Service',
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'description' => null,
            'total_quantity' => 0,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-07-01',
            'distribution_end_date' => '2026-07-01',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);

        return [$manager, $service, $serviceName];
    }
}
