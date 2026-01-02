<?php

namespace Modules\Student\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use Modules\Student\Policies\StudentPolicy;
use Modules\Student\Entities\Student;

class StudentServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path('Student', 'Database/Migrations'));
        Gate::policy(Student::class, StudentPolicy::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('Student', 'Config/config.php') => config_path('student.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
