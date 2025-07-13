<?php

namespace Scabbard;

use Illuminate\Support\ServiceProvider;
use Scabbard\Console\Commands\BuildCommand;
use Scabbard\Console\Commands\WatchCommand;
use Scabbard\Console\Commands\ServeCommand;

class ScabbardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config (optional)
        $this->mergeConfigFrom(
            __DIR__ . '/../config/scabbard.php',
            'scabbard'
        );

        // Register Artisan commands
        $this->commands([
            BuildCommand::class,
            WatchCommand::class,
            ServeCommand::class,
        ]);
    }

    public function boot(): void
    {
        // Publish config file to host app
        $this->publishes([
            __DIR__ . '/../config/scabbard.php' => config_path('scabbard.php'),
        ], 'scabbard-config');
    }
}

