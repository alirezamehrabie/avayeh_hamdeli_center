<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityDefinition;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_status_is_ready_to_hold_for_new_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->assertSet('status', 'ongoing');
    }

    public function test_unknown_status_is_rejected_when_creating_activity(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Summer Workshop')
            ->set('activityType', 'workshop')
            ->set('status', 'archived')
            ->call('save')
            ->assertHasErrors(['status']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Summer Workshop',
        ]);
    }

    public function test_new_activity_type_options_render_in_definition_form(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->assertSee('کلاس آموزشی')
            ->assertSee('طرح نخبگان (پسران)')
            ->assertSee('طرح نخبگان (دختران)');
    }

    public function test_new_activity_types_are_valid_and_saved(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        $types = [
            'educational_class',
            'elite_program_boys',
            'elite_program_girls',
        ];

        foreach ($types as $type) {
            $name = 'Activity Type '.$type;

            Livewire::test(ActivityDefinition::class)
                ->set('name', $name)
                ->set('activityType', $type)
                ->call('save')
                ->assertHasNoErrors(['activityType'])
                ->assertRedirect('/admin/dashboard?section=activity-list');

            $this->assertDatabaseHas('activities', [
                'name' => $name,
                'activity_type' => $type,
            ]);
        }
    }

    public function test_new_activity_type_is_preserved_when_editing_activity(): void
    {
        $user = $this->manager();
        $activity = Activity::query()->create([
            'name' => 'Elite Girls Program',
            'activity_type' => 'elite_program_girls',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class, ['activityId' => $activity->id])
            ->assertSet('activityType', 'elite_program_girls')
            ->set('name', 'Updated Elite Girls Program')
            ->call('save')
            ->assertHasNoErrors(['activityType'])
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Updated Elite Girls Program',
            'activity_type' => 'elite_program_girls',
        ]);
    }

    public function test_activity_datetimes_are_saved_correctly_from_jalali_input(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Summer Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30')
            ->set('endsAt', '1405/03/28 16:45')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Summer Workshop')->firstOrFail();

        $this->assertSame('2026-06-18 14:30', $activity->starts_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
        $this->assertSame('2026-06-18 16:45', $activity->ends_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
        $this->assertMatchesRegularExpression('/^ACT-\d{5}$/', (string) $activity->code);
    }

    public function test_activity_datetimes_accept_persian_digits_from_picker(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Persian Digits Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '۱۴۰۵/۰۳/۲۸ ۱۴:۳۰')
            ->set('endsAt', '۱۴۰۵/۰۳/۲۸ ۱۶:۴۵')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Persian Digits Workshop')->firstOrFail();

        $this->assertSame('2026-06-18 14:30', $activity->starts_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
        $this->assertSame('2026-06-18 16:45', $activity->ends_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'));
    }

    public function test_malformed_jalali_date_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed Date Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28abc')
            ->call('save')
            ->assertHasErrors(['startsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed Date Workshop',
        ]);
    }

    public function test_malformed_jalali_time_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed Time Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30x')
            ->call('save')
            ->assertHasErrors(['startsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed Time Workshop',
        ]);
    }

    public function test_malformed_jalali_end_time_suffix_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Malformed End Time Workshop')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 14:30')
            ->set('endsAt', '1405/03/28 16:45x')
            ->call('save')
            ->assertHasErrors(['endsAt']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Malformed End Time Workshop',
        ]);
    }

    public function test_ready_to_hold_status_is_preserved_when_start_time_is_in_future(): void
    {
        $user = $this->manager();

        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0, config('app.timezone')));
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Morning Event')
            ->set('activityType', 'workshop')
            ->set('startsAt', '1405/03/28 13:00')
            ->set('status', 'ongoing')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'name' => 'Morning Event',
            'status' => 'ongoing',
        ]);

        Carbon::setTestNow();
    }

    public function test_manager_can_change_status_when_editing_activity(): void
    {
        $user = $this->manager();
        $activity = Activity::query()->create([
            'name' => 'Camp Session',
            'activity_type' => 'camp',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class, ['activityId' => $activity->id])
            ->assertSet('status', 'draft')
            ->set('status', 'cancelled')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_new_activity_code_is_generated_on_save(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Code Generation Workshop')
            ->set('activityType', 'workshop')
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Code Generation Workshop')->firstOrFail();

        $this->assertMatchesRegularExpression('/^ACT-\d{5}$/', (string) $activity->code);
    }

    public function test_activity_definition_saves_linked_activity_services_and_categories(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Ceremony With Services')
            ->set('activityType', 'ceremony')
            ->set('startsAt', '1405/03/28 10:00')
            ->set('activityServices', [[
                'id' => null,
                'selectedServiceNameId' => null,
                'serviceName' => 'Blessed Sweets',
                'serviceType' => 'individual',
                'description' => 'Served during ceremony',
                'serviceDistrictId' => null,
                'distributionStartDate' => '1405/03/28',
                'distributionEndDate' => null,
                'priority' => 'normal',
                'status' => 'in_distribution',
                'statusNotes' => '',
                'categories' => [[
                    'id' => null,
                    'code' => '',
                    'name' => 'Sweet Pack',
                    'quantity' => '25',
                    'unit' => 'pack',
                    'value' => '10000',
                ]],
            ]])
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $activity = Activity::query()->where('name', 'Ceremony With Services')->firstOrFail();
        $serviceName = ServiceName::query()->where('name', 'Blessed Sweets')->firstOrFail();
        $service = Service::query()->where('activity_id', $activity->id)->firstOrFail();

        $this->assertSame($serviceName->id, $service->service_name_id);
        $this->assertFalse((bool) $service->supports_gate_delivery);
        $this->assertFalse((bool) $service->supports_home_delivery);
        $this->assertTrue((bool) $service->supports_activity_delivery);
        $this->assertSame('25.00', (string) $service->total_quantity);

        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'service_name_id' => $serviceName->id,
            'name' => 'Sweet Pack',
            'unit' => 'pack',
            'value' => 10000,
        ]);
    }

    public function test_new_activity_service_defaults_to_event_service_name_from_activity_name(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'مراسم افطاری')
            ->call('addActivityService')
            ->assertSet('activityServices.0.serviceName', 'خدمت رویداد مراسم افطاری');
    }

    public function test_new_activity_service_defaults_to_current_date_when_activity_name_is_empty(): void
    {
        $user = $this->manager();
        Carbon::setTestNow(Carbon::create(2026, 9, 26, 12, 0, 0, config('app.timezone')));
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->call('addActivityService')
            ->assertSet('activityServices.0.serviceName', 'خدمت رویداد - 4 مهر 1405');

        Carbon::setTestNow();
    }

    public function test_activity_name_change_updates_only_untouched_default_service_names(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'مراسم اول')
            ->call('addActivityService')
            ->set('name', 'مراسم دوم')
            ->assertSet('activityServices.0.serviceName', 'خدمت رویداد مراسم دوم')
            ->set('activityServices.0.serviceName', 'پذیرایی ویژه')
            ->set('name', 'مراسم سوم')
            ->assertSet('activityServices.0.serviceName', 'پذیرایی ویژه');
    }

    public function test_activity_service_category_fields_are_required(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class)
            ->set('name', 'Invalid Service Ceremony')
            ->set('activityType', 'ceremony')
            ->set('activityServices.0.serviceName', 'Food Service')
            ->set('activityServices.0.categories.0.name', '')
            ->call('save')
            ->assertHasErrors(['activityServices.0.categories.0.name']);

        $this->assertDatabaseMissing('activities', [
            'name' => 'Invalid Service Ceremony',
        ]);
    }

    public function test_retroactive_activity_service_assignment_creates_deliveries_for_existing_attendees(): void
    {
        $user = $this->manager();
        $activity = $this->activity('Retroactive Ceremony', $user);
        $firstPerson = $this->person('1234567890', '15001');
        $secondPerson = $this->person('2234567890', '15002');

        ActivityAttendance::query()->create([
            'activity_id' => $activity->id,
            'person_id' => $firstPerson->id,
            'status' => 'present',
            'registration_method' => 'manual',
            'checked_in_at' => '2026-07-03 10:00:00',
            'recorded_by' => $user->id,
        ]);
        ActivityAttendance::query()->create([
            'activity_id' => $activity->id,
            'person_id' => $secondPerson->id,
            'status' => 'present',
            'registration_method' => 'manual',
            'checked_in_at' => '2026-07-03 10:05:00',
            'recorded_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class, ['activityId' => $activity->id])
            ->set('activityServices', [[
                'id' => null,
                'selectedServiceNameId' => null,
                'serviceName' => 'Retroactive Food',
                'serviceType' => 'individual',
                'description' => '',
                'serviceDistrictId' => null,
                'distributionStartDate' => '1405/04/12',
                'distributionEndDate' => null,
                'priority' => 'normal',
                'status' => 'in_distribution',
                'statusNotes' => '',
                'categories' => [[
                    'id' => null,
                    'code' => '',
                    'name' => 'Food Pack',
                    'quantity' => '10',
                    'unit' => 'pack',
                    'value' => '120000',
                ]],
            ]])
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $service = Service::query()->where('activity_id', $activity->id)->firstOrFail();
        $category = $service->categories()->firstOrFail();

        $this->assertSame(2, ServiceDelivery::query()
            ->where('service_id', $service->id)
            ->where('service_category_id', $category->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->count());
        $this->assertSame('2.00', (string) $service->fresh()->quantity_delivered);
        $this->assertDatabaseHas('service_deliveries', [
            'person_id' => $firstPerson->id,
            'activity_attendance_id' => ActivityAttendance::query()->where('person_id', $firstPerson->id)->value('id'),
            'service_category_id' => $category->id,
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 120000,
            'delivered_total_value' => 120000,
        ]);
    }

    public function test_retroactive_activity_service_sync_removes_deleted_category_deliveries_without_duplicates(): void
    {
        $user = $this->manager();
        $activity = $this->activity('Category Removal Ceremony', $user);
        $person = $this->person('3234567890', '15003');
        $attendance = ActivityAttendance::query()->create([
            'activity_id' => $activity->id,
            'person_id' => $person->id,
            'status' => 'present',
            'registration_method' => 'manual',
            'checked_in_at' => '2026-07-03 10:00:00',
            'recorded_by' => $user->id,
        ]);
        $service = $this->activityService($activity, $user, 'Removable Service');
        $firstCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Keep Pack',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 100000,
            'created_by' => $user->id,
        ]);
        $secondCategory = $service->categories()->create([
            'service_name_id' => $service->service_name_id,
            'name' => 'Remove Pack',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 50000,
            'created_by' => $user->id,
        ]);

        foreach ([$firstCategory, $secondCategory] as $category) {
            ServiceDelivery::query()->create([
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'activity_attendance_id' => $attendance->id,
                'delivery_channel' => Service::DELIVERY_CHANNEL_ACTIVITY,
                'person_id' => $person->id,
                'national_id' => $person->national_id,
                'full_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name),
                'delivered_quantity' => 1,
                'value_per_unit_snapshot' => (int) $category->value,
                'delivered_total_value' => (int) $category->value,
                'delivered_at' => '2026-07-03',
                'created_by' => $user->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(ActivityDefinition::class, ['activityId' => $activity->id])
            ->set('activityServices', [[
                'id' => $service->id,
                'selectedServiceNameId' => $service->service_name_id,
                'serviceName' => $service->name,
                'serviceType' => 'individual',
                'description' => '',
                'serviceDistrictId' => null,
                'distributionStartDate' => '1405/04/12',
                'distributionEndDate' => null,
                'priority' => 'normal',
                'status' => 'in_distribution',
                'statusNotes' => '',
                'categories' => [[
                    'id' => $firstCategory->id,
                    'code' => $firstCategory->code,
                    'name' => 'Keep Pack',
                    'quantity' => '10',
                    'unit' => 'pack',
                    'value' => '140000',
                ]],
            ]])
            ->call('save')
            ->assertRedirect('/admin/dashboard?section=activity-list');

        $this->assertSame(1, ServiceDelivery::query()
            ->where('activity_attendance_id', $attendance->id)
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->count());
        $this->assertDatabaseHas('service_deliveries', [
            'activity_attendance_id' => $attendance->id,
            'service_category_id' => $firstCategory->id,
            'value_per_unit_snapshot' => 140000,
            'delivered_total_value' => 140000,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('service_deliveries', [
            'activity_attendance_id' => $attendance->id,
            'service_category_id' => $secondCategory->id,
        ]);
        $this->assertSame(1, ServiceDelivery::withTrashed()
            ->where('activity_attendance_id', $attendance->id)
            ->where('service_category_id', $firstCategory->id)
            ->count());
    }

    public function test_new_activity_code_ignores_soft_deleted_rows_when_generating_sequence(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        $softDeletedActivity = Activity::query()->create([
            'code' => 'ACT-00041',
            'name' => 'Archived Session',
            'activity_type' => 'workshop',
            'status' => 'closed',
            'created_by' => $user->id,
        ]);

        $softDeletedActivity->delete();

        $nextCode = Activity::generateNextCode();

        $this->assertSame('ACT-00042', $nextCode);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function activity(string $name, User $creator): Activity
    {
        return Activity::query()->create([
            'name' => $name,
            'activity_type' => 'ceremony',
            'status' => 'ongoing',
            'created_by' => $creator->id,
        ]);
    }

    private function person(string $nationalId, string $personCode): Person
    {
        return Person::query()->create([
            'first_name' => 'Activity',
            'last_name' => 'Attendee',
            'national_id' => $nationalId,
            'person_code' => $personCode,
        ]);
    }

    private function activityService(Activity $activity, User $creator, string $name): Service
    {
        $serviceName = ServiceName::query()->create([
            'name' => $name,
            'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
            'created_by' => $creator->id,
        ]);

        return Service::query()->create([
            'activity_id' => $activity->id,
            'service_name_id' => $serviceName->id,
            'name' => $name,
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => false,
            'supports_activity_delivery' => true,
            'distribution_start_date' => now()->toDateString(),
            'distribution_end_date' => null,
            'priority' => 'normal',
            'status' => 'in_distribution',
            'total_quantity' => 20,
            'total_service_value' => 1500000,
            'quantity_delivered' => 0,
            'created_by' => $creator->id,
        ]);
    }
}
