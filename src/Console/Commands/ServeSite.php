<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

class ServeSite extends Command
{
  protected $signature = 'site:serve {--once}';

  protected $description = 'Watch the site and serve the built output';

  public function handle()
  {
    $outputPath = Config::get('scabbard.output_path', base_path('output'));
    $port = Config::get('scabbard.serve_port', 8000);

    $router = base_path('serve_router.php');
    $process = new Process([
      'php',
      '-S', "127.0.0.1:{$port}",
      '-t', $outputPath,
      $router,
    ]);
    $process->start();

    $this->info('[' . now()->format('H:i:s') . '] ' . 'Serving site on http://127.0.0.1:' . $port);

    Artisan::call('site:watch', ['--once' => $this->option('once')], $this->output);

    $this->info('[' . now()->format('H:i:s') . '] ' . 'Serving site on http://127.0.0.1:' . $port);

    $process->stop();

    $this->info('[' . now()->format('H:i:s') . '] ' . 'Server stopped.');
  }
}
