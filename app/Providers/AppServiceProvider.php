<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Check if production env:

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::after(function (User $user, string $ability, ?bool $result, mixed $arguments) {
            return $user->hasRole('admin');
        });
    }
}
