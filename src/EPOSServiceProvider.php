<?php

namespace FaganChalabizada\EPOS;

use Illuminate\Support\ServiceProvider;

class EPOSServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/routes.php');
        $this->publishes([
            __DIR__ . '/Config/EPOS.php' => config_path('EPOS.php'),
        ]);
        $this->publishes([
            __DIR__ . '/Controller/EPOSController.php' => app_path('Http/Controllers/EPOSController.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('EPOS', function () {
            return new \FaganChalabizada\EPOS\EPOS;
        });
    }
}
