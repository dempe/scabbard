<?php

namespace Scabbard\Console\Commands\Concerns;

trait RequiresScabbardConfig
{
  protected function ensureScabbardConfigExists(): bool
  {
    if (! file_exists(config_path('scabbard.php'))) {
      $this->error('Scabbard config not found. Run: php artisan vendor:publish --tag=scabbard-config');
      return false;
    }

    return true;
  }
}
