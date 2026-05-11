<?php

namespace App\Providers;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\User;
use App\Observers\GuardianObserver;
use App\Observers\PersonObserver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('users')) {
            $createPayload = [
                'name' => User::PRIMARY_ADMIN_USERNAME,
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ];

            $updatePayload = [
                'is_admin' => true,
                'name' => User::PRIMARY_ADMIN_USERNAME,
            ];

            if (Schema::hasColumn('users', 'access_level')) {
                $createPayload['access_level'] = User::ACCESS_LEVEL_MANAGER;
                $updatePayload['access_level'] = User::ACCESS_LEVEL_MANAGER;
            }

            if (Schema::hasColumn('users', 'permissions')) {
                $createPayload['permissions'] = [User::PERMISSION_FULL_ACCESS];
                $updatePayload['permissions'] = [User::PERMISSION_FULL_ACCESS];
            }

            // اطمینان از وجود و سطح دسترسی حساب مدیریت اصلی
            User::query()->firstOrCreate(
                ['email' => User::PRIMARY_ADMIN_EMAIL],
                $createPayload
            );

            User::query()
                ->where('email', User::PRIMARY_ADMIN_EMAIL)
                ->update($updatePayload);
        }

        Guardian::observe(GuardianObserver::class);
        Person::observe(PersonObserver::class);

        Gate::define('manage-people', function (User $user) {
            return $user->canManagePeople();
        });

        Gate::define('people-register', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_REGISTER);
        });

        Gate::define('people-edit', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_EDIT);
        });

        Gate::define('people-delete', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_DELETE);
        });

        // تعریف یک Gate کلی برای دسترسی به کل پنل ادمین
        Gate::define('access-admin-panel', function (User $user) {
            return $user->canAccessAdminPanel();
        });

        Gate::define('full-access', function (User $user) {
            return $user->hasFullAccess();
        });

        Gate::define('manage-social-workers', function (User $user) {
            return $user->canManageSocialWorkers();
        });
    }
}
