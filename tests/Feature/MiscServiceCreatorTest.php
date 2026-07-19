<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\Deliveries\MiscServiceCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit-style coverage for the misc-service creation rules extracted out of the
 * ServiceBatchCreator Livewire component. Component-level behavior remains
 * covered by DistributionOperatorAllocationAssignerTest; this pins the domain
 * service so the persistence rules survive future component refactors.
 */
class MiscServiceCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_service_with_categories_and_worker_allocations(): void
    {
        $operator = $this->operator();
        $worker = $this->worker(701);

        $service = $this->creator()->create(
            name: 'خدمت متفرقه آزمایشی',
            serviceType: 'individual',
            description: 'توضیح',
            distributionDate: '2026-06-20',
            categories: [
                ['name' => 'اعتبار نقدی', 'quantity' => '1000000', 'unit' => 'rial'],
                ['name' => 'کفش', 'quantity' => '2', 'unit' => 'pair'],
            ],
            socialWorkerId: $worker->id,
            createdBy: $operator->id,
        );

        $this->assertSame($operator->id, $service->created_by);
        $this->assertSame('in_distribution', $service->status);
        $this->assertEqualsWithDelta(1000002.0, (float) $service->total_quantity, 0.001);
        $this->assertSame('2026-06-20', $service->distribution_start_date->toDateString());
        $this->assertSame('2026-06-20', $service->distribution_end_date->toDateString());

        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'name' => 'اعتبار نقدی',
            'unit' => 'rial',
        ]);
        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'service_category_id' => $service->categories()->where('unit', 'rial')->value('id'),
            'allocated_quantity' => 1000000,
        ]);
        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'service_category_id' => $service->categories()->where('unit', 'pair')->value('id'),
            'allocated_quantity' => 2,
        ]);
    }

    public function test_reuses_existing_service_name(): void
    {
        $operator = $this->operator();
        $worker = $this->worker(702);
        $existing = ServiceName::query()->create([
            'name' => 'نام مشترک',
            'sort_id' => 5,
            'created_by' => $operator->id,
        ]);

        $service = $this->creator()->create(
            name: 'نام مشترک',
            serviceType: 'individual',
            description: null,
            distributionDate: '2026-06-20',
            categories: [
                ['name' => 'بسته', 'quantity' => '3', 'unit' => 'pack'],
            ],
            socialWorkerId: $worker->id,
            createdBy: $operator->id,
        );

        $this->assertSame($existing->id, $service->service_name_id);
        $this->assertSame(1, ServiceName::query()->where('name', 'نام مشترک')->count());
    }

    public function test_removes_stored_images_when_transaction_fails(): void
    {
        Storage::fake('public');

        $operator = $this->operator();
        $worker = $this->worker(703);
        $storage = Storage::disk('public');

        $this->expectException(\Throwable::class);

        try {
            $this->creator()->create(
                name: 'خدمت عکسی خراب',
                serviceType: 'individual',
                description: null,
                distributionDate: '2026-06-20',
                categories: [
                    [
                        'name' => 'دسته تکراری',
                        'quantity' => '5',
                        'unit' => 'portion',
                        'image' => UploadedFile::fake()->image('food.png', 800, 600),
                    ],
                    // Duplicate category name violates the unique constraint on
                    // service_categories.name, forcing a rollback after the first
                    // row's image has already been written to disk.
                    [
                        'name' => 'دسته تکراری',
                        'quantity' => '1',
                        'unit' => 'pack',
                    ],
                ],
                socialWorkerId: $worker->id,
                createdBy: $operator->id,
            );
        } catch (\Throwable $e) {
            // Rolled back: no service persisted and no orphaned image left behind.
            $this->assertSame(0, Service::query()->count());
            $this->assertCount(0, $storage->allFiles());

            throw $e;
        }
    }

    private function creator(): MiscServiceCreator
    {
        return app(MiscServiceCreator::class);
    }

    private function operator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
    }

    private function worker(int $code): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => 'Worker',
            'last_name' => (string) $code,
            'is_active' => true,
        ]);
    }
}
