<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\Gates\EntryGate;
use App\Models\GateEntryAssignment;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionOperatorEntryGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_inbound_gate_permission_is_forbidden(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)->assertForbidden();
    }

    public function test_selecting_a_service_locks_it_for_the_session(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->assertSet('selectedServiceId', null)
            ->call('selectService', $service->id)
            ->assertSet('selectedServiceId', $service->id)
            ->call('selectService', 99999)
            ->assertSet('selectedServiceId', $service->id);
    }

    public function test_scanning_person_qr_displays_identity_and_category_toggle_persists(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('scannedSubjectType', QrIdentity::SUBJECT_PERSON)
            ->assertSet('scannedPersonId', $person->id)
            ->assertSet('scanStatus', 'paused')
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [$category->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'guardian_id' => null,
            'status' => GateEntryAssignment::STATUS_PENDING,
        ]);

        $component->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', []);

        $this->assertSoftDeleted('gate_entry_assignments', [
            'service_category_id' => $category->id,
            'person_id' => $person->id,
        ]);
    }

    public function test_selected_service_is_restored_from_the_url_on_reload(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::withQueryParams(['service' => $service->id])
            ->test(EntryGate::class)
            ->assertSet('selectedServiceId', $service->id);
    }

    public function test_stale_service_id_in_url_is_discarded(): void
    {
        [$operator] = $this->operator();

        $this->actingAs($operator);

        Livewire::withQueryParams(['service' => 99999])
            ->test(EntryGate::class)
            ->assertSet('selectedServiceId', null);
    }

    public function test_service_search_filters_the_selection_grid(): void
    {
        [$operator] = $this->operator();
        $rice = $this->makeGateService($operator, 'Rice basket');
        $blanket = $this->makeGateService($operator, 'Winter blanket');

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->assertSee($rice->name)
            ->assertSee($blanket->name)
            ->set('serviceSearch', 'Rice')
            ->assertSee($rice->name)
            ->assertDontSee($blanket->name)
            ->call('clearServiceSearch')
            ->assertSet('serviceSearch', '')
            ->assertSee($blanket->name);
    }

    public function test_invalid_qr_sets_scan_error(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', 'not-a-real-token')
            ->assertSet('scanStatus', 'scan_error')
            ->assertSet('scannedPersonId', null);
    }

    /**
     * @return array{0: User}
     */
    protected function operator(): array
    {
        return [User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ])];
    }

    protected function makeGateService(User $creator, string $name = 'Gate package'): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'supports_activity_delivery' => false,
            'description' => 'Gate package',
            'total_quantity' => 10,
            'total_service_value' => 100,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => now()->addDay()->toDateString(),
            'priority' => 'normal',
            'status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }

    protected function makeCategory(Service $service, string $name, User $creator): ServiceCategory
    {
        return ServiceCategory::query()->create([
            'service_id' => $service->id,
            'service_name_id' => $service->service_name_id,
            'name' => $name,
            'quantity' => $service->total_quantity,
            'unit' => 'pack',
            'value' => 10,
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);
    }
}
