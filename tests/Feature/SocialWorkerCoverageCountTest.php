<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\SocialWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialWorkerCoverageCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_covered_people_count_normalizes_divorced_child_at_home_values(): void
    {
        $worker = SocialWorker::query()->create([
            'worker_code' => 8801,
            'first_name' => 'Coverage',
            'last_name' => 'Worker',
        ]);

        foreach ([
            Guardian::DIVORCED_CHILD_BOY,
            Guardian::DIVORCED_CHILD_GIRL,
            Guardian::DIVORCED_CHILD_BOTH,
            'son',
            'daughter',
            '3',
            'more',
        ] as $index => $value) {
            Guardian::query()->create([
                'social_worker_id' => $worker->id,
                'guardian_code' => 880100 + $index,
                'first_name' => 'Guardian',
                'last_name' => (string) $index,
                'divorced_child_at_home' => $value,
            ]);
        }

        $this->assertSame(10, $worker->calculateCoveredPeopleCount());
    }
}
