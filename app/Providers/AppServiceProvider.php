<?php

namespace App\Providers;

use App\Models\Guardian;
use App\Models\Person;
use App\Observers\GuardianObserver;
use App\Observers\PersonObserver;
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
        Guardian::observe(GuardianObserver::class);
        Person::observe(PersonObserver::class);
    }
}
