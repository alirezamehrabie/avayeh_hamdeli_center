<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityScanner;
use App\Models\Activity;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityScannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_search_prefers_index_friendly_prefix_matches(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $activity = Activity::query()->create([
            'name' => 'Manual Search Activity',
            'activity_type' => 'group_activity',
            'status' => 'ongoing',
            'created_by' => $user->id,
        ]);

        $codeMatch = Person::query()->create([
            'first_name' => 'Code',
            'last_name' => 'Match',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $middleNameMatch = Person::query()->create([
            'first_name' => 'Middle',
            'last_name' => 'Contains',
            'national_id' => '2234567890',
            'person_code' => '24001',
        ]);

        $this->actingAs($user);

        Livewire::test(ActivityScanner::class, ['activityId' => $activity->id])
            ->set('manualSearch', '4')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->isEmpty())
            ->set('manualSearch', '14')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->contains('id', $codeMatch->id))
            ->set('manualSearch', 'dd')
            ->assertViewHas('manualCandidates', fn ($candidates) => ! $candidates->contains('id', $middleNameMatch->id))
            ->set('manualSearch', 'idd')
            ->assertViewHas('manualCandidates', fn ($candidates) => $candidates->contains('id', $middleNameMatch->id));
    }
}
