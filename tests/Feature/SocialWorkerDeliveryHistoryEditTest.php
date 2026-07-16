<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\SocialWorkers\Dashboard;
use App\Livewire\SocialWorkers\DeliveryHistory;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerDeliveryHistoryEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_creates_one_delivery_batch_per_recipient(): void
    {
        [$user, $worker] = $this->socialWorkerUser();
        $service = $this->serviceWithCategories();
        $categories = $service->categories()->orderBy('id')->get();

        foreach ($categories as $category) {
            $service->workerAllocations()->create([
                'social_worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'allocated_quantity' => 10,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('selectedServiceId', $service->id)
            ->set('recipientEntries', [
                $this->recipientEntry('1111111111', 'First Recipient', [
                    $categories[0]->id => 1,
                    $categories[1]->id => 2,
                ]),
                $this->recipientEntry('2222222222', 'Second Recipient', [
                    $categories[0]->id => 3,
                    $categories[1]->id => 4,
                ]),
            ])
            ->set('deliveredAt', Jalalian::fromDateTime(now())->format('Y/m/d'))
            ->call('saveDelivery')
            ->assertHasNoErrors();

        $deliveries = ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $deliveries);
        $this->assertCount(1, $deliveries->where('national_id', '1111111111')->pluck('delivery_batch_id')->unique());
        $this->assertCount(1, $deliveries->where('national_id', '2222222222')->pluck('delivery_batch_id')->unique());
        $this->assertNotSame(
            $deliveries->firstWhere('national_id', '1111111111')->delivery_batch_id,
            $deliveries->firstWhere('national_id', '2222222222')->delivery_batch_id
        );
    }

    public function test_social_worker_can_edit_all_category_quantities_in_owned_home_delivery_batch(): void
    {
        [$user, $worker] = $this->socialWorkerUser();
        $service = $this->serviceWithCategories();
        $categories = $service->categories()->orderBy('id')->get();
        $batchId = (string) Str::uuid();

        foreach ($categories as $category) {
            $service->workerAllocations()->create([
                'social_worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'allocated_quantity' => 10,
            ]);
        }

        $first = $this->delivery($service, $categories[0]->id, $worker, $user, [
            'delivery_batch_id' => $batchId,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
        ]);
        $second = $this->delivery($service, $categories[1]->id, $worker, $user, [
            'delivery_batch_id' => $batchId,
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 2500,
            'delivered_total_value' => 5000,
        ]);

        $this->actingAs($user);

        Livewire::test(DeliveryHistory::class)
            ->set('selectedServiceId', $service->id)
            ->call('editDeliveryBatch', 'batch-'.$batchId)
            ->assertSet('showEditDeliveryModal', true)
            ->assertSet('editRecipientType', 'گیرنده ثبت‌نشده')
            ->set('editItems.0.quantity', '3.5')
            ->set('editItems.1.quantity', '4')
            ->call('saveDeliveryBatch')
            ->assertHasNoErrors()
            ->assertSet('showEditDeliveryModal', false)
            ->assertDispatched('delivery-history-updated');

        $this->assertDatabaseHas('service_deliveries', [
            'id' => $first->id,
            'delivered_quantity' => 3.50,
            'delivered_total_value' => 3500,
            'updated_by' => $user->id,
        ]);
        $this->assertDatabaseHas('service_deliveries', [
            'id' => $second->id,
            'delivered_quantity' => 4.00,
            'delivered_total_value' => 10000,
            'updated_by' => $user->id,
        ]);
        $this->assertNotNull($first->fresh()->corrected_at);
        $this->assertSame(1000, $first->fresh()->value_per_unit_snapshot);
        $this->assertSame(2500, $second->fresh()->value_per_unit_snapshot);
    }

    public function test_edit_rejects_quantity_above_worker_category_allocation_without_partial_update(): void
    {
        [$user, $worker] = $this->socialWorkerUser();
        $service = $this->serviceWithCategories();
        $category = $service->categories()->orderBy('id')->firstOrFail();
        $batchId = (string) Str::uuid();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 5,
        ]);

        $delivery = $this->delivery($service, $category->id, $worker, $user, [
            'delivery_batch_id' => $batchId,
            'delivered_quantity' => 2,
        ]);

        $this->actingAs($user);

        Livewire::test(DeliveryHistory::class)
            ->set('selectedServiceId', $service->id)
            ->call('editDeliveryBatch', 'batch-'.$batchId)
            ->set('editItems.0.quantity', '6')
            ->call('saveDeliveryBatch')
            ->assertHasErrors(['editItems'])
            ->assertSet('showEditDeliveryModal', true);

        $this->assertSame('2.00', $delivery->fresh()->delivered_quantity);
        $this->assertNull($delivery->fresh()->corrected_at);
    }

    public function test_external_delivery_sources_and_other_workers_records_cannot_be_edited(): void
    {
        [$user, $worker] = $this->socialWorkerUser();
        [$otherUser, $otherWorker] = $this->socialWorkerUser(202);
        $service = $this->serviceWithCategories();
        $category = $service->categories()->firstOrFail();
        $gateBatchId = (string) Str::uuid();
        $otherBatchId = (string) Str::uuid();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 10,
        ]);
        $service->workerAllocations()->create([
            'social_worker_id' => $otherWorker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 10,
        ]);

        $this->delivery($service, $category->id, $worker, $user, [
            'delivery_batch_id' => $gateBatchId,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
        ]);
        $this->delivery($service, $category->id, $otherWorker, $otherUser, [
            'delivery_batch_id' => $otherBatchId,
            'national_id' => '3333333333',
        ]);

        $this->actingAs($user);

        Livewire::test(DeliveryHistory::class)
            ->set('selectedServiceId', $service->id)
            ->assertViewHas('deliveryGroups', fn ($groups): bool => $groups->count() === 1
                && $groups->first()['can_edit'] === false)
            ->call('editDeliveryBatch', 'batch-'.$gateBatchId)
            ->assertForbidden();

        Livewire::test(DeliveryHistory::class)
            ->set('selectedServiceId', $service->id)
            ->call('editDeliveryBatch', 'batch-'.$otherBatchId)
            ->assertNotFound();
    }

    public function test_legacy_rows_for_same_recipient_are_kept_as_separate_edit_units(): void
    {
        [$user, $worker] = $this->socialWorkerUser();
        $service = $this->serviceWithCategories();
        $category = $service->categories()->firstOrFail();

        $service->workerAllocations()->create([
            'social_worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'allocated_quantity' => 10,
        ]);

        $this->delivery($service, $category->id, $worker, $user);
        $this->delivery($service, $category->id, $worker, $user);

        $this->actingAs($user);

        Livewire::test(DeliveryHistory::class)
            ->set('selectedServiceId', $service->id)
            ->assertViewHas('deliveryGroups', fn ($groups): bool => $groups->count() === 2
                && $groups->every(fn (array $group): bool => str_starts_with($group['batch_key'], 'legacy-')));
    }

    private function socialWorkerUser(int $workerCode = 201): array
    {
        $worker = SocialWorker::query()->create([
            'worker_code' => $workerCode,
            'first_name' => 'Social',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'permissions' => [],
            'social_worker_id' => $worker->id,
        ]);

        return [$user, $worker];
    }

    private function serviceWithCategories(): Service
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Editable Delivery '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 20,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);

        $service->categories()->createMany([
            [
                'service_name_id' => $serviceName->id,
                'name' => 'Food '.Str::random(8),
                'quantity' => 10,
                'unit' => 'pack',
                'value' => 1000,
                'sort_id' => 1,
                'created_by' => $manager->id,
            ],
            [
                'service_name_id' => $serviceName->id,
                'name' => 'Clothing '.Str::random(8),
                'quantity' => 10,
                'unit' => 'count',
                'value' => 2500,
                'sort_id' => 2,
                'created_by' => $manager->id,
            ],
        ]);

        return $service->fresh('categories');
    }

    private function delivery(
        Service $service,
        int $categoryId,
        SocialWorker $worker,
        User $user,
        array $overrides = []
    ): ServiceDelivery {
        return ServiceDelivery::query()->create(array_merge([
            'service_id' => $service->id,
            'service_category_id' => $categoryId,
            'social_worker_id' => $worker->id,
            'national_id' => '1111111111',
            'full_name' => 'Editable Recipient',
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ], $overrides));
    }

    private function recipientEntry(string $nationalId, string $name, array $categoryQuantities): array
    {
        return [
            'national_id' => $nationalId,
            'full_name' => $name,
            'mobile' => null,
            'quantity' => '',
            'service_category_id' => null,
            'category_quantities' => $categoryQuantities,
            'is_unregistered' => true,
            'not_found_notice' => '',
            'resolved_name' => '',
            'resolved_meta' => '',
            'covered_dependents_count' => null,
            'family_members_count' => null,
            'person_id' => null,
            'guardian_id' => null,
            'qr_token' => '',
        ];
    }
}
