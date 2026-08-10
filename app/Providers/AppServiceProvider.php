<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        Gate::define('viewApiDocs', function () {
            return filter_var(
                env('SCRAMBLE_ENABLED', app()->environment('local')),
                FILTER_VALIDATE_BOOLEAN
            );
        });
    }
}
