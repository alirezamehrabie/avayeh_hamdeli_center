<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportRecipientCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_count_deduplicates_person_guardian_and_manual_recipients(): void
    {
        [$user, $service] = $this->serviceWithMixedDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertViewHas('deliveryRecipientCount', fn (int $count): bool => $count === 4);
    }

    public function test_recipient_count_ignores_delivery_filters(): void
    {
        [$user, $service] = $this->serviceWithMixedDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliverySearch', 'No Match')
            ->assertViewHas('deliveryRecipientCount', fn (int $count): bool => $count === 4);
    }

    public function test_detail_header_shows_recipient_count(): void
    {
        [$user, $service] = $this->serviceWithMixedDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertSee('تعداد تحویل:')
            ->assertSee('4 نفر');
    }

    /**
     * Six deliveries across four distinct recipients:
     * one person (x2), one guardian (x1), one manual national id (x2), one manual without id (x1).
     *
     * @return array{0: User, 1: Service}
     */
    private function serviceWithMixedDeliveries(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $service = $this->createService($user);
        $category = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Pack '.Str::random(8),
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Moradi',
            'national_code' => '2234567890',
            'guardian_code' => 701,
            'guardian_phone_number' => '09121234567',
        ]);

        $this->deliver($service, $category, $user, ['person_id' => $person->id]);
        $this->deliver($service, $category, $user, ['person_id' => $person->id]);
        $this->deliver($service, $category, $user, ['guardian_id' => $guardian->id]);
        $this->deliver($service, $category, $user, ['national_id' => '9876543210', 'full_name' => 'Manual Same Id']);
        $this->deliver($service, $category, $user, ['national_id' => '9876543210', 'full_name' => 'Manual Same Id']);
        $this->deliver($service, $category, $user, ['full_name' => 'Manual No Id']);

        return [$user, $service->fresh()];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function deliver(Service $service, ServiceCategory $category, User $user, array $attributes): ServiceDelivery
    {
        return ServiceDelivery::query()->create(array_merge([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ], $attributes));
    }

    private function createService(User $user): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Counted Service '.Str::random(8),
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
}
