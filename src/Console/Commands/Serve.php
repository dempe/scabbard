<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Scabbard\Console\Commands\Concerns\HasTimestampPrefix;
use Symfony\Component\Process\Process;

class Serve extends Command
{
  use HasTimestampPrefix;

  protected $signature = 'scabbard:serve';

  protected $description = 'Watch the site and serve the built output';

  public function handle()
  {
    if (! file_exists(config_path('scabbard.php'))) {
      $this->error('Scabbard config not found. Run: php artisan vendor:publish --tag=scabbard-config');
      return Command::FAILURE;
    }

    $outputPath = Config::get('scabbard.output_path', base_path('output'));
    $port = Config::get('scabbard.serve_port', 8000);

    $router = base_path('router.php');
    $process = new Process([
      'php',
      '-S',
      "127.0.0.1:{$port}",
      '-t',
      $outputPath,
      $router,
    ]);
    $process->start();

    $this->info($this->timestampPrefix() . 'Serving site on http://127.0.0.1:' . $port);

    Artisan::call('scabbard:build', ['--watch' => true], $this->output);

    $this->info($this->timestampPrefix() . 'Serving site on http://127.0.0.1:' . $port);

    $process->stop();

    $this->info($this->timestampPrefix() . 'Server stopped.');
  }
}
