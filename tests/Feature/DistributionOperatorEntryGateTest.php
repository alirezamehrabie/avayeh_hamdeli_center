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
use Illuminate\Support\Facades\DB;
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

    public function test_readding_a_removed_category_restores_the_assignment_without_colliding(): void
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

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [$category->id])
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [])
            // Re-adding the soft-deleted item must restore it, not insert a duplicate that hits the unique index.
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [$category->id]);

        // The original row is restored (not soft-deleted) and there is exactly one — no duplicate insert.
        $this->assertDatabaseHas('gate_entry_assignments', [
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'status' => GateEntryAssignment::STATUS_PENDING,
            'deleted_at' => null,
        ]);

        $this->assertSame(1, GateEntryAssignment::withTrashed()
            ->where('service_category_id', $category->id)
            ->where('person_id', $person->id)
            ->count());

        $component->assertSet('scanStatus', 'paused');
    }

    public function test_toggling_a_category_persists_without_re_rendering_the_gate(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Kazemi',
            'national_id' => '9234567890',
            'person_code' => '14060',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token);

        // The checklist flips on the client, so a toggle must neither ship fresh HTML back nor
        // rebuild the gate's data — that render (service + categories + entry fields + identity
        // card) on every tap was the selection lag.
        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        $component->call('toggleCategory', $category->id);

        $this->assertArrayNotHasKey('html', $component->effects);

        $this->assertEmpty(
            array_filter($statements, fn (string $sql): bool => str_contains($sql, 'service_entry_fields')),
            'Toggling a category must not re-hydrate the selected service with its entry fields.',
        );

        $this->assertDatabaseHas('gate_entry_assignments', [
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'status' => GateEntryAssignment::STATUS_PENDING,
        ]);
    }

    public function test_concurrent_authorization_of_same_item_converges_without_crashing(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Nima',
            'last_name' => 'Yousefi',
            'national_id' => '8234567890',
            'person_code' => '14050',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        // Simulate a second gate station inserting the same (category, person) row inside the TOCTOU window:
        // fire exactly once, mid-create, via a raw insert (no model events → no recursion) so this
        // component's create() then collides with the unique index.
        $injected = false;
        GateEntryAssignment::creating(function () use (&$injected, $service, $category, $person, $operator): void {
            if ($injected) {
                return;
            }

            $injected = true;

            DB::table('gate_entry_assignments')->insert([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'person_id' => $person->id,
                'guardian_id' => null,
                'status' => GateEntryAssignment::STATUS_PENDING,
                'assigned_at' => now(),
                'created_by' => $operator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $this->actingAs($operator);

            // Must converge silently — no UniqueConstraintViolationException bubbling up as a 500.
            Livewire::test(EntryGate::class)
                ->call('selectService', $service->id)
                ->call('resolveScannedQr', $token)
                ->call('toggleCategory', $category->id)
                ->assertSet('assignedCategoryIds', [$category->id]);
        } finally {
            app('events')->forget('eloquent.creating: '.GateEntryAssignment::class);
        }

        // Exactly one active row for the key: we converged to the concurrent insert, not duplicated it.
        $this->assertSame(1, GateEntryAssignment::withTrashed()
            ->where('service_category_id', $category->id)
            ->where('person_id', $person->id)
            ->count());
    }

    public function test_finalized_assignment_is_locked_and_cannot_be_removed_at_entry_gate(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Kaveh',
            'last_name' => 'Tabrizi',
            'national_id' => '7234567890',
            'person_code' => '14040',
        ]);

        // The item already moved through delivery and exit — its ledger record exists downstream.
        $assignment = GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
            'guardian_id' => null,
            'national_id' => $person->national_id,
            'full_name' => trim($person->first_name.' '.$person->last_name),
            'status' => GateEntryAssignment::STATUS_FINALIZED,
            'assigned_at' => now(),
            'created_by' => $operator->id,
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            // Finalized items render as locked, separate from editable (pending) assignments.
            ->assertSet('lockedCategoryIds', [$category->id])
            ->assertSet('assignedCategoryIds', [$category->id])
            ->assertSee('ثبت‌شده');

        // Tapping a locked item must not soft-delete or re-author it.
        $component->call('toggleCategory', $category->id)
            ->assertSet('lockedCategoryIds', [$category->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_FINALIZED,
            'deleted_at' => null,
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

    public function test_scan_result_includes_disambiguating_identity_details(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $person = Person::query()->create([
            'first_name' => 'Omid',
            'last_name' => 'Salehi',
            'national_id' => '5234567899',
            'person_code' => '14020',
            'father_name' => 'Bahram',
            'gender' => 'male',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $result = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->get('lastScanResult');

        $this->assertSame('مددجو', $result['subject_label']);
        $this->assertArrayHasKey('avatar_url', $result);

        $details = collect($result['details']);
        $this->assertSame('Bahram', $details->firstWhere('label', 'نام پدر')['value']);
        $this->assertNotNull($details->firstWhere('label', 'جنسیت'));
    }

    public function test_rescanning_same_person_is_flagged_as_duplicate(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $person = Person::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Rahimi',
            'national_id' => '2234567890',
            'person_code' => '14010',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($operator);

        $component = Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token);

        $this->assertSame('success', $component->get('lastScanResult')['code_key']);

        $component->call('resolveScannedQr', $token);

        $this->assertSame('duplicate', $component->get('lastScanResult')['code_key']);
        $component->assertSet('scannedPersonId', $person->id);
    }

    public function test_revoked_qr_reports_a_distinct_message(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $person = Person::query()->create([
            'first_name' => 'Reza',
            'last_name' => 'Karimi',
            'national_id' => '3234567890',
            'person_code' => '14011',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);
        $identity = $issued['identity'];
        $token = $issued['token'] ?? $identity->token_encrypted;

        app(QrIdentityService::class)->revoke($identity, $operator->id, 'lost card');

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('scanStatus', 'scan_error')
            ->assertSee('ابطال')
            ->assertSet('scannedPersonId', null);
    }

    public function test_manual_search_can_select_a_person_without_scanning(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Mina',
            'last_name' => 'Norouzi',
            'national_id' => '4234567890',
            'person_code' => '14012',
        ]);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('toggleManualSearch')
            ->set('manualSearch', 'Norouzi')
            ->assertSee('Mina Norouzi')
            ->call('selectManualSubject', QrIdentity::SUBJECT_PERSON, $person->id)
            ->assertSet('scannedPersonId', $person->id)
            ->assertSet('scanStatus', 'paused')
            ->call('toggleCategory', $category->id)
            ->assertSet('assignedCategoryIds', [$category->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => $person->id,
        ]);
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
