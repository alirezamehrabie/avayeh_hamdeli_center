<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\Gates\DeliveryGate;
use App\Models\Education;
use App\Models\EducationLevel;
use App\Models\GateEntryAssignment;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_toggle_reports_the_resulting_delivered_state_to_the_client(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Reza',
            'last_name' => 'Kazemi',
            'national_id' => '7234567890',
            'person_code' => '14040',
        ]);

        $this->assign($service, $category, $person, $operator);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token);

        // The Alpine checklist realigns its optimistic tick with this return value.
        $component->call('toggleDelivered', $category->id, 'deliver')
            ->assertReturned(true);

        $component->call('toggleDelivered', $category->id, 'revert')
            ->assertReturned(false);
    }

    public function test_a_stale_tap_cannot_flip_an_item_the_operator_never_saw_change(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Zahra',
            'last_name' => 'Sadeghi',
            'national_id' => '8234567890',
            'person_code' => '14041',
        ]);

        $assignment = $this->assign($service, $category, $person, $operator);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet('deliveredCategoryIds', []);

        // Another station delivered this item while the page sat open on a pending row. The operator here
        // still sees an unticked row, so their tap means "deliver" — a plain toggle would read the row as
        // delivered and silently revert it, i.e. cancel a delivery with no confirmation at all.
        $assignment->forceFill([
            'status' => GateEntryAssignment::STATUS_DELIVERED,
            'delivered_at' => now(),
            'delivered_by' => $operator->id,
        ])->save();

        $component->call('toggleDelivered', $category->id, 'deliver')
            ->assertReturned(true)
            ->assertSet('deliveredCategoryIds', [$category->id]);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_DELIVERED,
        ]);

        // The mirror case: the row was reverted elsewhere, so a confirmed "revert" tap on the tick this
        // operator still sees must not re-deliver it.
        $assignment->forceFill([
            'status' => GateEntryAssignment::STATUS_PENDING,
            'delivered_at' => null,
            'delivered_by' => null,
        ])->save();

        $component->call('toggleDelivered', $category->id, 'revert')
            ->assertReturned(false)
            ->assertSet('deliveredCategoryIds', []);

        $this->assertDatabaseHas('gate_entry_assignments', [
            'id' => $assignment->id,
            'status' => GateEntryAssignment::STATUS_PENDING,
        ]);
    }

    public function test_toggling_delivery_persists_without_re_rendering_the_gate(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $person = Person::query()->create([
            'first_name' => 'Nima',
            'last_name' => 'Bagheri',
            'national_id' => '9234567890',
            'person_code' => '14042',
        ]);

        $this->assign($service, $category, $person, $operator);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->call('toggleDelivered', $category->id, 'deliver');

        // The Alpine checklist owns the tick, so a toggle must not re-render (and re-query) the gate.
        $this->assertArrayNotHasKey('html', $component->effects);
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

    public function test_exit_gate_handoff_query_loads_the_subject_on_mount(): void
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

        $this->assign($service, $category, $person, $operator);

        $this->actingAs($operator);

        // The Exit Gate's handoff link deep-links straight onto this subject — no rescan needed.
        $this->get(route('distribution-operator.gates.delivery', [
            'service' => $service->id,
            'subject' => 'person:'.$person->id,
        ]))
            ->assertOk()
            ->assertSee('از گیت خروج ارجاع داده شد')
            ->assertSee('Ali Ahmadi')
            ->assertSee('Food basket');
    }

    public function test_exit_gate_handoff_query_loads_a_guardian_subject(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $guardian = Guardian::query()->create([
            'guardian_code' => 5566778,
            'first_name' => 'Maryam',
            'last_name' => 'Karimi',
        ]);

        GateEntryAssignment::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'person_id' => null,
            'guardian_id' => $guardian->id,
            'full_name' => 'Maryam Karimi',
            'status' => GateEntryAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        $this->get(route('distribution-operator.gates.delivery', [
            'service' => $service->id,
            'subject' => 'guardian:'.$guardian->id,
        ]))
            ->assertOk()
            ->assertSee('از گیت خروج ارجاع داده شد')
            ->assertSee('Maryam Karimi');
    }

    public function test_invalid_handoff_subject_is_ignored_and_the_gate_stays_on_the_scan_prompt(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        $this->get(route('distribution-operator.gates.delivery', [
            'service' => $service->id,
            'subject' => 'person:not-a-number',
        ]))
            ->assertOk()
            ->assertDontSee('از گیت خروج ارجاع داده شد')
            ->assertSee('دوربین را فعال کنید');
    }

    public function test_scanning_a_subject_opens_the_mobile_items_sheet(): void
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

        $this->assign($service, $category, $person, $operator);

        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        // The view listens for this event to slide the bottom sheet up on mobile.
        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertDispatched('delivery-gate-subject-loaded');

        // Manual selection takes the same onSubjectLoaded path, so it opens the sheet too.
        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('selectManualSubject', QrIdentity::SUBJECT_PERSON, $person->id)
            ->assertDispatched('delivery-gate-subject-loaded');
    }

    public function test_scan_surfaces_compact_identity_in_the_sheet_header(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Food basket', $operator);

        $worker = SocialWorker::query()->create([
            'first_name' => 'Zahra',
            'last_name' => 'Moradi',
            'is_active' => true,
        ]);

        $guardian = Guardian::query()->create([
            'social_worker_id' => $worker->id,
            'guardian_code' => 5566771,
            'first_name' => 'Ali',
            'last_name' => 'Guardian',
        ]);

        $person = Person::query()->create([
            'guardian_id' => $guardian->id,
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
            'father_name' => 'Reza',
            'gender' => 'male',
            'birth_year' => 1380,
        ]);

        $level = EducationLevel::query()->create(['name' => 'دیپلم', 'sort_order' => 5]);

        Education::query()->create([
            'person_id' => $person->id,
            'education_level_id' => $level->id,
            'is_studying' => true,
        ]);

        $this->assign($service, $category, $person, $operator);
        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        // The fixed sheet header carries the registration-form identity facts: name + father,
        // person code + social worker, and the filled-in gender/age chips. The education level
        // was deliberately dropped from the compact header.
        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSee('پدر: Reza')
            ->assertSee('کد مددجو')
            ->assertSee('Zahra Moradi')
            ->assertDontSee('دیپلم')
            ->assertSee('آقا / پسر');
    }

    public function test_confirm_button_stays_disabled_until_an_item_is_ticked(): void
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

        $this->assign($service, $category, $person, $operator);
        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        // The confirm button binds Alpine's live deliveredCount: zero ticks = disabled,
        // and the amber hint tells the operator what unlocks it.
        Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSee('deliveredCount === 0')
            ->assertSee('برای فعال‌شدن دکمه، ابتدا حداقل یک قلم را علامت بزنید');
    }

    public function test_sheet_item_rows_reserve_uniform_thumbnail_slots(): void
    {
        Storage::fake('public');

        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $withThumb = $this->makeCategory($service, 'Food basket', $operator);
        $withoutThumb = $this->makeCategory($service, 'Blanket', $operator);

        $imagePath = 'service-categories/'.$service->id.'/thumb.jpg';
        Storage::disk('public')->put($imagePath, 'thumbnail-binary');
        $withThumb->forceFill(['image_path' => $imagePath])->save();

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->assign($service, $withThumb, $person, $operator);
        $this->assign($service, $withoutThumb, $person, $operator);
        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSee('/media/'.$imagePath, false);

        // Both rows — the thumbless one included — reserve the same fixed-size frame,
        // so the sheet's item heights stay uniform.
        $this->assertSame(2, substr_count($component->html(), 'h-10 w-10'));
    }

    public function test_item_rows_stay_compact_when_no_category_has_a_thumbnail(): void
    {
        [$operator] = $this->operator();
        $service = $this->makeGateService($operator);
        $category = $this->makeCategory($service, 'Rice pack', $operator);

        $person = Person::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Norouzi',
            'national_id' => '2234567890',
            'person_code' => '14010',
        ]);

        $this->assign($service, $category, $person, $operator);
        $token = $this->issueToken($person, $operator);

        $this->actingAs($operator);

        $component = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSee('Rice pack');

        $this->assertStringNotContainsString('h-10 w-10', $component->html());
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
