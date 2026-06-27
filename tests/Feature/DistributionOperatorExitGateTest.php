<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\Gates\ExitGate;
use App\Models\GateEntryAssignment;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionOperatorExitGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_exit_gate_permission_is_forbidden(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_DELIVERY_GATE],
        ]);

        $this->actingAs($operator);

        Livewire::test(ExitGate::class)->assertForbidden();
    }

    public function test_scan_shows_only_delivered_items_and_offers_finalize(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $delivered = $this->makeCategory($service, 'Food basket', $operator);
        $pendingOnly = $this->makeCategory($service, 'Blanket', $operator);

        $person = $this->makePerson();

        // Delivered at the Delivery Gate vs. still pending — only the delivered one shows here.
        $this->assign($service, $delivered, $person, $operator, GateEntryAssignment::STATUS_DELIVERED);
        $this->assign($service, $pendingOnly, $person, $operator, GateEntryAssignment::STATUS_PENDING);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        Livewire::test(ExitGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('scannedPersonId', $person->id)
            ->assertSet('scanStatus', 'paused')
            ->assertSee('Food basket')
            ->assertDontSee('Blanket')
            ->assertSee('تأیید خروج و ثبت نهایی تحویل');
    }

    public function test_finalize_commits_permanent_delivery_and_locks_assignment(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson();

        $assignment = $this->assign($service, $category, $person, $operator, GateEntryAssignment::STATUS_DELIVERED);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        Livewire::test(ExitGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->call('finalizeExit')
            ->assertSee('خروج تأیید');

        // The assignment is locked to finalized.
        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_FINALIZED,
        ]);

        // A permanent ServiceDelivery is written via the gate channel with the category value snapshot.
        $this->assertDatabaseHas('service_deliveries', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'social_worker_id' => null,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 10,
            'delivered_total_value' => 10,
            'created_by' => $operator->id,
        ]);

        $this->assertSame(1, ServiceDelivery::query()->count());
    }

    public function test_finalize_is_idempotent_and_shows_locked_state_on_rescan(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson();

        $this->assign($service, $category, $person, $operator, GateEntryAssignment::STATUS_DELIVERED);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(ExitGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->call('finalizeExit')
            // Second finalize call finds no delivered rows — no duplicate is written.
            ->call('finalizeExit');

        $this->assertSame(1, ServiceDelivery::query()->count());

        // Rescanning the same subject now shows the locked "already exited" state, no finalize button.
        $component->call('resumeScanning')
            ->call('resolveScannedQr', $token)
            ->assertSee('قبلاً از گیت خروج تأیید شده است')
            ->assertDontSee('تأیید خروج و ثبت نهایی تحویل');
    }

    public function test_scanning_subject_without_delivered_items_offers_no_finalize(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson();

        // Only a pending (not yet delivered) assignment exists.
        $this->assign($service, $category, $person, $operator, GateEntryAssignment::STATUS_PENDING);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        Livewire::test(ExitGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSee('قلمی برای این خدمت در گیت تحویل ثبت نشده است')
            ->assertDontSee('تأیید خروج و ثبت نهایی تحویل');

        $this->assertSame(0, ServiceDelivery::query()->count());
    }

    /**
     * @return array{0: User}
     */
    protected function operator(): array
    {
        return [User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE],
        ])];
    }

    protected function makePerson(): Person
    {
        return Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);
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
