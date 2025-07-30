<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Scabbard\Tests\TestCase;

class BuildTest extends TestCase
{
  public function test_build_site_command_generates_static_files(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);
    File::put("{$tempInputDir}/dummy.txt", 'dummy');
    File::put("{$tempInputDir}/index.php", 'index');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/test' => 'test.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    app('router')->get('/test', fn () => view('home'));

    Artisan::call('scabbard:build');

    $this->assertTrue(File::exists("{$tempOutputDir}/dummy.txt"));
    $this->assertTrue(File::exists("{$tempOutputDir}/test.html"));
    $this->assertFalse(File::exists("{$tempOutputDir}/index.php"));

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_command_handles_invalid_configuration(): void
  {
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempOutputDir);

    Config::set('scabbard.copy_dirs', ['/missing-dir']);
    Config::set('scabbard.routes', ['/bad-route' => 'bad.html']);
    Config::set('scabbard.output_path', $tempOutputDir);

    $result = Artisan::call('scabbard:build');

    $this->assertSame(Command::SUCCESS, $result);
    $this->assertFalse(File::exists("{$tempOutputDir}/bad.html"));

    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_creates_directories_for_routes(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/athena' => 'athena/index.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    app('router')->get('/athena', fn () => view('home'));

    Artisan::call('scabbard:build');

    $this->assertTrue(File::exists("{$tempOutputDir}/athena/index.html"));

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_handles_dynamic_routes(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', []);
    Config::set('scabbard.dynamic_routes', [
      '/posts/{slug}' => [
        'output' => '/posts/{slug}/index.html',
        'values' => 'Scabbard\\Tests\\Fixtures\\Post@slug',
      ],
    ]);
    Config::set('scabbard.output_path', $tempOutputDir);

    app('router')->get('/posts/{slug}', fn ($slug) => view('home'));

    Artisan::call('scabbard:build');

    $this->assertTrue(File::exists("{$tempOutputDir}/posts/alpha/index.html"));
    $this->assertTrue(File::exists("{$tempOutputDir}/posts/beta/index.html"));

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_fingerprints_files(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);
    File::put("{$tempInputDir}/dummy.txt", 'dummy');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/fp' => 'fp.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.fingerprint', ['dummy.txt']);

    app('router')->get('/fp', fn () => view('asset'));

    Artisan::call('scabbard:build');

    $files = collect(File::allFiles($tempOutputDir));
    $fingerprinted = $files->first(function ($file) {
      return str_starts_with($file->getFilename(), 'dummy.') && $file->getExtension() === 'txt';
    });

    $this->assertNotNull($fingerprinted);
    $this->assertFalse(File::exists("{$tempOutputDir}/dummy.txt"));

    $html = File::get("{$tempOutputDir}/fp.html");
    $this->assertStringContainsString($fingerprinted->getFilename(), $html);

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }
}
