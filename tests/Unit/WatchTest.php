<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class WatchTest extends TestCase
{
  public function test_site_watch_triggers_build(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', []);
    Config::set('scabbard.views', ['watch.html' => 'home']);
    Config::set('scabbard.output_path', $tempOutputDir);

    Artisan::call('scabbard:watch', ['--once' => true]);

    $this->assertTrue(File::exists("{$tempOutputDir}/watch.html"));

    File::deleteDirectory($tempOutputDir);
  }
}
