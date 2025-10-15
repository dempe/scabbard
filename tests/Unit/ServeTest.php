<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class ServeTest extends TestCase
{
  // Stub the build command so the watcher doesn't loop forever.
  protected function setUp(): void
  {
    parent::setUp();

    /** @var \Illuminate\Foundation\Console\Kernel $kernel */
    $kernel = $this->app->make(\Illuminate\Foundation\Console\Kernel::class);

    $kernel->registerCommand(new class ($this->app->make(\Scabbard\Content\FrontMatterParser::class)) extends \Scabbard\Console\Commands\Build {
      public function handle(): void
      {
        $this->buildSite();
      }
    });
  }

  public function test_site_serve_runs_watch_and_serves(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', []);
    Config::set('scabbard.routes', ['/serve' => 'serve.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.serve_port', 5678);
    app('router')->get('/serve', fn () => view('home'));

    $command = new class () extends \Scabbard\Console\Commands\Serve {
      public function handle(): void
      {
        \Illuminate\Support\Facades\Artisan::call('scabbard:build');
      }
    };
    $command->setLaravel($this->app);

    $command->handle();

    $this->assertTrue(File::exists("{$tempOutputDir}/serve.html"));

    File::deleteDirectory($tempOutputDir);
  }
}
