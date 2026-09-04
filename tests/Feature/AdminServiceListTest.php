<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceList;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminServiceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_paginates_services_and_never_queries_deliveries_for_rows(): void
    {
        $this->actingAs($this->manager());

        for ($index = 0; $index < 16; $index++) {
            $this->serviceWithDelivery('SN-70'.(100 + $index), 'خدمت تستی '.(100 + $index));
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component = Livewire::test(ServiceList::class);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $component->assertViewHas('services', function ($services): bool {
            return $services instanceof LengthAwarePaginator
                && count($services->items()) === 15
                && $services->total() === 16
                && ! $services->first()->relationLoaded('deliveries');
        });

        $deliveryQueries = array_filter(
            $queries,
            fn (array $query): bool => str_contains($query['query'], 'from `service_deliveries`')
        );

        $this->assertCount(
            0,
            $deliveryQueries,
            'The list must not load delivery history for every row; summaries are loaded on demand.'
        );
    }

    public function test_worker_summary_is_computed_on_demand_for_the_clicked_service_only(): void
    {
        $this->actingAs($this->manager());

        $target = $this->serviceWithDelivery('SN-70200', 'خدمت هدف', withWorker: true);
        $other = $this->serviceWithDelivery('SN-70201', 'خدمت دیگر', withWorker: true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component = Livewire::test(ServiceList::class)
            ->call('showWorkerSummary', $target->id);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $component->assertDispatched('service-workers-loaded', function (string $name, array $params) use ($target): bool {
            $summary = $params['summary'] ?? null;

            return is_array($summary)
                && ($summary['code'] ?? null) === $target->code
                && count($summary['workers'] ?? []) === 1
                && ($summary['workers'][0]['recipients'][0]['name'] ?? null) === 'گیرنده تست';
        });

        $deliveryQueries = array_filter(
            $queries,
            fn (array $query): bool => str_contains($query['query'], 'from `service_deliveries`')
        );

        $this->assertCount(1, $deliveryQueries, 'Only the clicked service deliveries should be loaded.');
        $this->assertStringContainsString((string) $target->id, $deliveryQueries[array_key_first($deliveryQueries)]['query'] ?? '');
    }

    public function test_search_and_status_filter_reset_pagination_to_first_page(): void
    {
        $this->actingAs($this->manager());

        for ($index = 0; $index < 16; $index++) {
            $this->serviceWithDelivery('SN-70'.(300 + $index), 'خدمت فیلتر '.(300 + $index));
        }

        Livewire::test(ServiceList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'خدمت فیلتر 305')
            ->assertSet('paginators.page', 1)
            ->assertSee('SN-70305')
            ->assertDontSee('SN-70300');
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function serviceWithDelivery(string $code, string $name, bool $withWorker = false): Service
    {
        $managerId = auth()->id();

        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $managerId,
        ]);

        $service = Service::query()->create([
            'code' => $code,
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'total_quantity' => 10,
            'total_service_value' => 300000,
            'status' => 'approved',
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->addWeek()->toDateString(),
            'created_by' => $managerId,
        ]);

        $category = ServiceCategory::query()->create([
            'service_name_id' => $serviceName->id,
            'service_id' => $service->id,
            'code' => $code.'-CAT001',
            'name' => $name.' دسته',
            'quantity' => 10,
            'unit' => 'count',
            'value' => 30000,
            'sort_id' => 1,
            'created_by' => $managerId,
        ]);

        $worker = null;

        if ($withWorker) {
            $worker = SocialWorker::withoutGlobalScope('active')->create([
                'worker_code' => ((int) SocialWorker::withoutGlobalScope('active')->max('worker_code') ?? 9) + 1,
                'first_name' => 'مددکار',
                'last_name' => 'تست',
                'is_active' => true,
            ]);

            ServiceWorkerAllocation::query()->create([
                'service_id' => $service->id,
                'social_worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'allocated_quantity' => 10,
            ]);
        }

        $guardian = Guardian::query()->create([
            'guardian_code' => random_int(100000, 999999),
            'first_name' => 'سرپرست',
            'last_name' => 'تست',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_phone_number' => '09120000000',
            'social_worker_id' => $worker?->id,
            'insurance_status' => false,
        ]);

        $person = Person::query()->create([
            'first_name' => 'گیرنده',
            'last_name' => 'تست',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(15000, 99999),
            'guardian_id' => $guardian->id,
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'guardian_id' => $guardian->id,
            'social_worker_id' => $worker?->id,
            'national_id' => $person->national_id,
            'full_name' => trim($person->first_name.' '.$person->last_name),
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 30000,
            'delivered_total_value' => 60000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $managerId,
        ]);

        return $service->fresh();
    }
}
