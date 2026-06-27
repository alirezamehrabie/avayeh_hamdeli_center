<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\Gates\DeliveryGate;
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

class DistributionOperatorDeliveryGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_delivery_gate_permission_is_forbidden(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);

        $this->actingAs($operator);

        Livewire::test(DeliveryGate::class)->assertForbidden();
    }

    public function test_selecting_a_service_locks_it_for_the_session(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::test(DeliveryGate::class)
            ->assertSet('selectedServiceId', null)
            ->call('selectService', $service->id)
            ->assertSet('selectedServiceId', $service->id)
            ->call('selectService', 99999)
            ->assertSet('selectedServiceId', $service->id);
    }

    public function test_scanning_shows_only_entry_gate_authorized_items_and_delivery_toggle_persists(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $authorized = $this->makeCategory($service, 'Food basket', $operator);
        $unauthorized = $this->makeCategory($service, 'Blanket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        // Only the food basket was approved at the Entry Gate.
        $assignment = $this->assign($service, $authorized, $person, $operator);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('scannedSubjectType', QrIdentity::SUBJECT_PERSON)
            ->assertSet('scannedPersonId', $person->id)
            ->assertSet('scanStatus', 'paused')
            ->assertSet('deliveredCategoryIds', [])
            ->assertSee('Food basket')
            ->assertDontSee('Blanket');

        // Unauthorized category has no assignment — toggling it is a no-op.
        $component->call('toggleDelivered', $unauthorized->id)
            ->assertSet('deliveredCategoryIds', []);

        $component->call('toggleDelivered', $authorized->id)
            ->assertSet('deliveredCategoryIds', [$authorized->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_DELIVERED,
        ]);

        // Reversible: tapping again reverts to pending.
        $component->call('toggleDelivered', $authorized->id)
            ->assertSet('deliveredCategoryIds', []);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_PENDING,
        ]);
    }

    public function test_existing_delivered_status_is_reflected_on_scan(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Rahimi',
            'national_id' => '2234567890',
            'person_code' => '14010',
        ]);

        $this->assign($service, $category, $person, $operator, GateEntryAssignment::STATUS_DELIVERED);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('deliveredCategoryIds', [$category->id]);
    }

    public function test_finalized_item_is_locked_and_cannot_be_reverted_to_delivered(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Hadi',
            'last_name' => 'Moradi',
            'national_id' => '6234567890',
            'person_code' => '14030',
        ]);

        // Already exited and finalized at the Exit Gate.
        $assignment = $this->assign($service, $category, $person, $operator, GateEntryAssignment::STATUS_FINALIZED);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            // Finalized items surface as locked, never as toggleable delivered items.
            ->assertSet('finalizedCategoryIds', [$category->id])
            ->assertSet('deliveredCategoryIds', [])
            ->assertSee('خروج نهایی شده');

        // Toggling a finalized item is a no-op: it must not drop back to delivered.
        $component->call('toggleDelivered', $category->id)
            ->assertSet('deliveredCategoryIds', [])
            ->assertSet('finalizedCategoryIds', [$category->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_FINALIZED,
        ]);
    }

    public function test_scanning_subject_without_assignments_shows_empty_state(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Mina',
            'last_name' => 'Norouzi',
            'national_id' => '4234567890',
            'person_code' => '14012',
        ]);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('scannedPersonId', $person->id)
            ->assertSet('deliveredCategoryIds', [])
            ->assertSee('قلمی برای این خدمت ثبت نشده است');
    }

    public function test_invalid_qr_sets_scan_error(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::test(DeliveryGate::class)
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
            'permissions' => [User::PERMISSION_DISTRIBUTION_DELIVERY_GATE],
        ])];
    }

    protected function issueToken(Person $person, User $operator): string
    {
        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);

        return $issued['token'] ?? $issued['identity']->token_encrypted;
    }

    protected function assign(
        Service $service,
        ServiceCategory $category,
        Person $person,
        User $creator,
        string $status = GateEntryAssignment::STATUS_PENDING,
    ): GateEntryAssignment {
        return GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'guardian_id' => null,
            'national_id' => $person->national_id,
            'full_name' => trim($person->first_name.' '.$person->last_name),
            'status' => $status,
            'assigned_at' => now(),
            'created_by' => $creator->id,
        ]);
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
