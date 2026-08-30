<?php

namespace Tests\Feature;

use App\Models\ManagerNotificationPreference;
use App\Models\User;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotifyManagersOfUserLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_of_user_with_enabled_preference_notifies_manager(): void
    {
        $manager = User::factory()->create([
            'first_name' => 'مدیر',
            'last_name' => 'سیستم',
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        $operator = User::factory()->create([
            'first_name' => 'علی',
            'last_name' => 'محمدی',
            'access_level' => User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
        ]);

        ManagerNotificationPreference::query()->create([
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'enabled' => true,
            'target_type' => NotificationEventRegistry::TARGET_ALL,
        ]);

        $response = $this->actingAs($operator)->post('/logout');

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('manager_notifications', [
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'actor_id' => $operator->id,
            'actor_name' => 'علی محمدی',
            'title' => 'خروج کاربر از سیستم',
        ]);
    }

    public function test_logout_of_manager_does_not_create_notification(): void
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        ManagerNotificationPreference::query()->create([
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'enabled' => true,
            'target_type' => NotificationEventRegistry::TARGET_ALL,
        ]);

        $this->actingAs($manager)->post('/logout');

        $this->assertDatabaseCount('manager_notifications', 0);
    }

    public function test_logout_with_disabled_preference_does_not_create_notification(): void
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        $operator = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
        ]);

        ManagerNotificationPreference::query()->create([
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'enabled' => false,
            'target_type' => NotificationEventRegistry::TARGET_ALL,
        ]);

        $this->actingAs($operator)->post('/logout');

        $this->assertDatabaseCount('manager_notifications', 0);
    }

    public function test_logout_respects_role_targeting(): void
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        $socialWorker = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
        ]);

        $regularUser = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
        ]);

        ManagerNotificationPreference::query()->create([
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'enabled' => true,
            'target_type' => NotificationEventRegistry::TARGET_ROLES,
            'target_roles' => [User::ACCESS_LEVEL_SOCIAL_WORKER],
        ]);

        $this->actingAs($regularUser)->post('/logout');
        $this->assertDatabaseCount('manager_notifications', 0);

        $this->actingAs($socialWorker)->post('/logout');
        $this->assertDatabaseHas('manager_notifications', [
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_USER_LOGOUT,
            'actor_id' => $socialWorker->id,
        ]);
    }

    public function test_notification_settings_screen_renders_logout_event_toggle(): void
    {
        $manager = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);

        $this->actingAs($manager);

        Livewire::test(\App\Livewire\Admin\Notifications\NotificationSettings::class)
            ->assertSee('خروج کاربران از سیستم')
            ->assertSee('user.logout')
            ->assertSee('preferences.user_logout.enabled');
    }
}
