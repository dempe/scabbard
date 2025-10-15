<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Scabbard\Console\Commands\Concerns\HasTimestampPrefix;
use Scabbard\Console\Commands\Concerns\RequiresScabbardConfig;
use Symfony\Component\Process\Process;

class Serve extends Command
{
  use HasTimestampPrefix;
  use RequiresScabbardConfig;

  protected $signature = 'scabbard:serve {--drafts : Include drafts in the generated output}';

  protected $description = 'Watch the site and serve the built output';

  public function handle()
  {
    if (! $this->ensureScabbardConfigExists()) {
      return Command::FAILURE;
    }

    $outputPath = Config::get('scabbard.output_path', base_path('output'));
    $port = Config::get('scabbard.serve_port', 8000);
    $notFound = Config::get('scabbard.not_found_page', '/404.html');

    $router = base_path('router.php');

    if (! file_exists($router)) {
      $router = realpath(__DIR__ . '/../../../router.php');
    }
    $server = new Process([
      'php',
      '-S',
      "127.0.0.1:{$port}",
      '-t',
      $outputPath,
      $router,
    ], null, ['SCABBARD_NOT_FOUND' => $notFound]);
    $server->start();

    $builderCommand = ['php', 'artisan', 'scabbard:build', '--watch'];
    if ($this->option('drafts')) {
      $builderCommand[] = '--drafts';
    }

    $builder = new Process($builderCommand);
    $builder->setTimeout(null);
    $builder->start(function ($type, $buffer) {
      echo $buffer;
    });

    $this->info($this->timestampPrefix() . 'Serving site on http://127.0.0.1:' . $port);

    while ($builder->isRunning()) {
      $out = $server->getIncrementalOutput();
      if ($out !== '') {
        $this->output->write($out);
      }

      $err = $server->getIncrementalErrorOutput();
      if ($err !== '') {
        $this->output->write($err);
      }

      usleep(100000);
    }

    $server->stop();

    $this->info($this->timestampPrefix() . 'Server stopped.');
  }
}
