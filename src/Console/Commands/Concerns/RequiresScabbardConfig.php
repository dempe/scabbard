<?php

namespace Scabbard\Console\Commands\Concerns;

trait RequiresScabbardConfig
{
  protected function ensureScabbardConfigExists(): bool
  {
    if (! file_exists(config_path('scabbard.php'))) {
      $this->error('❌ Scabbard config not found.');
      $this->line('Run the following command to publish the default config:');
      $this->line('  php artisan vendor:publish --tag=scabbard-config');
      return false;
    }

    return true;
  }
}
