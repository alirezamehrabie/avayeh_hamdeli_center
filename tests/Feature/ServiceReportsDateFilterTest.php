<?php

namespace Tests\Feature;

use App\Helpers\Morilog\Jalalian;
use App\Livewire\Services\ServiceReports;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportsDateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_range_filters_deliveries_in_service_report(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 3)
            ->set('deliveryDateFrom', '2026-08-10')
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 2
                && $deliveries->every(fn (ServiceDelivery $delivery): bool => $delivery->delivered_at->toDateString() >= '2026-08-10'))
            ->set('deliveryDateTo', '2026-08-19')
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 1
                && $deliveries->first()->delivered_at->toDateString() === '2026-08-15')
            ->set('deliveryDateFrom', '')
            ->set('deliveryDateTo', '')
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 3);
    }

    public function test_jalali_date_inputs_are_converted_to_gregorian_when_filtering(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        $jalaliFrom = Jalalian::fromDateTime('2026-08-10 00:00:00')->format('Y/m/d');
        $jalaliTo = Jalalian::fromDateTime('2026-08-19 00:00:00')->format('Y/m/d');

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliveryDateFrom', $jalaliFrom)
            ->set('deliveryDateTo', $jalaliTo)
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 1
                && $deliveries->first()->delivered_at->toDateString() === '2026-08-15');
    }

    public function test_clear_delivery_filters_resets_search_type_and_dates(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliverySearch', 'Editable')
            ->set('selectedDeliveryEntryType', 'manual')
            ->set('deliveryDateFrom', '2026-08-10')
            ->set('deliveryDateTo', '2026-08-19')
            ->call('clearDeliveryFilters')
            ->assertSet('deliverySearch', '')
            ->assertSet('selectedDeliveryEntryType', 'all')
            ->assertSet('deliveryDateFrom', '')
            ->assertSet('deliveryDateTo', '')
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 3);
    }

    public function test_invalid_date_input_is_ignored_instead_of_throwing(): void
    {
        [$user, $service] = $this->serviceWithDeliveries();

        $this->actingAs($user);

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id])
            ->set('deliveryDateFrom', 'not-a-date')
            ->set('deliveryDateTo', '1404/13/40')
            ->assertViewHas('filteredDeliveries', fn ($deliveries): bool => $deliveries->count() === 3)
            ->assertHasNoErrors();
    }

    public function test_clear_service_filters_resets_search_and_selects(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $service = $this->createServiceWithDeliveries($user, 'Reported Service', ['2026-08-14']);

        Livewire::test(ServiceReports::class)
            ->set('search', $service->serviceName->name)
            ->set('selectedServiceName', $service->serviceName->name)
            ->set('selectedStatus', 'approved')
            ->call('clearServiceFilters')
            ->assertSet('search', '')
            ->assertSet('selectedServiceName', 'all')
            ->assertSet('selectedCategory', 'all')
            ->assertSet('selectedStatus', 'all')
            ->assertSet('selectedType', 'all')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 1);
    }

    public function test_creation_date_range_filters_services_list(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $this->createServiceWithDeliveries($user, 'Older Service', [], '2026-07-01 10:00:00');
        $middle = $this->createServiceWithDeliveries($user, 'Middle Service', [], '2026-08-10 14:30:00');
        $newer = $this->createServiceWithDeliveries($user, 'Newer Service', [], '2026-08-25 09:00:00');

        Livewire::test(ServiceReports::class)
            ->assertViewHas('services', fn ($services): bool => $services->count() === 3)
            ->set('serviceDateFrom', '2026-08-10')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 2
                && $services->contains(fn (Service $service): bool => $service->is($middle))
                && $services->contains(fn (Service $service): bool => $service->is($newer)))
            ->set('serviceDateTo', '2026-08-10')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 1
                && $services->first()->is($middle))
            ->set('serviceDateFrom', '')
            ->set('serviceDateTo', '')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 3);
    }

    public function test_jalali_creation_date_inputs_are_converted_when_filtering_services_list(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $this->createServiceWithDeliveries($user, 'July Service', [], '2026-07-01 10:00:00');
        $august = $this->createServiceWithDeliveries($user, 'August Service', [], '2026-08-10 14:30:00');

        $jalaliFrom = Jalalian::fromDateTime('2026-08-01 00:00:00')->format('Y/m/d');
        $jalaliTo = Jalalian::fromDateTime('2026-08-31 00:00:00')->format('Y/m/d');

        Livewire::test(ServiceReports::class)
            ->set('serviceDateFrom', $jalaliFrom)
            ->set('serviceDateTo', $jalaliTo)
            ->assertViewHas('services', fn ($services): bool => $services->count() === 1
                && $services->first()->is($august));
    }

    public function test_invalid_creation_date_input_is_ignored_instead_of_throwing(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $this->createServiceWithDeliveries($user, 'First Service', [], '2026-07-01 10:00:00');
        $this->createServiceWithDeliveries($user, 'Second Service', [], '2026-08-10 14:30:00');

        Livewire::test(ServiceReports::class)
            ->set('serviceDateFrom', 'not-a-date')
            ->set('serviceDateTo', '1404/13/40')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 2)
            ->assertHasNoErrors();
    }

    public function test_clear_service_filters_resets_creation_date_inputs(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $this->createServiceWithDeliveries($user, 'July Service', [], '2026-07-01 10:00:00');
        $this->createServiceWithDeliveries($user, 'August Service', [], '2026-08-10 14:30:00');

        Livewire::test(ServiceReports::class)
            ->set('serviceDateFrom', '2026-08-10')
            ->set('serviceDateTo', '2026-08-31')
            ->call('clearServiceFilters')
            ->assertSet('serviceDateFrom', '')
            ->assertSet('serviceDateTo', '')
            ->assertViewHas('services', fn ($services): bool => $services->count() === 2);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @param  array<int, string>  $deliveryDates
     * @return array{0: User, 1: Service}
     */
    private function serviceWithDeliveries(): array
    {
        $user = $this->adminUser();

        $service = $this->createServiceWithDeliveries($user, 'Reported Service', ['2026-08-05', '2026-08-15', '2026-08-25']);

        return [$user, $service];
    }

    /**
     * @param  array<int, string>  $deliveryDates
     */
    private function createServiceWithDeliveries(User $user, string $label, array $deliveryDates = [], ?string $createdAt = null): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $label.' '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $service = Service::query()->create([
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

        if ($createdAt !== null) {
            // $fillable does not include timestamps, so they must be force-filled.
            $service->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        if ($deliveryDates !== []) {
            $category = $service->categories()->create([
                'service_name_id' => $serviceName->id,
                'name' => 'Food '.Str::random(8),
                'quantity' => 30,
                'unit' => 'pack',
                'value' => 1000,
                'sort_id' => 1,
                'created_by' => $user->id,
            ]);

            foreach ($deliveryDates as $index => $date) {
                ServiceDelivery::query()->create([
                    'service_id' => $service->id,
                    'service_category_id' => $category->id,
                    'national_id' => '111111111'.$index,
                    'full_name' => 'Recipient '.$index,
                    'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
                    'delivered_quantity' => 1,
                    'value_per_unit_snapshot' => 1000,
                    'delivered_total_value' => 1000,
                    'delivered_at' => $date,
                    'created_by' => $user->id,
                ]);
            }
        }

        return $service->fresh();
    }
}
