<?php

namespace Orumad\LaravelAppVersion;

use Illuminate\Support\ServiceProvider;

class TuPaqueteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\UpdateVersion::class,
            ]);
        }
    }

    public function register()
    {
        // Registrar otros servicios o enlaces si es necesario.
    }
}
