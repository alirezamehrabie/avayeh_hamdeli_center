<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementPasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_defined_password_is_only_stored_as_a_hash(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(UserManagement::class, ['listOnly' => true])
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('username', 'defined-user')
            ->set('password', 'defined-password')
            ->set('password_confirmation', 'defined-password')
            ->set('access_level', User::ACCESS_LEVEL_REGULAR)
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::query()->where('name', 'defined-user')->firstOrFail();

        $this->assertTrue(Hash::check('defined-password', $user->password));
        $this->assertFalse(Schema::hasColumn('users', 'manager_visible_password'));
        $this->assertArrayNotHasKey('manager_visible_password', $user->getAttributes());

        Livewire::test(UserManagement::class, ['listOnly' => true])
            ->call('startEditingUser', $user->id)
            ->assertSet('showEditModal', true)
            ->assertDontSee('رمز عبور فعلی');
    }

    public function test_updating_user_password_only_replaces_the_hash(): void
    {
        $this->actingAs($this->manager());

        $user = User::factory()->create([
            'first_name' => 'Editable',
            'last_name' => 'User',
            'name' => 'editable-user',
            'email' => 'editable-user@local.system',
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'permissions' => [],
        ]);

        Livewire::test(UserManagement::class, ['listOnly' => true])
            ->call('startEditingUser', $user->id)
            ->set('edit_first_name', 'Editable')
            ->set('edit_last_name', 'User')
            ->set('edit_username', 'editable-user')
            ->set('edit_password', 'new-password')
            ->set('edit_password_confirmation', 'new-password')
            ->set('edit_access_level', User::ACCESS_LEVEL_REGULAR)
            ->set('edit_permissions', [])
            ->call('updateUser')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertArrayNotHasKey('manager_visible_password', $user->getAttributes());
    }

    public function test_social_worker_user_can_be_saved_from_admin_edit_modal(): void
    {
        $this->actingAs($this->manager());

        $socialWorker = SocialWorker::query()->create([
            'worker_code' => 10,
            'first_name' => 'Social',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'first_name' => 'Social',
            'last_name' => 'Worker',
            'name' => 'social-worker-user',
            'email' => 'social-worker-user@local.system',
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'permissions' => [],
            'social_worker_id' => $socialWorker->id,
        ]);

        Livewire::test(UserManagement::class, ['listOnly' => true])
            ->call('startEditingUser', $user->id)
            ->assertSet('edit_access_level', User::ACCESS_LEVEL_SOCIAL_WORKER)
            ->set('edit_first_name', 'Edited')
            ->set('edit_last_name', 'Worker')
            ->set('edit_username', 'social-worker-user')
            ->set('edit_permissions', [])
            ->call('updateUser')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Edited', $user->first_name);
        $this->assertSame(User::ACCESS_LEVEL_SOCIAL_WORKER, $user->access_level);
        $this->assertSame([], $user->permissions);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'name' => 'manager-user',
            'email' => 'manager-user@local.system',
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }
}
