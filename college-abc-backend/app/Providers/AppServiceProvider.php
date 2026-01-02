<?php

namespace App\Providers;

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
        // Charger les migrations des différentes bases de données
        $this->loadMigrationsFrom(database_path('migrations/core'));
        $this->loadMigrationsFrom(database_path('migrations/mp'));
        $this->loadMigrationsFrom(database_path('migrations/college'));
        $this->loadMigrationsFrom(database_path('migrations/lycee'));

        // Gate temporairement désactivé
        // Gate::before(function ($user, $ability) {
        //     return $user->hasRole('super_admin') ? true : null;
        // });
    }
}
