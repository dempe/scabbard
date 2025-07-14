<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\NullOutput;
use Scabbard\Tests\TestCase;

class WatchTest extends TestCase
{
  public function test_site_watch_triggers_build(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', []);
    Config::set('scabbard.routes', ['/watch' => 'watch.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    app('router')->get('/watch', fn () => view('home'));

    $command = new class () extends \Scabbard\Console\Commands\Build {
      public function handle(): void
      {
        $this->buildSite();
      }
    };
    $command->setLaravel($this->app);

    $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new NullOutput());

    $this->assertTrue(File::exists("{$tempOutputDir}/watch.html"));

    File::deleteDirectory($tempOutputDir);
  }
}
