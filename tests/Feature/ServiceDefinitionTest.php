<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceDefinition;
use App\Models\Service;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_category_is_auto_created_as_template_when_saving_service_definition(): void
    {
        $user = $this->manager();

        $this->actingAs($user);

        Livewire::test(ServiceDefinition::class)
            ->set('serviceName', 'Emergency Aid Package')
            ->set('serviceType', 'individual')
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Protein Bundle',
                'quantity' => '12',
                'unit' => 'pack',
                'value' => '150,000',
            ]])
            ->call('save');

        $serviceName = ServiceName::query()->where('name', 'Emergency Aid Package')->firstOrFail();
        $service = Service::query()->where('service_name_id', $serviceName->id)->firstOrFail();

        $this->assertDatabaseHas('service_categories', [
            'service_id' => $service->id,
            'service_name_id' => $serviceName->id,
            'name' => 'Protein Bundle',
        ]);

        $this->assertDatabaseHas('service_category_templates', [
            'service_name_id' => $serviceName->id,
            'name' => 'Protein Bundle',
            'created_by' => $user->id,
        ]);
    }

    public function test_soft_deleted_template_is_restored_when_reused_in_service_definition(): void
    {
        $user = $this->manager();
        $serviceName = ServiceName::query()->create([
            'name' => 'Food Basket',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template = ServiceCategoryTemplate::query()->create([
            'service_name_id' => $serviceName->id,
            'name' => 'Seasonal Items',
            'sort_id' => 1,
            'created_by' => $user->id,
        ]);

        $template->delete();

        $this->actingAs($user);

        Livewire::test(ServiceDefinition::class)
            ->set('selectedServiceNameId', $serviceName->id)
            ->set('serviceName', $serviceName->name)
            ->set('serviceType', 'individual')
            ->set('distributionStartDate', '1405/03/30')
            ->set('status', 'draft')
            ->set('categories', [[
                'id' => null,
                'code' => '',
                'name' => 'Seasonal Items',
                'quantity' => '4',
                'unit' => 'pack',
                'value' => '200,000',
            ]])
            ->call('save');

        $this->assertDatabaseHas('service_category_templates', [
            'id' => $template->id,
            'service_name_id' => $serviceName->id,
            'name' => 'Seasonal Items',
        ]);

        $this->assertNull($template->fresh()?->deleted_at);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
