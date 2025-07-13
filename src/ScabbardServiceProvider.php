<?php

namespace Scabbard;

use Illuminate\Support\ServiceProvider;
use Scabbard\Console\Commands\Build;
use Scabbard\Console\Commands\Watch;
use Scabbard\Console\Commands\Serve;

class ScabbardServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    // Merge default config (so we don't overwrite user config)
    $this->mergeConfigFrom(
      __DIR__ . '/../config/scabbard.php',
      'scabbard'
    );

    // Register Artisan commands
    $this->commands([
      Build::class,
      Watch::class,
      Serve::class,
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
