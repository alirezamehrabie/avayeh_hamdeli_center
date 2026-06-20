<?php

namespace Tests\Feature;

use App\Livewire\DistributionOperators\ServiceBatchCreator;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->set('serviceBlocks.0.service_name', 'Operator Package')
            ->set('serviceBlocks.0.service_type', 'individual')
            ->set('serviceBlocks.0.total_quantity', '6')
            ->set('serviceBlocks.0.unit', 'pack')
            ->set('serviceBlocks.0.date_year', '1405')
            ->set('serviceBlocks.0.date_month', '3')
            ->set('serviceBlocks.0.date_day', '30')
            ->set('serviceBlocks.0.social_worker_id', $worker->id)
            ->set('serviceBlocks.0.social_worker_query', $worker->full_name)
            ->call('saveBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_social_worker', [
            'social_worker_id' => $worker->id,
            'allocated_quantity' => 6,
            'assigned_by_user_id' => $operator->id,
        ]);
    }
}
