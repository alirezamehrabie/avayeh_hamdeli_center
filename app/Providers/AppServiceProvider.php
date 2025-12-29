<?php

namespace App\Providers;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\User;
use App\Observers\GuardianObserver;
use App\Observers\PersonObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Guardian::observe(GuardianObserver::class);
        Person::observe(PersonObserver::class);

        Gate::define('manage-people', function (User $user) {
            // اگر ستون is_admin برابر true بود، اجازه بده
            return $user->is_admin;
        });

        // تعریف یک Gate کلی برای دسترسی به کل پنل ادمین
        Gate::define('access-admin-panel', function (User $user) {
            return $user->is_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-social-workers', function ($user) {
            return $user->is_admin;
        });
    }
}
