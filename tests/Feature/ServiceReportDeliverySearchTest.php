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
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportDeliverySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_beneficiary_name_folding_variants(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id]);

        // Alef folding: «ارمان» finds «آرمان رضایی».
        $this->assertSame(1, $this->searchTotal($component, 'ارمان'));

        // ZWNJ + space-insensitivity: «محمدحسین» finds «محمد حسین‌زاده».
        $this->assertSame(1, $this->searchTotal($component, 'محمدحسین'));

        // Mid-string part of the name (last name).
        $this->assertSame(1, $this->searchTotal($component, 'حسین'));
    }

    public function test_search_matches_beneficiary_codes_exactly_and_by_prefix(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id]);

        // Full national id is an exact match; partial input a prefix match.
        $this->assertSame(1, $this->searchTotal($component, '1234567890'));
        $this->assertSame(1, $this->searchTotal($component, '80045'));

        // Persian digits normalize to Latin before matching.
        $this->assertSame(1, $this->searchTotal($component, '۷۰۰۱۲۳'));
    }

    public function test_search_ignores_fields_outside_name_and_codes(): void
    {
        [$user, $service] = $this->fixture();

        $this->actingAs($user);

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $service->id]);

        // Notes, mobile and category names are no longer searchable.
        $this->assertSame(0, $this->searchTotal($component, 'محرمانه'));
        $this->assertSame(0, $this->searchTotal($component, '0912000000'));
        $this->assertSame(0, $this->searchTotal($component, 'بسته'));
    }

    private function searchTotal(Testable $component, string $term): int
    {
        return $component->set('deliverySearch', $term)->instance()->deliveryGroups->total();
    }

    /**
     * @return array{0: User, 1: Service}
     */
    private function fixture(): array
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $serviceName = ServiceName::query()->create([
            'name' => 'Search Service '.Str::random(8),
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

        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'بسته تحصیلی',
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $first = $this->personWithDelivery($service, $category, $user, 'آرمان', 'رضایی', '700123', '1234567890');
        $second = $this->personWithDelivery($service, $category, $user, 'محمد', 'حسین‌زاده', '800456', '0987654321');

        $this->assertNotNull($first->id);
        $this->assertNotNull($second->id);

        return [$user, $service];
    }

    private function personWithDelivery(
        Service $service,
        ServiceCategory $category,
        User $user,
        string $firstName,
        string $lastName,
        string $personCode,
        string $nationalId,
    ): Person {
        $guardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => $nationalId,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $person = Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => $personCode,
            'national_id' => $nationalId,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => $nationalId,
            'full_name' => $firstName.' '.$lastName,
            'mobile' => '0912000000',
            'notes' => 'محرمانه',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        return $person;
    }
}
