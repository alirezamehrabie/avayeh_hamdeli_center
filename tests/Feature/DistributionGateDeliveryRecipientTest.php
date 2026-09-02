<?php

namespace Tests\Feature;

use App\Exports\GateEntryReportExport;
use App\Livewire\Admin\GateReport;
use App\Livewire\DistributionOperators\Gates\DeliveryGate;
use App\Livewire\DistributionOperators\Gates\EntryGate;
use App\Models\GateEntryAssignment;
use App\Models\GateEntryDeliveryRecipient;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionGateDeliveryRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_delivery_records_nothing(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('1234567890', '15001');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('isProxyDelivery', false)
            ->call('confirmPermission')
            ->assertSet('scanStatus', 'scanning');

        $this->assertDatabaseCount('gate_entry_delivery_recipients', 0);
    }

    public function test_selecting_a_relation_persists_the_declaration_for_the_subject(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('2234567890', '15002');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_MOTHER)
            // The header chip summarises the current choice for the operator.
            ->assertSee('تحویل به: مادر');

        $this->assertDatabaseHas('gate_entry_delivery_recipients', [
            'service_id' => $service->id,
            'person_id' => $person->id,
            'guardian_id' => null,
            'is_proxy_delivery' => true,
            'recipient_type' => GateEntryDeliveryRecipient::TYPE_MOTHER,
            'recipient_name' => null,
            'created_by' => $operator->id,
        ]);

        // Re-scanning the same subject restores what was recorded instead of resetting to direct.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('isProxyDelivery', true)
            ->assertSet('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_MOTHER);
    }

    public function test_other_recipient_stores_the_typed_name(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('3234567890', '15003');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_OTHER)
            ->set('proxyRecipientName', '  مریم رضایی - همسایه  ')
            ->call('confirmPermission')
            ->assertSet('scanStatus', 'scanning');

        $record = GateEntryDeliveryRecipient::query()->sole();

        $this->assertSame(GateEntryDeliveryRecipient::TYPE_OTHER, $record->recipient_type);
        $this->assertSame('مریم رضایی - همسایه', $record->recipient_name);
        $this->assertSame('مریم رضایی - همسایه', $record->recipient_label);
    }

    public function test_switching_away_from_other_clears_the_stale_name(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('4234567890', '15004');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_OTHER)
            ->set('proxyRecipientName', 'همسایه')
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_GROUP_LEADER)
            ->assertSet('proxyRecipientName', '');

        $record = GateEntryDeliveryRecipient::query()->sole();

        $this->assertSame(GateEntryDeliveryRecipient::TYPE_GROUP_LEADER, $record->recipient_type);
        $this->assertNull($record->recipient_name);
        $this->assertSame('سرگروه (یاریگر)', $record->recipient_label);
    }

    public function test_unchecking_the_box_reverts_the_declaration_to_a_direct_handover(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('5234567890', '15005');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_FATHER)
            ->set('isProxyDelivery', false)
            ->assertSet('proxyRecipientType', '');

        // The row stays as an audit trail of the correction, flipped back to a direct handover.
        $record = GateEntryDeliveryRecipient::query()->sole();

        $this->assertFalse($record->is_proxy_delivery);
        $this->assertNull($record->recipient_type);
        $this->assertSame($operator->id, $record->updated_by);
        $this->assertSame('', $record->recipient_label);
    }

    public function test_confirming_with_an_incomplete_declaration_holds_the_operator_on_the_subject(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('6234567890', '15006');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        // Checked but no relation picked.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->call('confirmPermission')
            ->assertSet('scanStatus', 'paused')
            ->assertSet('scannedPersonId', $person->id)
            ->assertSee('گیرنده خدمت را انتخاب کنید.');

        // "Other" with no name typed.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_OTHER)
            ->call('confirmPermission')
            ->assertSet('scanStatus', 'paused')
            ->assertSee('نام و مشخصات گیرنده را وارد کنید.');
    }

    public function test_guardian_subjects_record_the_declaration_on_the_household(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);

        $guardian = Guardian::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Moradi',
            'national_code' => '7234567890',
            'guardian_code' => 815,
        ]);

        $token = $this->qrTokenFor($guardian, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_GROUP_LEADER);

        $this->assertDatabaseHas('gate_entry_delivery_recipients', [
            'service_id' => $service->id,
            'person_id' => null,
            'guardian_id' => $guardian->id,
            'is_proxy_delivery' => true,
            'recipient_type' => GateEntryDeliveryRecipient::TYPE_GROUP_LEADER,
        ]);
    }

    public function test_downstream_gates_surface_the_declaration_on_the_identity_card(): void
    {
        $operator = $this->allGatesOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('8234567890', '15007');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_MOTHER);

        $delivery = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token);

        $this->assertSame('مادر', $delivery->get('lastScanResult')['proxy_recipient']['label']);
        $delivery->assertSee('تحویل به غیر از مددجو');
    }

    public function test_report_filters_and_lists_the_declaration(): void
    {
        $operator = $this->inboundOperator();
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $proxyPerson = $this->makePerson('9234567890', '15008', 'Proxied', 'Recipient');
        $directPerson = $this->makePerson('9334567890', '15009', 'Direct', 'Recipient');

        foreach ([$proxyPerson, $directPerson] as $person) {
            GateEntryAssignment::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'person_id' => $person->id,
                'national_id' => $person->national_id,
                'full_name' => trim($person->first_name.' '.$person->last_name),
                'status' => GateEntryAssignment::STATUS_PENDING,
                'assigned_at' => now(),
                'created_by' => $operator->id,
            ]);
        }

        GateEntryDeliveryRecipient::query()->create([
            'service_id' => $service->id,
            'person_id' => $proxyPerson->id,
            'is_proxy_delivery' => true,
            'recipient_type' => GateEntryDeliveryRecipient::TYPE_FATHER,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(GateReport::class)
            ->call('selectService', $service->id)
            ->assertSee('تحویل به غیر از مددجو')
            ->assertSee('Proxied Recipient')
            ->assertSee('Direct Recipient');

        $component->set('selectedDeliveryMethod', 'proxy')
            ->assertSee('Proxied Recipient')
            ->assertDontSee('Direct Recipient');

        $component->set('selectedDeliveryMethod', 'direct')
            ->assertSee('Direct Recipient')
            ->assertDontSee('Proxied Recipient');

        // Narrowing to a specific relation keeps only the matching declaration.
        $component->set('selectedDeliveryMethod', GateEntryDeliveryRecipient::TYPE_FATHER)
            ->assertSee('Proxied Recipient')
            ->assertDontSee('Direct Recipient');

        $component->set('selectedDeliveryMethod', GateEntryDeliveryRecipient::TYPE_MOTHER)
            ->assertDontSee('Proxied Recipient')
            ->assertDontSee('Direct Recipient');

        $component->call('clearFilters')->assertSet('selectedDeliveryMethod', 'all');
    }

    public function test_delivery_and_exit_tabs_filter_by_delivery_method_too(): void
    {
        $operator = $this->inboundOperator();
        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
        ]);

        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $proxyPerson = $this->makePerson('4334567890', '15013', 'Proxied', 'Recipient');
        $directPerson = $this->makePerson('5334567890', '15014', 'Direct', 'Recipient');

        foreach ([$proxyPerson, $directPerson] as $person) {
            $assignment = GateEntryAssignment::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'person_id' => $person->id,
                'national_id' => $person->national_id,
                'full_name' => trim($person->first_name.' '.$person->last_name),
                'status' => GateEntryAssignment::STATUS_FINALIZED,
                'assigned_at' => now(),
                'delivered_at' => now(),
                'delivered_by' => $operator->id,
                'created_by' => $operator->id,
            ]);

            ServiceDelivery::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'gate_entry_assignment_id' => $assignment->id,
                'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
                'person_id' => $person->id,
                'national_id' => $person->national_id,
                'full_name' => trim($person->first_name.' '.$person->last_name),
                'delivered_quantity' => 1,
                'value_per_unit_snapshot' => 10,
                'delivered_total_value' => 10,
                'delivered_at' => now()->toDateString(),
                'created_by' => $operator->id,
            ]);
        }

        GateEntryDeliveryRecipient::query()->create([
            'service_id' => $service->id,
            'person_id' => $proxyPerson->id,
            'is_proxy_delivery' => true,
            'recipient_type' => GateEntryDeliveryRecipient::TYPE_MOTHER,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($admin);

        // The delivery tab reads gate_entry_assignments; the exit tab reads service_deliveries. Both
        // carry service_id + person_id/guardian_id, so the same correlated filter must work on each.
        foreach (['delivery', 'exit'] as $tab) {
            $component = Livewire::test(GateReport::class)
                ->call('selectService', $service->id)
                ->call('setActiveTab', $tab)
                ->assertSee('Proxied Recipient')
                ->assertSee('Direct Recipient');

            $component->set('selectedDeliveryMethod', 'proxy')
                ->assertSee('Proxied Recipient')
                ->assertDontSee('Direct Recipient');

            $component->set('selectedDeliveryMethod', 'direct')
                ->assertSee('Direct Recipient')
                ->assertDontSee('Proxied Recipient');
        }
    }

    public function test_concurrent_declaration_for_the_same_subject_converges_without_crashing(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('1334567890', '15010');
        $token = $this->qrTokenFor($person, $operator);

        // Simulate a second station inserting the row inside the read/write window: fire exactly once,
        // mid-create, via a raw insert (no model events → no recursion) so our create() then collides
        // with the (service_id, person_id) unique index.
        $injected = false;
        GateEntryDeliveryRecipient::creating(function () use (&$injected, $service, $person, $operator): void {
            if ($injected) {
                return;
            }

            $injected = true;

            DB::table('gate_entry_delivery_recipients')->insert([
                'service_id' => $service->id,
                'person_id' => $person->id,
                'guardian_id' => null,
                'is_proxy_delivery' => true,
                'recipient_type' => GateEntryDeliveryRecipient::TYPE_MOTHER,
                'created_by' => $operator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $this->actingAs($operator);

            Livewire::test(EntryGate::class)
                ->call('selectService', $service->id)
                ->call('resolveScannedQr', $token)
                ->set('isProxyDelivery', true)
                ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_FATHER);
        } finally {
            app('events')->forget('eloquent.creating: '.GateEntryDeliveryRecipient::class);
        }

        // Exactly one row for the key, holding this operator's choice.
        $record = GateEntryDeliveryRecipient::query()->sole();
        $this->assertSame(GateEntryDeliveryRecipient::TYPE_FATHER, $record->recipient_type);
    }

    public function test_an_incomplete_declaration_is_not_written(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $person = $this->makePerson('3334567890', '15012');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        // Ticking the box, and picking "other" without a name, must leave nothing behind: a row with
        // no recipient would read as "handed to somebody, unknown who" in the reports.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set('isProxyDelivery', true)
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_OTHER);

        $this->assertDatabaseCount('gate_entry_delivery_recipients', 0);
    }

    public function test_the_delivery_method_box_renders_above_the_item_checklist(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson('6334567890', '15015');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->assertSee('نحوه تحویل')
            ->assertSee('برای ثبت نحوه تحویل، ابتدا QR فرد را اسکن کنید.');

        $html = $component->call('resolveScannedQr', $token)
            ->assertSee('تحویل به غیر از مددجو')
            ->assertSee('مادر')
            ->assertSee('پدر')
            ->assertSee('سرگروه (یاریگر)')
            ->assertSee('سایر')
            ->html();

        $this->assertLessThan(
            mb_strpos($html, 'دسته‌بندی‌های خدمت'),
            mb_strpos($html, 'نحوه تحویل'),
            'The delivery-method box must render before the item-selection checklist.',
        );
    }

    public function test_toggling_the_delivery_method_keeps_the_authorized_items(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson('7334567890', '15016');
        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        // The delivery-method box re-renders the gate; the item checklist must survive that round-trip.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [$category->id])
            ->set('isProxyDelivery', true)
            ->assertSet('assignedCategoryIds', [$category->id])
            ->set('proxyRecipientType', GateEntryDeliveryRecipient::TYPE_FATHER)
            ->assertSet('assignedCategoryIds', [$category->id])
            ->assertSet('scannedPersonId', $person->id);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'deleted_at' => null,
        ]);
    }

    public function test_entry_export_carries_the_delivery_method_columns(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);
        $person = $this->makePerson('2334567890', '15011', 'Proxied', 'Recipient');

        $assignment = GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'national_id' => $person->national_id,
            'full_name' => 'Proxied Recipient',
            'status' => GateEntryAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'created_by' => $operator->id,
        ]);

        GateEntryDeliveryRecipient::query()->create([
            'service_id' => $service->id,
            'person_id' => $person->id,
            'is_proxy_delivery' => true,
            'recipient_type' => GateEntryDeliveryRecipient::TYPE_OTHER,
            'recipient_name' => 'مریم رضایی',
            'created_by' => $operator->id,
        ]);

        $export = new GateEntryReportExport(GateEntryAssignment::query()->whereKey($assignment->id), $service);

        $headings = $export->headings();
        $this->assertContains('نحوه تحویل', $headings);
        $this->assertContains('گیرنده', $headings);

        $row = $export->map($assignment->fresh());
        $methodIndex = array_search('نحوه تحویل', $headings, true);

        $this->assertSame('تحویل به غیر از مددجو', $row[$methodIndex]);
        $this->assertSame('مریم رضایی', $row[$methodIndex + 1]);
        $this->assertCount(count($headings), $row);
    }

    protected function makePerson(string $nationalId, string $personCode, string $first = 'Ali', string $last = 'Ahmadi'): Person
    {
        return Person::query()->create([
            'first_name' => $first,
            'last_name' => $last,
            'national_id' => $nationalId,
            'person_code' => $personCode,
        ]);
    }

    protected function qrTokenFor(Person|Guardian $subject, User $operator): string
    {
        $issued = app(QrIdentityService::class)->issueFor($subject, $operator->id);

        return $issued['token'] ?? $issued['identity']->token_encrypted;
    }

    protected function inboundOperator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_INBOUND_GATE],
        ]);
    }

    protected function allGatesOperator(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [
                User::PERMISSION_DISTRIBUTION_INBOUND_GATE,
                User::PERMISSION_DISTRIBUTION_DELIVERY_GATE,
                User::PERMISSION_DISTRIBUTION_OUTBOUND_GATE,
            ],
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
