<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\ServiceBatchCreator;
use App\Livewire\DistributionOperators\ServiceList;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DistributionOperatorAllocationAssignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_assignment_records_assigning_user(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 901,
            'first_name' => 'Distribution',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'متفرقه',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'سایر (متفرقه)',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceName', 'بسته اپراتور توزیع')
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'بسته اپراتور')
            ->set('miscCategories.0.quantity', '6')
            ->set('miscCategories.0.unit', 'pack')
            ->set('date', '1405/03/30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertRedirect(route('distribution-operator.service-list', ['tab' => ServiceList::TAB_MISC]));

        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 6,
            'assigned_by_user_id' => $operator->id,
        ]);

        $service = Service::query()
            ->where('created_by', $operator->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertFalse((bool) $service->supports_gate_delivery);
        $this->assertTrue((bool) $service->supports_home_delivery);
    }

    public function test_misc_service_creation_accepts_rial_and_pair_units(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 905,
            'first_name' => 'Distribution',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceName', 'خدمت واحدهای ترکیبی')
            ->set('miscServiceType', 'individual')
            ->set('miscCategories', [
                [
                    'name' => 'اعتبار نقدی',
                    'quantity' => '1000000',
                    'unit' => 'rial',
                    'value' => '1',
                ],
                [
                    'name' => 'کفش',
                    'quantity' => '2',
                    'unit' => 'pair',
                    'value' => '500,000',
                ],
            ])
            ->set('date', '1405/03/30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertRedirect(route('distribution-operator.service-list', ['tab' => ServiceList::TAB_MISC]));

        $service = Service::query()
            ->where('created_by', $operator->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'name' => 'اعتبار نقدی',
            'unit' => 'rial',
        ]);
        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'name' => 'کفش',
            'unit' => 'pair',
        ]);
        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'service_category_id' => $service->categories()->where('unit', 'rial')->value('id'),
            'allocated_quantity' => 1000000,
        ]);
        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'service_category_id' => $service->categories()->where('unit', 'pair')->value('id'),
            'allocated_quantity' => 2,
        ]);
    }

    public function test_misc_service_type_must_be_selected_before_save_confirmation(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 901,
            'first_name' => 'Distribution',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->assertSet('miscServiceType', '')
            ->set('miscCategories.0.name', 'Operator package')
            ->set('miscCategories.0.quantity', '1')
            ->set('miscCategories.0.unit', 'pack')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('requestSaveConfirmation')
            ->assertHasErrors(['miscServiceType' => 'required'])
            ->assertSet('confirmingBatchSave', false);
    }

    public function test_predefined_mode_allocates_existing_service_without_creating_service_or_category(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 902,
            'first_name' => 'مددکار',
            'last_name' => 'پویش',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش مدیریتی',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش مدیریتی',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'برنج',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $serviceCount = Service::query()->count();
        $categoryCount = ServiceCategory::query()->count();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->call('choosePredefinedMode')
            ->assertDispatched('open-predefined-service-selector')
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->set('predefinedAllocations.'.$category->id, '4')
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertRedirect(route('distribution-operator.service-list'));

        $this->assertSame($serviceCount, Service::query()->count());
        $this->assertSame($categoryCount, ServiceCategory::query()->count());
        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
            'assigned_by_user_id' => $operator->id,
        ]);
        $this->assertSame(10.0, (float) $category->fresh()->quantity);
        $this->assertSame(10.0, (float) $service->fresh()->total_quantity);
    }

    public function test_predefined_mode_limits_repeated_allocations_by_remaining_assignable_capacity(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $firstWorker = SocialWorker::query()->create([
            'worker_code' => 912,
            'first_name' => 'First',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $secondWorker = SocialWorker::query()->create([
            'worker_code' => 913,
            'first_name' => 'Second',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Repeated Allocation Campaign',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Repeated Allocation Campaign',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 10,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Rice',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $firstWorker->full_name)
            ->set('socialWorkerId', $firstWorker->id)
            ->set('predefinedAllocations.'.$category->id, '4')
            ->call('saveBatch')
            ->assertHasNoErrors();

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $secondWorker->full_name)
            ->set('socialWorkerId', $secondWorker->id)
            ->set('predefinedAllocations.'.$category->id, '7')
            ->call('saveBatch')
            ->assertHasErrors(['predefinedAllocations.'.$category->id]);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('socialWorkerQuery', $secondWorker->full_name)
            ->set('socialWorkerId', $secondWorker->id)
            ->set('predefinedAllocations.'.$category->id, '6')
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertSame(10.0, (float) $category->fresh()->quantity);
        $this->assertSame(10.0, (float) $service->fresh()->total_quantity);
        $this->assertSame(10.0, (float) $service->workerAllocations()->sum('allocated_quantity'));
    }

    public function test_predefined_mode_exposes_minimal_live_remaining_inventory_for_selected_categories(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 914,
            'first_name' => 'Inventory',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش آمار',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش آمار',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 15,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $rice = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'برنج',
            'quantity' => 10,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $oil = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'روغن',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 2,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->call('selectSocialWorker', $worker->id)
            ->set('predefinedAllocations.'.$rice->id, '3')
            ->set('predefinedAllocations.'.$oil->id, '2')
            ->assertSee('data-predefined-service-header-title="پویش آمار"', false)
            ->assertSee('aria-label="تغییر خدمت"', false)
            ->assertSee('تغییر خدمت / پویش')
            ->assertDontSee('data-predefined-service-header-title="انتخاب خدمت / پویش"', false)
            ->call('choosePredefinedMode')
            ->assertDispatched('open-predefined-service-selector')
            ->assertSee('موجودی کل')
            ->assertSee('15.00')
            ->assertSee('10')
            ->assertSee('5')
            ->assertSee('3')
            ->assertSee('2')
            ->assertDontSee('واردشده');
    }

    public function test_predefined_mode_prevents_over_allocation_before_submit(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش کنترل موجودی',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش کنترل موجودی',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 5,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'سبد کالا',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->set('predefinedAllocations.'.$category->id, '7')
            ->assertHasErrors(['predefinedAllocations.'.$category->id])
            ->assertSee('برای ثبت، مقدار را اصلاح کنید یا از گزینه حداکثر استفاده کنید')
            ->assertSeeHtml('disabled')
            ->call('useMaxPredefinedAllocation', $category->id)
            ->assertSet('predefinedAllocations.'.$category->id, '5')
            ->assertHasNoErrors(['predefinedAllocations.'.$category->id])
            ->assertDontSee('برای ثبت، مقدار را اصلاح کنید یا از گزینه حداکثر استفاده کنید');
    }

    public function test_predefined_mode_requires_confirmation_before_saving(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 921,
            'first_name' => 'مددکار',
            'last_name' => 'تأیید',
            'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'پویش تأیید',
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'پویش تأیید',
            'service_type' => 'individual',
            'supports_gate_delivery' => true,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 8,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $manager->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'بسته تأیید',
            'quantity' => 8,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->call('selectSocialWorker', $worker->id)
            ->set('predefinedAllocations.'.$category->id, '4')
            ->call('requestSaveConfirmation')
            ->assertSet('confirmingBatchSave', true)
            ->assertSee('تأیید تخصیص خدمت موجود')
            ->assertSee('پویش تأیید')
            ->assertSee('مددکار تأیید')
            ->assertSee('بسته تأیید')
            ->assertSee('4.00')
            ->assertSee('مانده بعد از ثبت');

        $this->assertDatabaseMissing('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
        ]);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->call('selectSocialWorker', $worker->id)
            ->set('predefinedAllocations.'.$category->id, '4')
            ->call('requestSaveConfirmation')
            ->call('cancelSaveConfirmation')
            ->assertSet('confirmingBatchSave', false);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_PREDEFINED)
            ->set('selectedServiceId', $service->id)
            ->call('selectSocialWorker', $worker->id)
            ->set('predefinedAllocations.'.$category->id, '4')
            ->call('requestSaveConfirmation')
            ->call('confirmSaveBatch');

        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 4,
        ]);
    }

    public function test_misc_mode_uses_operator_custom_service_name(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 903,
            'first_name' => 'مددکار',
            'last_name' => 'متفرقه',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->call('requestMiscServiceName')
            ->set('miscServiceName', '  بسته زمستانی مددجو  ')
            ->call('confirmMiscServiceName')
            ->assertSet('mode', ServiceBatchCreator::MODE_MISC)
            ->assertSet('miscServiceName', 'بسته زمستانی مددجو')
            ->assertSee('data-service-header-title="بسته زمستانی مددجو"', false)
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'پتو')
            ->set('miscCategories.0.quantity', '3')
            ->set('miscCategories.0.unit', 'pack')
            ->set('date', '1405/03/30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'بسته زمستانی مددجو',
            'created_by' => $operator->id,
            'total_quantity' => 3,
        ]);
        $this->assertDatabaseHas('service_names', [
            'name' => 'بسته زمستانی مددجو',
        ]);
        $this->assertDatabaseMissing('services', [
            'name' => 'متفرقه - 1',
        ]);
    }

    public function test_misc_mode_requires_confirmed_valid_custom_service_name(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->call('requestMiscServiceName')
            ->assertSet('confirmingMiscServiceName', true)
            ->call('cancelMiscServiceName')
            ->assertSet('mode', '')
            ->assertSet('confirmingMiscServiceName', false)
            ->call('requestMiscServiceName')
            ->set('miscServiceName', '<script>')
            ->call('confirmMiscServiceName')
            ->assertHasErrors(['miscServiceName'])
            ->assertSet('mode', '');
    }

    public function test_misc_mode_save_rejects_missing_custom_service_name(): void
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 904,
            'first_name' => 'مددکار',
            'last_name' => 'نام سفارشی',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class)
            ->set('mode', ServiceBatchCreator::MODE_MISC)
            ->set('miscServiceType', 'individual')
            ->set('miscCategories.0.name', 'کفش')
            ->set('miscCategories.0.quantity', '2')
            ->set('miscCategories.0.unit', 'pack')
            ->set('date', '1405/03/30')
            ->set('socialWorkerQuery', $worker->full_name)
            ->set('socialWorkerId', $worker->id)
            ->call('saveBatch')
            ->assertHasErrors(['miscServiceName']);

        $this->assertDatabaseCount('services', 0);
    }

    public function test_editing_misc_service_cannot_reduce_used_category_below_delivered_quantity(): void
    {
        [$operator, $worker, $service, $category] = $this->editableMiscService();

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'social_worker_id' => $worker->id,
            'national_id' => '0000000000',
            'full_name' => 'Delivered Recipient',
            'delivered_quantity' => 3,
            'value_per_unit_snapshot' => 0,
            'delivered_total_value' => 0,
            'delivered_at' => '2026-06-20',
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscWorkerGroups.0.categories.0.quantity', '2')
            ->call('requestSaveConfirmation')
            ->assertHasErrors(['miscWorkerGroups.0.categories.0.quantity'])
            ->assertSet('confirmingBatchSave', false);

        $this->assertSame(5.0, (float) $category->fresh()->quantity);
    }

    public function test_editing_misc_service_cannot_move_used_category_to_another_worker(): void
    {
        [$operator, $worker, $service, $category] = $this->editableMiscService();
        $otherWorker = SocialWorker::query()->create([
            'worker_code' => 991,
            'first_name' => 'Other',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'social_worker_id' => $worker->id,
            'national_id' => '0000000001',
            'full_name' => 'Delivered Recipient',
            'delivered_quantity' => 1,
            'value_per_unit_snapshot' => 0,
            'delivered_total_value' => 0,
            'delivered_at' => '2026-06-20',
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscWorkerGroups.0.social_worker_id', $otherWorker->id)
            ->call('requestSaveConfirmation')
            ->assertHasErrors(['miscWorkerGroups.0.social_worker_id'])
            ->assertSet('confirmingBatchSave', false);

        $this->assertDatabaseHas('service_social_worker', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
        ]);
    }

    public function test_editing_misc_service_preserves_used_category_when_safe_fields_change(): void
    {
        [$operator, $worker, $service, $category] = $this->editableMiscService();

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'social_worker_id' => $worker->id,
            'national_id' => '0000000002',
            'full_name' => 'Delivered Recipient',
            'delivered_quantity' => 2,
            'value_per_unit_snapshot' => 0,
            'delivered_total_value' => 0,
            'delivered_at' => '2026-06-20',
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscDescription', 'Updated safe note')
            ->set('miscWorkerGroups.0.categories.0.quantity', '6')
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'service_id' => $service->id,
            'quantity' => 6,
        ]);
        $this->assertSame(1, $service->categories()->count());
        $this->assertDatabaseHas('service_deliveries', [
            'service_id' => $service->id,
            'service_category_id' => $category->id,
        ]);
    }

    public function test_editing_misc_service_redirects_to_misc_services_tab_after_successful_save(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscDescription', 'Updated redirect note')
            ->call('requestSaveConfirmation')
            ->call('confirmSaveBatch')
            ->assertHasNoErrors()
            ->assertRedirect(route('distribution-operator.service-list', ['tab' => ServiceList::TAB_MISC]));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'description' => 'Updated redirect note',
        ]);
        $this->assertSame($worker->id, $service->workerAllocations()->firstOrFail()->social_worker_id);
    }

    public function test_editing_misc_service_header_replaces_removed_step_progress(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->assertSee('data-service-header-title="جزئیات خدمت"', false)
            ->assertDontSee('مرحله فعلی:')
            ->assertDontSee('3 از 4 مرحله')
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_validates_category_rows_as_they_change(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->call('addGroupCategory', 0)
            ->set('miscWorkerGroups.0.categories.1.name', 'Editable Pack')
            ->assertHasErrors(['miscWorkerGroups.0.categories.1.name'])
            ->set('miscWorkerGroups.0.categories.1.name', 'Second Pack')
            ->assertHasNoErrors(['miscWorkerGroups.0.categories.1.name'])
            ->set('miscWorkerGroups.0.categories.1.quantity', '0')
            ->assertHasErrors(['miscWorkerGroups.0.categories.1.quantity'])
            ->set('miscWorkerGroups.0.categories.1.quantity', '1')
            ->assertHasNoErrors(['miscWorkerGroups.0.categories.1.quantity'])
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_validates_duplicate_workers_as_they_change(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->call('addWorkerGroup')
            ->set('miscWorkerGroups.1.social_worker_id', $worker->id)
            ->assertHasErrors(['miscWorkerGroups.1.social_worker_id']);
    }

    public function test_editing_misc_service_validates_used_quantity_as_it_changes(): void
    {
        [$operator, $worker, $service, $category] = $this->editableMiscService();

        ServiceDelivery::query()->create([
            'service_id' => $service->id,
            'service_category_id' => $category->id,
            'delivery_channel' => Service::DELIVERY_CHANNEL_HOME,
            'social_worker_id' => $worker->id,
            'national_id' => '0000000003',
            'full_name' => 'Delivered Recipient',
            'delivered_quantity' => 3,
            'value_per_unit_snapshot' => 0,
            'delivered_total_value' => 0,
            'delivered_at' => '2026-06-20',
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscWorkerGroups.0.categories.0.quantity', '2')
            ->assertHasErrors(['miscWorkerGroups.0.categories.0.quantity'])
            ->set('miscWorkerGroups.0.categories.0.quantity', '3')
            ->assertHasNoErrors(['miscWorkerGroups.0.categories.0.quantity']);
    }

    public function test_editing_misc_service_review_button_stays_clickable_and_surfaces_validation_errors(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->set('miscWorkerGroups.0.categories.0.quantity', '')
            ->assertSee('تأیید خدمت')
            ->call('requestSaveConfirmation')
            ->assertHasErrors(['miscWorkerGroups.0.categories.0.quantity'])
            ->assertSet('confirmingBatchSave', false)
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_confirmation_modal_has_keyboard_backdrop_and_scroll_lock_hooks(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->call('requestSaveConfirmation')
            ->assertSet('confirmingBatchSave', true)
            ->assertSeeHtml('x-on:keydown.escape.window.prevent="close()"')
            ->assertSeeHtml('@click.self="close()"')
            ->assertSeeHtml('dataset.distributionOperatorSheetLocks')
            ->assertSeeHtml('x-ref="cancelButton"')
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_tracks_unsaved_changes_for_navigation_guard(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->assertSet('hasUnsavedChanges', false)
            ->set('miscWorkerGroups.0.worker_search', 'Transient search')
            ->assertSet('hasUnsavedChanges', false)
            ->set('miscDescription', 'Updated unsaved description')
            ->assertSet('hasUnsavedChanges', true)
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_renders_unsaved_change_navigation_hooks(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->assertSeeHtml("window.addEventListener('beforeunload'")
            ->assertSeeHtml('distributionOperatorConfirmServiceEditNavigation')
            ->assertSeeHtml('hasUnsavedChanges')
            ->assertSee($worker->full_name);

        $this->get(route('distribution-operator.edit-service', $service->id))
            ->assertOk()
            ->assertSee('distributionOperatorConfirmServiceEditNavigation', false);
    }

    public function test_editing_misc_service_keeps_group_worker_search_state_isolated(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();
        $otherWorker = SocialWorker::query()->create([
            'worker_code' => 992,
            'first_name' => 'Search',
            'last_name' => 'Target',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id])
            ->call('addWorkerGroup')
            ->set('miscWorkerGroups.0.worker_search', 'First query')
            ->assertSet('miscWorkerGroups.0.worker_search', 'First query')
            ->assertSet('socialWorkerQuery', '')
            ->set('miscWorkerGroups.1.worker_search', 'Search')
            ->assertSet('miscWorkerGroups.0.worker_search', 'First query')
            ->assertSet('miscWorkerGroups.1.worker_search', 'Search')
            ->assertSet('activeWorkerGroupIndex', 1)
            ->assertSee($otherWorker->full_name)
            ->call('selectGroupWorker', 1, $otherWorker->id)
            ->assertSet('miscWorkerGroups.0.worker_search', 'First query')
            ->assertSet('miscWorkerGroups.1.worker_search', '')
            ->assertSet('socialWorkerQuery', '')
            ->assertSet('miscWorkerGroups.1.social_worker_id', $otherWorker->id)
            ->assertSee($worker->full_name);
    }

    public function test_editing_misc_service_review_summary_uses_loaded_group_state_without_extra_queries(): void
    {
        [$operator, $worker, $service] = $this->editableMiscService();

        $this->actingAs($operator);

        $component = Livewire::test(ServiceBatchCreator::class, ['editingServiceId' => $service->id]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $component
            ->set('miscDescription', 'Updated review note')
            ->assertSee($service->name)
            ->assertSee($worker->full_name)
            ->assertSet('editingServiceName', $service->name);

        $this->assertSame([], array_values(array_filter(
            $queries,
            fn (string $sql): bool => str_contains($sql, 'from `social_workers`')
                || str_contains($sql, 'from `services`')
        )));
    }

    private function editableMiscService(): array
    {
        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            'is_admin' => false,
        ]);
        $worker = SocialWorker::query()->create([
            'worker_code' => 990,
            'first_name' => 'Editable',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);
        $serviceName = ServiceName::query()->create([
            'name' => 'Editable Misc',
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);
        $service = Service::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Editable Misc',
            'service_type' => 'individual',
            'supports_gate_delivery' => false,
            'supports_home_delivery' => true,
            'description' => null,
            'total_quantity' => 5,
            'total_service_value' => 0,
            'distribution_start_date' => '2026-06-20',
            'distribution_end_date' => '2026-06-20',
            'status' => 'approved',
            'quantity_delivered' => 0,
            'created_by' => $operator->id,
        ]);
        $category = $service->categories()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Editable Pack',
            'quantity' => 5,
            'unit' => 'pack',
            'value' => 0,
            'sort_id' => 1,
            'created_by' => $operator->id,
        ]);

        $service->workerAllocations()->create([
            'service_category_id' => $category->id,
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 5,
            'assigned_by_user_id' => $operator->id,
        ]);

        return [$operator, $worker, $service, $category];
    }
}
