<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only honor public/hot for local Vite HMR. A leftover hot file on a
        // live host (or APP_ENV=local in production) points CSS/JS at
        // [::1]:5173 and makes the whole site look unstyled / destroyed.
        $hotFile = public_path('hot');
        $useViteHot = $this->app->environment('local')
            && is_file($hotFile)
            && str_contains((string) @file_get_contents($hotFile), ':5173');

        if (! $useViteHot) {
            Vite::useHotFile(storage_path('framework/vite-hot-disabled'));
        }
    }
}
