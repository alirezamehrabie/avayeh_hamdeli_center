<?php

namespace Tests\Feature;

use App\Livewire\Admin\GateTechnicalReport;
use App\Models\GateEntryAssignment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceDeliveryCancellation;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GateTechnicalReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_and_integrity_checks_follow_the_gate_workflow(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        [$service, $category] = $this->gateFixture();

        // Authorized at Entry, never confirmed at Delivery — but once delivered
        // and cancelled at Exit (audit row resets it to pending).
        $pending = $this->assignment($service, $category, GateEntryAssignment::STATUS_PENDING);
        ServiceDeliveryCancellation::query()->create([
            'gate_entry_assignment_id' => $pending->id,
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $pending->person_id,
            'delivered_quantity' => 1,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'delivery_snapshot' => ['full_name' => $pending->full_name],
            'canceled_by' => $user->id,
            'canceled_at' => now(),
        ]);

        // Confirmed at Delivery, not exited yet.
        $this->assignment($service, $category, GateEntryAssignment::STATUS_DELIVERED);

        // Exited with its ledger row.
        $finalized = $this->assignment($service, $category, GateEntryAssignment::STATUS_FINALIZED);
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $finalized->person_id,
            'guardian_id' => $finalized->guardian_id,
            'gate_entry_assignment_id' => $finalized->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => $finalized->national_id,
            'full_name' => $finalized->full_name,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now(),
            'created_by' => $user->id,
        ]);

        // Discrepancy 1: finalized but the ledger row is missing.
        $this->assignment($service, $category, GateEntryAssignment::STATUS_FINALIZED);

        // Discrepancy 2: a gate ledger row that belongs to no Entry authorization.
        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => '9998887776',
            'full_name' => 'Orphan Recipient',
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 2000,
            'delivered_at' => now(),
            'created_by' => $user->id,
        ]);

        Livewire::test(GateTechnicalReport::class, ['service' => $service])
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['pending'] === 1
                && $stats['delivered'] === 3
                && $stats['finalized'] === 2
                && $stats['cancelled'] === 1
                && $stats['discrepancies'] === 2)
            ->assertSee('Orphan Recipient')
            ->assertSee('سابقه لغو در خروج');
    }

    public function test_non_gate_services_are_rejected(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $homeOnly = $this->createService(gate: false);
        $gateService = $this->createService(gate: true);

        $this->get(route('admin.gate-technical-report', ['service' => $homeOnly]))->assertNotFound();
        $this->get(route('admin.gate-technical-report', ['service' => $gateService]))->assertOk();
    }

    public function test_operators_tab_reports_activity_per_gate_operator(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        [$service, $category] = $this->gateFixture();

        $entryOp = $this->operator(User::PERMISSION_DISTRIBUTION_INBOUND_GATE, 'entry');
        $idleEntryOp = $this->operator(User::PERMISSION_DISTRIBUTION_INBOUND_GATE, 'entry-idle');
        $deliveryOp = $this->operator(User::PERMISSION_DISTRIBUTION_DELIVERY_GATE, 'del');
        $exitOp = $this->operator(User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE, 'exit');
        $ghostOp = $this->operator(null, 'ghost');

        $mine1 = $this->assignment($service, $category, GateEntryAssignment::STATUS_PENDING, $entryOp->id);
        $mine2 = $this->assignment($service, $category, GateEntryAssignment::STATUS_DELIVERED, $entryOp->id, $deliveryOp->id);
        $ghost = $this->assignment($service, $category, GateEntryAssignment::STATUS_FINALIZED, $ghostOp->id, $deliveryOp->id);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $ghost->person_id,
            'gate_entry_assignment_id' => $ghost->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => $ghost->national_id,
            'full_name' => $ghost->full_name,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now(),
            'created_by' => $exitOp->id,
        ]);

        ServiceDeliveryCancellation::query()->create([
            'gate_entry_assignment_id' => $ghost->id,
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $ghost->person_id,
            'delivered_quantity' => 1,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'delivery_snapshot' => ['full_name' => $ghost->full_name],
            'canceled_by' => $exitOp->id,
            'canceled_at' => now(),
        ]);

        Livewire::test(GateTechnicalReport::class, ['service' => $service])
            ->call('setTab', 'operators')
            ->assertSet('activeTab', 'operators')
            ->assertViewHas('operatorReports', function (array $reports) use ($entryOp, $idleEntryOp, $deliveryOp, $exitOp, $ghostOp): bool {
                $entry = collect($reports['entry']['operators'])->keyBy('userId');
                $delivery = collect($reports['delivery']['operators'])->keyBy('userId');
                $exit = collect($reports['exit']['operators'])->keyBy('userId');

                return $reports['entry']['activeCount'] === 2
                    && $reports['entry']['idleCount'] === 1
                    && ($entry[$entryOp->id]['totalRecords'] ?? 0) === 2
                    && ($entry[$entryOp->id]['authorized'] ?? false) === true
                    && ($entry[$idleEntryOp->id]['totalRecords'] ?? -1) === 0
                    && ($entry[$idleEntryOp->id]['authorized'] ?? false) === true
                    && ($entry[$ghostOp->id]['authorized'] ?? true) === false
                    && ($delivery[$deliveryOp->id]['totalRecords'] ?? 0) === 2
                    && ($delivery[$deliveryOp->id]['extraTotal'] ?? 0) === 1
                    && ($exit[$exitOp->id]['totalRecords'] ?? 0) === 1
                    && ($exit[$exitOp->id]['cancelledTotal'] ?? 0) === 1;
            })
            ->assertSee('بدون مجوز فعلی');
    }

    private function operator(?string $permission, string $tag): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => $permission === null ? [] : [$permission],
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @return array{0: Service, 1: ServiceCategory}
     */
    private function gateFixture(): array
    {
        $user = auth()->user();
        $service = $this->createService(gate: true);

        $category = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Tech Pack '.Str::random(8),
            'quantity' => 50,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return [$service, $category];
    }

    /**
     * One assignment per call with a fresh subject (the table enforces a
     * unique service + category + person index).
     */
    private function assignment(Service $service, ServiceCategory $category, string $status, ?int $createdBy = null, ?int $deliveredBy = null): GateEntryAssignment
    {
        $nationalId = (string) random_int(1000000000, 9999999999);

        $guardian = Guardian::query()->create([
            'guardian_code' => random_int(1000000, 9999999),
            'national_code' => $nationalId,
            'first_name' => 'Guardian',
            'last_name' => Str::random(6),
        ]);

        $person = Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => (string) random_int(1000000, 9999999),
            'national_id' => $nationalId,
            'first_name' => 'Ali',
            'last_name' => 'Rezaei',
        ]);

        return GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'guardian_id' => $guardian->id,
            'national_id' => $nationalId,
            'full_name' => 'علی رضایی '.$person->person_code,
            'status' => $status,
            'assigned_at' => now()->subHours(3),
            'delivered_at' => $status === GateEntryAssignment::STATUS_PENDING ? null : now()->subHour(),
            'delivered_by' => $status === GateEntryAssignment::STATUS_PENDING ? null : ($deliveredBy ?? auth()->id()),
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    private function createService(bool $gate): Service
    {
        $user = auth()->user();

        $serviceName = ServiceName::query()->create([
            'name' => 'Tech Report Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        return Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => $gate,
            'supports_home_delivery' => true,
            'total_quantity' => 50,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $user->id,
        ]);
    }
}
