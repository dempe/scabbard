<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class WatchTest extends TestCase
{
  private $buildCommand;

  // Use a stub build command that does not enter the watch loop.
  protected function setUp(): void
  {
    parent::setUp();

    $this->buildCommand = new class () extends \Scabbard\Console\Commands\Build {
      protected $signature = 'scabbard:build {--watch}';

      public function handle(): void
      {
        $this->buildSite();
      }
    };

    /** @var \Illuminate\Foundation\Console\Kernel $kernel */
    $kernel = $this->app->make(\Illuminate\Foundation\Console\Kernel::class);
    $kernel->registerCommand($this->buildCommand);
  }

  public function test_site_watch_triggers_build(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', []);
    Config::set('scabbard.routes', ['/watch' => 'watch.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    app('router')->get('/watch', fn () => view('home'));

    // Run the build command with the --watch option which would normally run
    // indefinitely. The stub registered in setUp ensures it executes only once.
    $this->buildCommand->setLaravel($this->app);
    $style = new \Illuminate\Console\OutputStyle(
      new \Symfony\Component\Console\Input\ArrayInput([]),
      new \Symfony\Component\Console\Output\NullOutput()
    );
    $this->buildCommand->setOutput($style);
    $this->buildCommand->handle();

    $this->assertTrue(File::exists("{$tempOutputDir}/watch.html"));

    File::deleteDirectory($tempOutputDir);
  }
}
