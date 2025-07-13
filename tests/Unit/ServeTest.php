<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class ServeTest extends TestCase
{
  public function test_site_serve_runs_watch_and_serves(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', []);
    Config::set('scabbard.routes', ['/serve' => 'serve.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.serve_port', 5678);
    app('router')->get('/serve', fn () => view('home'));

    Artisan::call('scabbard:serve', ['--once' => true]);

    $this->assertTrue(File::exists("{$tempOutputDir}/serve.html"));

    File::deleteDirectory($tempOutputDir);
  }
}
