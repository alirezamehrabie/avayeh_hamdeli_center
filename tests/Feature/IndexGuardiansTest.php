<?php

namespace Tests\Feature;

use App\Livewire\Guardians\IndexGuardians;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexGuardiansTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardians_list_does_not_eager_load_people_for_every_row(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 710001,
            'first_name' => 'Guardian',
            'last_name' => 'List',
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P710001',
            'national_id' => '2234567890',
            'first_name' => 'Child',
            'last_name' => 'List',
        ]);

        Livewire::test(IndexGuardians::class)
            ->assertViewHas('totalGuardians', 1)
            ->tap(function ($component) {
                $guardians = $component->instance()->guardians;
                $this->assertCount(1, $guardians);
                $this->assertFalse($guardians->first()->relationLoaded('people'));
                $this->assertSame(1, $guardians->first()->people_count);
            });
    }

    public function test_expanded_guardian_people_loads_only_selected_guardian_people(): void
    {
        $this->actingAs($this->manager());

        $selectedGuardian = Guardian::query()->create([
            'guardian_code' => 710002,
            'first_name' => 'Selected',
            'last_name' => 'Guardian',
        ]);
        $otherGuardian = Guardian::query()->create([
            'guardian_code' => 710003,
            'first_name' => 'Other',
            'last_name' => 'Guardian',
        ]);

        Person::query()->create([
            'guardian_id' => $otherGuardian->id,
            'person_code' => 'P710099',
            'national_id' => '3234567899',
            'first_name' => 'Other',
            'last_name' => 'Child',
        ]);
        $first = Person::query()->create([
            'guardian_id' => $selectedGuardian->id,
            'person_code' => 'P710002',
            'national_id' => '3234567890',
            'first_name' => 'First',
            'last_name' => 'Child',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $latest = Person::query()->create([
            'guardian_id' => $selectedGuardian->id,
            'person_code' => 'P710003',
            'national_id' => '3234567891',
            'first_name' => 'Latest',
            'last_name' => 'Child',
            'created_at' => '2026-01-02 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);

        Livewire::test(IndexGuardians::class)
            ->call('toggleGuardian', $selectedGuardian->id)
            ->tap(function ($component) use ($latest, $first) {
                $people = $component->instance()->expandedGuardianPeople;

                $this->assertEqualsCanonicalizing([$latest->id, $first->id], $people->pluck('id')->all());
            });
    }

    public function test_guardians_list_does_not_auto_refresh_stats_on_load(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(IndexGuardians::class)
            ->assertDontSee('wire:init="refreshStatsOnLoad"', false);
    }

    public function test_manual_refresh_updates_stats_and_dispatches_toast(): void
    {
        $this->actingAs($this->manager());

        $guardian = Guardian::query()->create([
            'guardian_code' => 710004,
            'first_name' => 'Manual',
            'last_name' => 'Refresh',
            'children_count' => 1,
            'children_in_house' => 0,
        ]);

        Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => 'P710004',
            'national_id' => '4234567890',
            'first_name' => 'Child',
            'last_name' => 'Refresh',
        ]);

        Livewire::test(IndexGuardians::class)
            ->call('refreshStats')
            ->assertDispatched('guardian-stats-refreshed');

        $this->assertSame(2, $guardian->refresh()->children_in_house);
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
