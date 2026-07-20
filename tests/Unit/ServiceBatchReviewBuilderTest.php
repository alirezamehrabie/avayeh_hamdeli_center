<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Services\Deliveries\ServiceBatchReviewBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class ServiceBatchReviewBuilderTest extends TestCase
{
    public function test_it_builds_a_miscellaneous_review_without_livewire_or_database_access(): void
    {
        $summary = app(ServiceBatchReviewBuilder::class)->buildMisc(
            categories: [
                ['name' => 'Rice', 'unit' => 'pack', 'quantity' => '2'],
                ['name' => '', 'unit' => 'pack', 'quantity' => '4'],
                ['name' => 'Oil', 'unit' => 'pack', 'quantity' => '1000'],
            ],
            unitOptions: ['pack' => 'Pack'],
            worker: null,
            workerQuery: 'Worker search',
            serviceName: 'Misc service',
            serviceType: 'Individual',
            date: '1405/04/29',
            description: 'Description',
            title: 'Create review',
        );

        $this->assertSame('misc', $summary['mode']);
        $this->assertSame('Create review', $summary['title']);
        $this->assertSame('Worker search', $summary['worker_name']);
        $this->assertSame('2.00', $summary['rows'][0]['quantity_label']);
        $this->assertSame(1, $summary['large_quantity_rows_count']);
        $this->assertSame('1,002.00', $summary['total_quantity_label']);
    }

    public function test_it_builds_predefined_rows_from_a_shared_remaining_pool(): void
    {
        $serviceName = new ServiceName(['name' => 'Catalog service']);
        $service = new Service([
            'id' => 10,
            'name' => 'Catalog service',
            'code' => 'S-10',
        ]);
        $service->forceFill(['id' => 10]);
        $service->setRelation('serviceName', $serviceName);

        $rice = new ServiceCategory([
            'id' => 1,
            'name' => 'Rice',
            'unit' => 'pack',
            'quantity' => 10,
        ]);
        $rice->forceFill(['id' => 1]);
        $service->setRelation('categories', new EloquentCollection([$rice]));

        $summary = app(ServiceBatchReviewBuilder::class)->buildPredefined(
            service: $service,
            categories: new EloquentCollection([$rice]),
            metrics: [
                1 => ['quantity' => 10.0, 'allocated' => 0.0, 'assignable' => 10.0],
            ],
            workerGroups: [
                [
                    'worker_display' => 'First worker',
                    'worker_code' => '101',
                    'allocations' => [1 => '4'],
                ],
                [
                    'worker_display' => 'Second worker',
                    'worker_code' => '102',
                    'allocations' => [1 => '6'],
                ],
            ],
            unitOptions: ['pack' => 'Pack'],
            formatQuantity: fn (float $quantity, string $unit): string => number_format($quantity, 0),
            fallbackServiceName: 'Selected service',
            fallbackWorkerName: 'Worker',
            dateLabel: 'After confirmation',
            title: 'Allocation review',
        );

        $this->assertSame('predefined', $summary['mode']);
        $this->assertSame('Allocation review', $summary['title']);
        $this->assertSame('2 Worker', $summary['worker_name']);
        $this->assertSame(2, count($summary['groups']));
        $this->assertSame('6', $summary['groups'][0]['rows'][0]['remaining_label']);
        $this->assertSame('0', $summary['groups'][1]['rows'][0]['remaining_label']);
        $this->assertSame(1, $summary['depleting_rows_count']);
    }

    public function test_it_marks_an_empty_edit_description_for_the_existing_confirmation_warning(): void
    {
        $summary = app(ServiceBatchReviewBuilder::class)->buildMiscEdit(
            workerGroups: [[
                'worker_display' => 'Editable worker',
                'worker_code' => '200',
                'categories' => [[
                    'name' => 'Pack',
                    'unit' => 'pack',
                    'quantity' => '3',
                ]],
            ]],
            unitOptions: ['pack' => 'Pack'],
            serviceName: 'Editable service',
            serviceType: 'Individual',
            date: '1405/04/29',
            description: ' ',
            fallbackWorkerName: 'Worker',
            title: 'Edit review',
        );

        $this->assertTrue($summary['is_edit']);
        $this->assertTrue($summary['has_empty_description_warning']);
        $this->assertSame('3.00', $summary['total_quantity_label']);
    }
}
