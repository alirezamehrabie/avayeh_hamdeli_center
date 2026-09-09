<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceReports;
use App\Models\Guardian;
use App\Models\NeedLevelType;
use App\Models\NeedsLevel;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SupportCoverage;
use App\Models\SupportOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceReportRecipientAttributesFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Service $service;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
        $this->actingAs($this->user);

        $serviceName = ServiceName::query()->create([
            'name' => 'Attrs Report Service '.Str::random(8),
            'sort_id' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => $serviceName->name,
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'total_quantity' => 30,
            'total_service_value' => 0,
            'distribution_start_date' => now()->subDay()->toDateString(),
            'distribution_end_date' => null,
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->category = $this->service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Pack '.Str::random(8),
            'quantity' => 30,
            'unit' => 'pack',
            'value' => 1000,
            'sort_id' => 1,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_options_list_only_institutions_and_levels_registered_for_this_services_recipients(): void
    {
        $orgA = $this->createOrganization();
        $orgB = $this->createOrganization();
        $unusedOrg = $this->createOrganization();
        $high = $this->createNeedLevel('A');
        $low = $this->createNeedLevel('B');

        $person = $this->createPerson();
        $this->attachCoverage($person, $orgA);
        $this->attachNeedLevel($person, $high);
        $this->createDelivery(['person_id' => $person->id], 'Tagged Individual');

        $guardian = $this->createGuardian();
        $member = $this->createPerson($guardian->id);
        $this->attachCoverage($member, $orgB);
        $this->attachNeedLevel($member, $low);
        $this->createDelivery(['guardian_id' => $guardian->id], 'Household Row');

        $this->createDelivery([], 'Manual Row');

        Livewire::test(ServiceReports::class, ['selectedServiceId' => $this->service->id])
            ->assertViewHas('supportOrganizationOptions', fn (array $options): bool => collect($options)->pluck('id')->sort()->values()->all() === [$orgA->id, $orgB->id])
            ->assertViewHas('needLevelOptions', fn (array $options): bool => collect($options)->pluck('id')->sort()->values()->all() === [$high->id, $low->id])
            ->assertViewHas('supportOrganizationOptions', fn (array $options): bool => ! collect($options)->contains('id', $unusedOrg->id));
    }

    public function test_support_organization_filter_narrows_individual_family_and_manual_records(): void
    {
        $orgA = $this->createOrganization();
        $orgB = $this->createOrganization();

        $person = $this->createPerson();
        $this->attachCoverage($person, $orgA);
        $this->createDelivery(['person_id' => $person->id], 'Org A Individual');

        $guardian = $this->createGuardian();
        $member = $this->createPerson($guardian->id);
        $this->attachCoverage($member, $orgB);
        $this->createDelivery(['guardian_id' => $guardian->id], 'Org B Household');

        $this->createDelivery(['person_id' => $this->createPerson()->id], 'Untagged Individual');
        $this->createDelivery([], 'Manual Row');

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $this->service->id]);

        $component->set('selectedSupportOrganization', (string) $orgA->id);
        $this->assertSame(
            ['Org A Individual'],
            collect($component->instance()->deliveryGroups->items())->pluck('recipientName')->all()
        );

        $component->set('selectedSupportOrganization', (string) $orgB->id);
        $this->assertSame(
            ['Org B Household'],
            collect($component->instance()->deliveryGroups->items())->pluck('recipientName')->all()
        );

        $component->set('selectedSupportOrganization', 'all');
        $this->assertSame(4, $component->instance()->deliveryGroups->total());
    }

    public function test_need_level_filter_and_combination_with_other_filters(): void
    {
        $high = $this->createNeedLevel('A');
        $low = $this->createNeedLevel('B');
        $orgB = $this->createOrganization();

        $person = $this->createPerson();
        $this->attachNeedLevel($person, $high);
        $this->createDelivery(['person_id' => $person->id], 'High Need Individual');

        $guardian = $this->createGuardian();
        $member = $this->createPerson($guardian->id);
        $this->attachNeedLevel($member, $low);
        $this->attachCoverage($member, $orgB);
        $this->createDelivery(['guardian_id' => $guardian->id], 'Low Need Household');

        $this->createDelivery([], 'Manual Row');

        $component = Livewire::test(ServiceReports::class, ['selectedServiceId' => $this->service->id]);

        $component->set('selectedNeedLevel', (string) $low->id);
        $this->assertSame(
            ['Low Need Household'],
            collect($component->instance()->deliveryGroups->items())->pluck('recipientName')->all()
        );

        // Stacking the new filters keeps narrowing (AND semantics)…
        $component->set('selectedSupportOrganization', (string) $orgB->id);
        $component->set('selectedNeedLevel', (string) $high->id);
        $this->assertSame(0, $component->instance()->deliveryGroups->total());

        // …and they combine with the existing entry-type filter.
        $component->set('selectedNeedLevel', 'all');
        $component->set('selectedDeliveryEntryType', 'guardian');
        $this->assertSame(1, $component->instance()->deliveryGroups->total());

        $component->set('selectedDeliveryEntryType', 'individual');
        $this->assertSame(0, $component->instance()->deliveryGroups->total());

        // The clear button drops both new filters along with the rest.
        $component->call('clearDeliveryFilters');
        $this->assertSame('all', $component->instance()->selectedSupportOrganization);
        $this->assertSame('all', $component->instance()->selectedNeedLevel);
        $this->assertSame(3, $component->instance()->deliveryGroups->total());
    }

    private function createOrganization(): SupportOrganization
    {
        return SupportOrganization::query()->create([
            'name' => 'Organization '.Str::random(8),
            'slug' => 'org-'.Str::random(12),
        ]);
    }

    private function createNeedLevel(string $code): NeedLevelType
    {
        return NeedLevelType::query()->create([
            'code' => $code,
            'title' => 'Level '.$code.' '.Str::random(6),
            'severity_order' => random_int(1, 99),
        ]);
    }

    private function createGuardian(): Guardian
    {
        return Guardian::query()->create([
            'guardian_code' => (string) random_int(1000000, 9999999),
            'national_code' => (string) random_int(1000000000, 9999999999),
            'first_name' => 'Guardian',
            'last_name' => 'Household',
        ]);
    }

    private function createPerson(?int $guardianId = null): Person
    {
        return Person::query()->create([
            'guardian_id' => $guardianId,
            'person_code' => (string) random_int(1000000, 9999999),
            'national_id' => (string) random_int(1000000000, 9999999999),
            'first_name' => 'Beneficiary',
            'last_name' => 'Person',
        ]);
    }

    private function attachCoverage(Person $person, SupportOrganization $organization): SupportCoverage
    {
        return SupportCoverage::query()->create([
            'person_id' => $person->id,
            'support_organization_id' => $organization->id,
        ]);
    }

    private function attachNeedLevel(Person $person, NeedLevelType $level): NeedsLevel
    {
        return NeedsLevel::query()->create([
            'person_id' => $person->id,
            'need_level_id' => $level->id,
        ]);
    }

    private function createDelivery(array $recipient, string $fullName): ServiceDelivery
    {
        return ServiceDelivery::query()->create($recipient + [
            'service_id' => $this->service->id,
            'service_category_id' => $this->category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_GATE,
            'national_id' => (string) random_int(1000000000, 9999999999),
            'full_name' => $fullName,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 1000,
            'delivered_total_value' => 1000,
            'delivered_at' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);
    }
}
