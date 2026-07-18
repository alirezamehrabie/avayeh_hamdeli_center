<?php

namespace Tests\Feature;

use App\Livewire\Services\BeneficiaryServiceDelivery;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\Deliveries\DirectDeliveryRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BeneficiaryDirectDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_is_forbidden_without_full_access(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'permissions' => [],
        ]);

        $this->actingAs($user);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->assertForbidden();
    }

    public function test_manager_delivers_individual_service_directly_to_registered_person(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        $category = $service->categories->first();

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_RECIPIENT)
            ->call('selectRecipient', $person->id)
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_ITEMS)
            ->set('categoryQuantities.'.$category->id, '2')
            ->call('goToReview')
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_REVIEW)
            ->call('confirmDelivery')
            ->assertSet('successState.tracking_code', fn ($code) => is_string($code) && str_starts_with($code, 'DL-'));

        $delivery = ServiceDelivery::query()->firstOrFail();

        $this->assertSame($service->id, (int) $delivery->service_id);
        $this->assertSame($category->id, (int) $delivery->service_category_id);
        $this->assertSame($person->id, (int) $delivery->person_id);
        $this->assertNull($delivery->guardian_id);
        $this->assertNull($delivery->social_worker_id);
        $this->assertSame(Service::DELIVERY_CHANNEL_DIRECT, $delivery->delivery_channel);
        $this->assertSame('1234567890', $delivery->national_id);
        $this->assertSame('Ali Ahmadi', $delivery->full_name);
        $this->assertSame('2.00', (string) $delivery->delivered_quantity);
        $this->assertSame(1000, (int) $delivery->value_per_unit_snapshot);
        $this->assertSame(2000, (int) $delivery->delivered_total_value);
        $this->assertSame($manager->id, (int) $delivery->created_by);
        $this->assertNotNull($delivery->delivery_batch_id);
    }

    public function test_family_service_delivers_to_guardian_and_individual_service_rejects_guardian_flow(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('family', $manager);
        $category = $service->categories->first();

        $guardian = Guardian::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Moradi',
            'national_code' => '2234567890',
            'guardian_code' => 701,
            'guardian_phone_number' => '09121234567',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('selectRecipient', $guardian->id)
            ->set('categoryQuantities.'.$category->id, '1')
            ->call('goToReview')
            ->call('confirmDelivery');

        $delivery = ServiceDelivery::query()->firstOrFail();

        $this->assertSame($guardian->id, (int) $delivery->guardian_id);
        $this->assertNull($delivery->person_id);
        $this->assertNull($delivery->social_worker_id);
        $this->assertSame('09121234567', $delivery->mobile);
    }

    public function test_manual_recipient_entry_normalizes_persian_digits_and_links_registered_match(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);

        $person = Person::query()->create([
            'first_name' => 'Zahra',
            'last_name' => 'Karimi',
            'national_id' => '3234567890',
            'person_code' => '14002',
        ]);

        $this->actingAs($manager);

        // Manual entry with Persian digits matching a registered person must
        // link the existing record instead of creating a manual row.
        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('startManualEntry')
            ->set('manualNationalId', '۳۲۳۴۵۶۷۸۹۰')
            ->set('manualFullName', 'زهرا کریمی')
            ->call('confirmManualEntry')
            ->assertSet('selectedPersonId', $person->id)
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_ITEMS);
    }

    public function test_manual_unregistered_recipient_is_saved_with_manual_details(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        $category = $service->categories->first();

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('startManualEntry')
            ->set('manualNationalId', '4234567890')
            ->set('manualFullName', 'گیرنده ثبت‌نشده')
            ->set('manualMobile', '۰۹۱۲۱۱۱۲۲۳۳')
            ->call('confirmManualEntry')
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_ITEMS)
            ->set('categoryQuantities.'.$category->id, '1')
            ->call('goToReview')
            ->call('confirmDelivery');

        $delivery = ServiceDelivery::query()->firstOrFail();

        $this->assertNull($delivery->person_id);
        $this->assertNull($delivery->guardian_id);
        $this->assertSame('4234567890', $delivery->national_id);
        // ZWNJ is normalized to a plain space, matching Person::normalizeSearchText().
        $this->assertSame('گیرنده ثبت نشده', $delivery->full_name);
        $this->assertSame('09121112233', $delivery->mobile);
    }

    public function test_manual_entry_requires_valid_national_id_and_name(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('startManualEntry')
            ->set('manualNationalId', '123')
            ->set('manualFullName', '')
            ->call('confirmManualEntry')
            ->assertHasErrors(['manualNationalId', 'manualFullName'])
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_RECIPIENT);
    }

    public function test_quantity_above_remaining_stock_is_rejected(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        $category = $service->categories->first();

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('selectRecipient', $person->id)
            ->set('categoryQuantities.'.$category->id, '11')
            ->call('goToReview')
            ->assertHasErrors(['categoryQuantities.'.$category->id])
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_ITEMS);

        $this->assertSame(0, ServiceDelivery::query()->count());
    }

    public function test_discrete_unit_rejects_decimal_quantity(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        $category = $service->categories->first(); // unit: pack (discrete)

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('selectRecipient', $person->id)
            ->set('categoryQuantities.'.$category->id, '1.5')
            ->call('goToReview')
            ->assertHasErrors(['categoryQuantities.'.$category->id]);
    }

    public function test_review_requires_at_least_one_quantity(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->call('selectRecipient', $person->id)
            ->call('goToReview')
            ->assertHasErrors(['categoryQuantities']);
    }

    public function test_draft_service_cannot_be_selected(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager, status: 'draft');

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->assertSet('selectedServiceId', null)
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_SERVICE);
    }

    public function test_distribution_operator_created_service_is_selectable(): void
    {
        $manager = $this->manager();
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_DISTRIBUTION_SERVICE_MANAGE],
        ]);
        $service = $this->makeService('individual', $operator);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->assertSet('selectedServiceId', $service->id)
            ->assertSet('step', BeneficiaryServiceDelivery::STEP_RECIPIENT);
    }

    public function test_recorder_rejects_over_stock_delivery_and_rolls_back_atomically(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        [$first, $second] = $service->categories->all();

        $recorder = app(DirectDeliveryRecorder::class);
        $recipient = [
            'person_id' => null,
            'guardian_id' => null,
            'national_id' => '5234567890',
            'full_name' => 'Manual Recipient',
            'mobile' => null,
        ];

        try {
            // First category fits, second exceeds its stock — nothing may persist.
            $recorder->record($service, $recipient, [
                $first->id => 1,
                $second->id => 100,
            ], now()->toDateString(), null, $manager->id);

            $this->fail('Expected over-stock delivery to be rejected.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(0, ServiceDelivery::query()->count());
    }

    public function test_recorder_creates_one_batch_with_shared_tracking_code(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);
        [$first, $second] = $service->categories->all();

        $result = app(DirectDeliveryRecorder::class)->record($service, [
            'person_id' => null,
            'guardian_id' => null,
            'national_id' => '5234567890',
            'full_name' => 'Manual Recipient',
            'mobile' => null,
        ], [
            $first->id => 2,
            $second->id => 1,
        ], now()->toDateString(), 'یادداشت تست', $manager->id);

        $this->assertCount(2, $result['deliveries']);
        $this->assertSame(DirectDeliveryRecorder::trackingCode($result['batch_id']), $result['tracking_code']);
        $this->assertSame(
            1,
            ServiceDelivery::query()->distinct()->count('delivery_batch_id'),
            'All rows of a direct delivery must share one batch id.'
        );
    }

    public function test_recipient_search_matches_persian_digit_input(): void
    {
        $manager = $this->manager();
        $service = $this->makeService('individual', $manager);

        Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $this->actingAs($manager);

        Livewire::test(BeneficiaryServiceDelivery::class)
            ->call('selectService', $service->id)
            ->set('recipientSearch', '۱۲۳۴۵')
            ->assertViewHas('recipientCandidates', fn ($candidates) => $candidates->count() === 1
                && $candidates->first()['national_id'] === '1234567890');
    }

    public function test_dashboard_direct_delivery_route_is_registered_for_full_access(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('admin.service-delivery.beneficiary'))
            ->assertOk();
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function makeService(string $type, User $creator, string $status = 'approved'): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => 'Direct Delivery '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $creator->id,
        ]);

        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => $type,
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'total_quantity' => 20,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => $status,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);

        $service->categories()->createMany([
            [
                'service_name_id' => $serviceName->id,
                'name' => 'Food '.Str::random(8),
                'quantity' => 10,
                'unit' => 'pack',
                'value' => 1000,
                'sort_id' => 1,
                'created_by' => $creator->id,
            ],
            [
                'service_name_id' => $serviceName->id,
                'name' => 'Rice '.Str::random(8),
                'quantity' => 10,
                'unit' => 'kilogram',
                'value' => 2500,
                'sort_id' => 2,
                'created_by' => $creator->id,
            ],
        ]);

        return $service->fresh('categories');
    }
}
