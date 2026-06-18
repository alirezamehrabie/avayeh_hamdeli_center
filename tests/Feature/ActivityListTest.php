<?php

namespace Tests\Feature;

use App\Livewire\Activities\ActivityList;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityListTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_start_filter_date_shows_error_instead_of_unfiltered_results(): void
    {
        $this->actingAs($this->manager());

        Activity::query()->create([
            'name' => 'Visible Without Filters',
            'activity_type' => 'workshop',
            'starts_at' => '2026-06-18 09:00:00',
            'status' => 'ongoing',
        ]);

        Livewire::test(ActivityList::class)
            ->assertSee('Visible Without Filters')
            ->set('startsFrom', '1405/03/28abc')
            ->assertHasErrors(['startsFrom'])
            ->assertDontSee('Visible Without Filters');
    }

    public function test_start_filter_accepts_persian_digits(): void
    {
        $this->actingAs($this->manager());

        Activity::query()->create([
            'name' => 'Filtered Workshop',
            'activity_type' => 'workshop',
            'starts_at' => '2026-06-18 09:00:00',
            'status' => 'ongoing',
        ]);

        Activity::query()->create([
            'name' => 'Older Workshop',
            'activity_type' => 'workshop',
            'starts_at' => '2026-06-17 09:00:00',
            'status' => 'ongoing',
        ]);

        Livewire::test(ActivityList::class)
            ->set('startsFrom', '۱۴۰۵/۰۳/۲۸')
            ->assertHasNoErrors(['startsFrom'])
            ->assertSee('Filtered Workshop')
            ->assertDontSee('Older Workshop');
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
