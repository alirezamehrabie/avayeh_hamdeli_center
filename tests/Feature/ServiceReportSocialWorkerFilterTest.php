<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportSocialWorkerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_shows_only_services_where_worker_accepted_responsibility(): void
    {
        [$user, $worker, $allocatedService, $deliveredService, $unrelatedService] = $this->scenario();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->set('selectedSocialWorker', (string) $worker->id)
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $services->pluck('id')->sort()->values()->all() === [$allocatedService->id, $deliveredService->id]
                && $services->total() === 2);
    }

    public function test_filter_combines_with_other_service_filters(): void
    {
        [$user, $worker, $allocatedService] = $this->scenario();

        $allocatedService->forceFill(['status' => 'draft'])->save();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->set('selectedSocialWorker', (string) $worker->id)
            ->set('selectedStatus', 'draft')
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $services->pluck('id')->contains($allocatedService->id))
            ->set('selectedStatus', 'completed')
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => ! $services->pluck('id')->contains($allocatedService->id));
    }

    public function test_clearing_the_filter_restores_the_full_list(): void
    {
        [$user, $worker, $allocatedService, $deliveredService, $unrelatedService] = $this->scenario();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->set('selectedSocialWorker', (string) $worker->id)
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $services->total() === 2)
            ->set('selectedSocialWorker', 'all')
            ->assertViewHas('services', fn (LengthAwarePaginator $services): bool => $services->total() === 3)
            ->assertViewHas(
                'services',
                fn (LengthAwarePaginator $services): bool => $services->pluck('id')
                    ->intersect([$allocatedService->id, $deliveredService->id, $unrelatedService->id])
                    ->count() === 3
            );
    }

    public function test_worker_options_include_inactive_workers(): void
    {
        [$user, $worker] = $this->scenario();

        $worker->forceFill(['is_active' => false])->save();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class)
            ->assertViewHas('socialWorkerOptions', fn (array $options): bool => collect($options)->contains(
                fn (array $option): bool => $option['id'] === $worker->id
            ));
    }

    /**
     * One worker with a category allocation, one service carrying only a
     * delivery stamped with the worker, and one untouched service.
     *
     * @return array{0: User, 1: SocialWorker, 2: Service, 3: Service, 4: Service}
     */
    private function scenario(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $worker = SocialWorker::query()->create([
            'worker_code' => 411,
            'first_name' => 'Zahra',
            'last_name' => 'Worker '.Str::random(6),
            'is_active' => true,
        ]);

        $allocatedService = $this->createService($user);
        $category = $this->createCategory($allocatedService, $user);

        ServiceWorkerAllocation::query()->create([
            'service_id' => $allocatedService->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $user->id,
        ]);

        $deliveredService = $this->createService($user);
        $deliveredCategory = $this->createCategory($deliveredService, $user);

        $otherWorker = SocialWorker::query()->create([
            'worker_code' => 412,
            'first_name' => 'Hamed',
            'last_name' => 'Worker '.Str::random(6),
            'is_active' => true,
        ]);

        // Handover state: the quota was originally given to $worker (which the
        // delivery was recorded against), then reassigned to another worker —
        // mirroring ServiceDeliveryManager::saveAllocations wiping and re-adding
        // rows, so $worker now has only the delivery stamped with their id.
        $workerAllocation = ServiceWorkerAllocation::query()->create([
            'service_id' => $deliveredService->id,
            'service_category_id' => $deliveredCategory->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $user->id,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $deliveredService->id,
            'service_category_id' => $deliveredCategory->id,
            'social_worker_id' => $worker->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'national_id' => '3334445556',
            'full_name' => 'Some Recipient',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $workerAllocation->delete();

        ServiceWorkerAllocation::query()->create([
            'service_id' => $deliveredService->id,
            'service_category_id' => $deliveredCategory->id,
            'social_worker_id' => $otherWorker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $user->id,
        ]);

        $unrelatedService = $this->createService($user);

        return [$user, $worker, $allocatedService, $deliveredService, $unrelatedService];
    }

    private function createService(User $user): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'SW-Filtered Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 30,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);
    }

    private function createCategory(Service $service, User $user): ServiceCategory
    {
        return $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Pack '.Str::random(8),
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);
    }
}
