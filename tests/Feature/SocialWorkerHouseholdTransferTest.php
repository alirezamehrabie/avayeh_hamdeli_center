<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\IndexSocialWorkers;
use App\Livewire\SocialWorkers\TransferHouseholds;
use App\Models\Guardian;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\SocialWorkers\HouseholdTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerHouseholdTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->manager());
    }

    public function test_bulk_transfer_moves_all_guardians_and_refreshes_statistics(): void
    {
        $source = $this->worker(9001);
        $target = $this->worker(9002);

        $this->guardianFor($source, 900101);
        $this->guardianFor($source, 900102);

        $moved = app(HouseholdTransferService::class)->transfer($source, $target);

        $this->assertSame(2, $moved);
        $this->assertSame(0, $source->guardians()->count());
        $this->assertSame(2, $target->guardians()->count());

        $this->assertSame(0, (int) $source->fresh()->covered_households_count);
        $this->assertSame(2, (int) $target->fresh()->covered_households_count);
    }

    public function test_selective_transfer_moves_only_chosen_guardians_and_ignores_foreign_ids(): void
    {
        $source = $this->worker(9101);
        $target = $this->worker(9102);
        $other = $this->worker(9103);

        $keep = $this->guardianFor($source, 910101);
        $move = $this->guardianFor($source, 910102);
        $foreign = $this->guardianFor($other, 910103);

        $moved = app(HouseholdTransferService::class)
            ->transfer($source, $target, [$move->id, $foreign->id]);

        $this->assertSame(1, $moved);
        $this->assertSame($target->id, $move->fresh()->social_worker_id);
        $this->assertSame($source->id, $keep->fresh()->social_worker_id);
        // A guardian that does not belong to the source is never moved.
        $this->assertSame($other->id, $foreign->fresh()->social_worker_id);
    }

    public function test_transfer_rejects_same_source_and_target(): void
    {
        $source = $this->worker(9201);

        $this->expectException(InvalidArgumentException::class);

        app(HouseholdTransferService::class)->transfer($source, $source);
    }

    public function test_transfer_rejects_inactive_target(): void
    {
        $source = $this->worker(9301);
        $target = $this->worker(9302);
        $target->deactivate();

        // The active global scope hides the deactivated worker; re-fetch without it.
        $inactiveTarget = SocialWorker::withoutGlobalScope('active')->withTrashed()->find($target->id);

        $this->expectException(InvalidArgumentException::class);

        app(HouseholdTransferService::class)->transfer($source, $inactiveTarget);
    }

    public function test_transfer_all_and_deactivate_reassigns_then_deactivates_source(): void
    {
        $source = $this->worker(9401);
        $target = $this->worker(9402);

        $this->guardianFor($source, 940101);
        $this->guardianFor($source, 940102);

        $moved = app(HouseholdTransferService::class)->transferAllAndDeactivate($source, $target);

        $this->assertSame(2, $moved);

        $freshSource = SocialWorker::withoutGlobalScope('active')->withTrashed()->find($source->id);
        $this->assertFalse((bool) $freshSource->is_active);
        $this->assertTrue($freshSource->trashed());

        // No household is left pointing at the deactivated worker.
        $this->assertSame(0, Guardian::query()->where('social_worker_id', $source->id)->count());
        $this->assertSame(2, $target->guardians()->count());
    }

    public function test_deactivation_rolls_back_transfer_when_it_fails(): void
    {
        $source = $this->worker(9501);
        $target = $this->worker(9502);
        $guardian = $this->guardianFor($source, 950101);

        // Force a failure after households have been moved but before the transaction commits.
        $failing = new class extends HouseholdTransferService
        {
            protected function moveHouseholds(SocialWorker $source, SocialWorker $target, ?array $guardianIds, bool $refreshSource): int
            {
                $moved = parent::moveHouseholds($source, $target, $guardianIds, $refreshSource);

                throw new \RuntimeException('boom');
            }
        };

        try {
            $failing->transferAllAndDeactivate($source, $target);
            $this->fail('Expected the transfer to throw.');
        } catch (\RuntimeException $exception) {
            // expected
        }

        // Everything rolled back: guardian still on the source, source still active.
        $this->assertSame($source->id, $guardian->fresh()->social_worker_id);
        $this->assertTrue((bool) $source->fresh()->is_active);
    }

    public function test_index_deactivation_is_blocked_and_opens_modal_when_worker_has_households(): void
    {
        $worker = $this->worker(9601);
        $this->guardianFor($worker, 960101);

        Livewire::test(IndexSocialWorkers::class)
            ->call('deleteSocialWorker', $worker->id)
            ->assertDispatched('open-reassign-before-deactivate', workerId: $worker->id);

        // The worker is NOT deactivated yet.
        $this->assertTrue((bool) $worker->fresh()->is_active);
    }

    public function test_index_deactivation_is_immediate_when_worker_has_no_households(): void
    {
        $worker = $this->worker(9701);

        Livewire::test(IndexSocialWorkers::class)
            ->call('deleteSocialWorker', $worker->id)
            ->assertNotDispatched('open-reassign-before-deactivate');

        $fresh = SocialWorker::withoutGlobalScope('active')->withTrashed()->find($worker->id);
        $this->assertFalse((bool) $fresh->is_active);
        $this->assertTrue($fresh->trashed());
    }

    public function test_transfer_component_confirm_moves_selected_households(): void
    {
        $source = $this->worker(9801);
        $target = $this->worker(9802);
        $move = $this->guardianFor($source, 980101);
        $this->guardianFor($source, 980102);

        Livewire::test(TransferHouseholds::class)
            ->call('openForTransfer', $source->id)
            ->set('mode', 'selective')
            ->set('selectedGuardianIds', [$move->id])
            ->call('selectSocialWorker', $target->id)
            ->call('confirm')
            ->assertDispatched('households-transferred');

        $this->assertSame($target->id, $move->fresh()->social_worker_id);
        $this->assertSame(1, $source->guardians()->count());
    }

    public function test_transfer_component_aborts_for_user_without_permission(): void
    {
        $this->actingAs($this->regularUser());

        // mount() enforces the manage-social-workers gate and aborts 403.
        Livewire::test(TransferHouseholds::class)->assertForbidden();
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function regularUser(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]);
    }

    private function worker(int $code): SocialWorker
    {
        return SocialWorker::query()->create([
            'worker_code' => $code,
            'first_name' => 'Worker',
            'last_name' => (string) $code,
            'is_active' => true,
        ]);
    }

    private function guardianFor(SocialWorker $worker, int $code): Guardian
    {
        return Guardian::query()->create([
            'social_worker_id' => $worker->id,
            'guardian_code' => $code,
            'first_name' => 'Guardian',
            'last_name' => (string) $code,
        ]);
    }
}
