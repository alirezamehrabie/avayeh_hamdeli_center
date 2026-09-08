<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Guardian;
use App\Models\Person;
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
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
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
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
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
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
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
            ->call('selectDeliveryChannel', Service::DELIVERY_CHANNEL_GATE)
            ->assertViewHas('socialWorkerOptions', fn (array $options): bool => collect($options)->contains(
                fn (array $option): bool => $option['id'] === $worker->id
            ));
    }

    public function test_detail_page_coverage_filter_returns_recipients_of_the_assigned_worker(): void
    {
        [$user, $worker, , $deliveredService] = $this->scenario();

        $this->actingAs($user);

        $category = $deliveredService->categories()->first();
        $otherWorkerId = SocialWorker::query()->where('worker_code', 412)->value('id');

        $coveredGuardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => '5556667778',
            'first_name' => 'Covered',
            'last_name' => 'Household',
            'social_worker_id' => $worker->id,
        ]);

        $coveredPerson = Person::query()->create([
            'guardian_id' => $coveredGuardian->id,
            'person_code' => (string) random_int(1000000, 9999999),
            'national_id' => (string) random_int(1000000000, 9999999999),
            'first_name' => 'Covered',
            'last_name' => 'Child',
        ]);

        $otherGuardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => '6667778889',
            'first_name' => 'Other',
            'last_name' => 'Household',
            'social_worker_id' => $otherWorkerId,
        ]);

        // Family delivery for the covered guardian, individual delivery for
        // their child (coverage reached via person.guardian), a family
        // delivery for the other worker's guardian, and a manual record
        // belonging to the scenario (no guardian at all).
        $this->createDelivery($deliveredService, $category, $user, ['guardian_id' => $coveredGuardian->id], 'Covered Guardian');
        $this->createDelivery($deliveredService, $category, $user, ['person_id' => $coveredPerson->id], 'Covered Child');
        $this->createDelivery($deliveredService, $category, $user, ['guardian_id' => $otherGuardian->id], 'Other Guardian');

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $deliveredService->id]);

        // Coverage options list exactly the workers assigned to this service's recipient guardians.
        $component->assertViewHas('coverageSocialWorkerOptions', fn (array $options): bool => collect($options)->pluck('id')->sort()->values()->all() === [$worker->id, $otherWorkerId]);

        $component->set('selectedCoverageSocialWorker', (string) $worker->id);

        $groups = $component->instance()->deliveryGroups;

        // The «یافت شد» count is the same paginator total, so it must agree.
        $this->assertSame(2, $groups->total());
        $this->assertSame(
            ['Covered Child', 'Covered Guardian'],
            collect($groups->items())->pluck('recipientName')->sort()->values()->all()
        );

        // Clearing restores every recipient group, including the manual record.
        $component->set('selectedCoverageSocialWorker', 'all');
        $this->assertSame(4, $component->instance()->deliveryGroups->total());

        // Combined with the entry-type filter, coverage still narrows correctly.
        $component->set('selectedDeliveryEntryType', 'individual');
        $component->set('selectedCoverageSocialWorker', (string) $worker->id);
        $this->assertSame(1, $component->instance()->deliveryGroups->total());
    }

    public function test_detail_records_fall_back_to_the_covering_guardian_worker_name(): void
    {
        [$user, , , $deliveredService] = $this->scenario();

        $this->actingAs($user);

        $category = $deliveredService->categories()->first();

        $familyWorker = $this->createWorker(413, 'Bahar');
        $personWorker = $this->createWorker(414, 'Kian');

        $familyGuardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => '5556667008',
            'first_name' => 'Family',
            'last_name' => 'Household',
            'social_worker_id' => $familyWorker->id,
        ]);

        $personGuardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => '6667778009',
            'first_name' => 'Person',
            'last_name' => 'Household',
            'social_worker_id' => $personWorker->id,
        ]);

        $person = Person::query()->create([
            'guardian_id' => $personGuardian->id,
            'person_code' => (string) random_int(1000000, 9999999),
            'national_id' => (string) random_int(1000000000, 9999999999),
            'first_name' => 'Person',
            'last_name' => 'Beneficiary',
        ]);

        // Deliveries without a stamped worker: the family row resolves through
        // its guardian, the individual row through the person's guardian.
        $this->createDelivery($deliveredService, $category, $user, ['guardian_id' => $familyGuardian->id], 'Family Row');
        $this->createDelivery($deliveredService, $category, $user, ['person_id' => $person->id], 'Individual Row');

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $deliveredService->id])
            ->assertSee($familyWorker->full_name)
            ->assertSee($personWorker->full_name);
    }

    private function createWorker(int $code, string $firstName): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => $firstName,
            'last_name' => 'Covering '.Str::random(6),
            'is_active' => true,
        ]);
    }

    private function createDelivery(
        Service $service,
        ServiceCategory $category,
        User $user,
        array $recipient,
        string $fullName,
    ): ServiceDelivery {
        return ServiceDelivery::query()->create($recipient + [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => (string) random_int(1000000000, 9999999999),
            'full_name' => $fullName,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
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
