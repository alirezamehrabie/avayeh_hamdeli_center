<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\Gates\DeliveryGate;
use App\Livewire\DistributionOperators\Gates\EntryGate;
use App\Livewire\DistributionOperators\Gates\ExitGate;
use App\Models\EducationLevel;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceEntryField;
use App\Models\ServiceName;
use App\Models\User;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionGateEntryFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_define_extra_fields_that_persist_on_the_service(): void
    {
        $operator = $this->inboundOperator();
        $service = $this->makeGateService($operator);

        $this->actingAs($operator);

        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('addEntryField')
            ->assertSet('showFieldConfig', true);

        $field = $service->entryFields()->sole();
        $this->assertSame(ServiceEntryField::TYPE_TEXT, $field->type);

        // Editing a draft persists to the definition.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->set("entryFieldDrafts.{$field->id}.title", 'سایز کفش')
            ->set("entryFieldDrafts.{$field->id}.type", ServiceEntryField::TYPE_NUMBER);

        $field->refresh();
        $this->assertSame('سایز کفش', $field->title);
        $this->assertSame(ServiceEntryField::TYPE_NUMBER, $field->type);

        // Definitions reload for the next session (configured once, reused).
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->assertSet("entryFieldDrafts.{$field->id}.title", 'سایز کفش');
    }

    public function test_entered_values_save_for_the_subject_and_show_across_delivery_and_exit_gates(): void
    {
        $operator = $this->allGatesOperator();
        $service = $this->makeGateService($operator);

        $shoeSize = $service->entryFields()->create([
            'title' => 'سایز کفش',
            'type' => ServiceEntryField::TYPE_NUMBER,
            'sort_order' => 1,
        ]);
        $grade = $service->entryFields()->create([
            'title' => 'مقطع تحصیلی',
            'type' => ServiceEntryField::TYPE_EDUCATION_LEVEL,
            'sort_order' => 2,
        ]);

        $level = EducationLevel::query()->create(['name' => 'پایه سوم ابتدایی', 'sort_order' => 4]);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $token = $this->qrTokenFor($person, $operator);

        $this->actingAs($operator);

        // Enter values at the Entry Gate.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->set("entryFieldValues.{$shoeSize->id}", '38')
            ->set("entryFieldValues.{$grade->id}", (string) $level->id);

        $this->assertDatabaseHas('gate_entry_field_values', [
            'service_id' => $service->id,
            'person_id' => $person->id,
            'guardian_id' => null,
        ]);

        $saved = \App\Models\GateEntryFieldValue::query()
            ->where('service_id', $service->id)
            ->where('person_id', $person->id)
            ->sole();
        $this->assertSame('38', $saved->values[$shoeSize->id]);
        $this->assertSame($level->id, (int) $saved->values[$grade->id]);

        // Re-scanning the same person reloads the saved values.
        Livewire::test(EntryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->assertSet("entryFieldValues.{$shoeSize->id}", '38');

        // Delivery Gate shows the values (education level resolved to its name).
        $delivery = Livewire::test(DeliveryGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->get('lastScanResult');

        $extras = collect($delivery['extra_fields']);
        $this->assertSame('38', $extras->firstWhere('label', 'سایز کفش')['value']);
        $this->assertSame('پایه سوم ابتدایی', $extras->firstWhere('label', 'مقطع تحصیلی')['value']);

        // Exit Gate shows the same values.
        $exit = Livewire::test(ExitGate::class)
            ->call('selectService', $service->id)
            ->call('resolveScannedQr', $token)
            ->get('lastScanResult');

        $exitExtras = collect($exit['extra_fields']);
        $this->assertSame('38', $exitExtras->firstWhere('label', 'سایز کفش')['value']);
        $this->assertSame('پایه سوم ابتدایی', $exitExtras->firstWhere('label', 'مقطع تحصیلی')['value']);
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

    protected function qrTokenFor(Person $person, User $operator): string
    {
        $issued = app(QrIdentityService::class)->issueFor($person, $operator->id);

        return $issued['token'] ?? $issued['identity']->token_encrypted;
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
}
