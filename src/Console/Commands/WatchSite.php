<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Scabbard\Console\Commands\Concerns\WatchesFiles;

class WatchSite extends Command
{
  use WatchesFiles;

  protected $signature = 'site:watch {--once}';

  protected $description = 'Watch for changes and rebuild the static site';

  public function handle()
  {
    $this->info('[' . now()->format('H:i:s') . '] ' . 'Watching for changes...');

    $lastHash = null;

    do {
      $currentHash = $this->hashAllWatchedFiles();

      if ($lastHash !== $currentHash) {
        $lastHash = $currentHash;
        $this->info('[' . now()->format('H:i:s') . '] ' . 'Rebuilding...');
        Artisan::call('site:build', [], $this->output);
      }

      $this->trap(SIGINT, function () {
        $this->info('[' . now()->format('H:i:s') . '] ' .  'Watcher interrupted. Exiting.');
        exit;
      });

      usleep(500000);
    } while (! $this->option('once'));
  }
}
