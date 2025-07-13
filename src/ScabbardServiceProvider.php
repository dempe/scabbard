<?php

namespace Scabbard;

use Illuminate\Support\ServiceProvider;
use Scabbard\Console\Commands\BuildSite;
use Scabbard\Console\Commands\WatchSite;
use Scabbard\Console\Commands\ServeSite;

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
            BuildSite::class,
            WatchSite::class,
            ServeSite::class,
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

