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

  public function test_build_site_fingerprints_nested_files(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists("{$tempInputDir}/assets/css", 0755);
    File::put("{$tempInputDir}/assets/css/nest.css", 'body{}');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/nested' => 'nested.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.fingerprint', ['assets/**/*.css']);

    app('router')->get('/nested', fn () => view('nested'));

    Artisan::call('scabbard:build');

    $files = collect(File::allFiles($tempOutputDir));
    $fingerprinted = $files->first(fn ($file) => str_starts_with($file->getFilename(), 'nest.') && $file->getExtension() === 'css');

    $this->assertNotNull($fingerprinted);
    $this->assertFalse(File::exists("{$tempOutputDir}/assets/css/nest.css"));

    $html = File::get("{$tempOutputDir}/nested.html");
    $this->assertStringContainsString($fingerprinted->getFilename(), $html);

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_does_not_fingerprint_when_disabled(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);
    File::put("{$tempInputDir}/dummy.txt", 'dummy');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/plain' => 'plain.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.fingerprint', []);

    app('router')->get('/plain', fn () => view('asset'));

    Artisan::call('scabbard:build');

    $this->assertTrue(File::exists("{$tempOutputDir}/dummy.txt"));

    $html = File::get("{$tempOutputDir}/plain.html");
    $this->assertStringContainsString('/dummy.txt', $html);

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_fingerprints_files_with_query_parameters(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempInputDir);
    File::put("{$tempInputDir}/dummy.txt", 'dummy');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/query' => 'query.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.fingerprint', ['dummy.txt']);

    app('router')->get('/query', fn () => view('query'));

    Artisan::call('scabbard:build');

    $files = collect(File::allFiles($tempOutputDir));
    $fingerprinted = $files->first(function ($file) {
      return str_starts_with($file->getFilename(), 'dummy.') && $file->getExtension() === 'txt';
    });

    $this->assertNotNull($fingerprinted);

    $html = File::get("{$tempOutputDir}/query.html");
    $this->assertStringContainsString($fingerprinted->getFilename() . '?v=1', $html);

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_updates_css_font_references(): void
  {
    $tempInputDir = base_path('tests/tmp_public');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists("{$tempInputDir}/assets/fonts", 0755);
    File::ensureDirectoryExists("{$tempInputDir}/assets/css", 0755);
    File::put("{$tempInputDir}/assets/fonts/foo.woff", 'dummyfont');
    File::put("{$tempInputDir}/assets/css/font.css", '@font-face { src: url("../fonts/foo.woff"); }');

    Config::set('scabbard.copy_dirs', [$tempInputDir]);
    Config::set('scabbard.routes', ['/font' => 'font.html']);
    Config::set('scabbard.output_path', $tempOutputDir);
    Config::set('scabbard.fingerprint', ['assets/fonts/*.woff']);

    app('router')->get('/font', fn () => view('font'));

    Artisan::call('scabbard:build');

    $files = collect(File::allFiles($tempOutputDir . '/assets/fonts'));
    $fingerprinted = $files->first(fn ($file) => str_starts_with($file->getFilename(), 'foo.') && $file->getExtension() === 'woff');

    $this->assertNotNull($fingerprinted);
    $this->assertFalse(File::exists("{$tempOutputDir}/assets/fonts/foo.woff"));

    $css = File::get("{$tempOutputDir}/assets/css/font.css");
    $this->assertStringContainsString($fingerprinted->getFilename(), $css);

    File::deleteDirectory($tempInputDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_skips_drafts_by_default(): void
  {
    $tempContentDir = base_path('tests/tmp_content');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempContentDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempContentDir);
    File::put("{$tempContentDir}/published.md", <<<MD
---
title: Published Post
draft: false
---

Content
MD);

    File::put("{$tempContentDir}/draft.md", <<<MD
---
title: Draft Post
draft: true
---

Content
MD);

    $originalCopyDirs = Config::get('scabbard.copy_dirs');
    $originalContentDirs = Config::get('scabbard.content_dirs');
    $originalRoutes = Config::get('scabbard.routes');
    $originalOutputPath = Config::get('scabbard.output_path');

    Config::set('scabbard.copy_dirs', [$tempContentDir]);
    Config::set('scabbard.content_dirs', [$tempContentDir]);
    Config::set('scabbard.routes', []);
    Config::set('scabbard.output_path', $tempOutputDir);

    Artisan::call('scabbard:build');

    $this->assertTrue(File::exists("{$tempOutputDir}/published.md"));
    $this->assertFalse(File::exists("{$tempOutputDir}/draft.md"));

    Config::set('scabbard.copy_dirs', $originalCopyDirs);
    Config::set('scabbard.content_dirs', $originalContentDirs);
    Config::set('scabbard.routes', $originalRoutes);
    Config::set('scabbard.output_path', $originalOutputPath);

    File::deleteDirectory($tempContentDir);
    File::deleteDirectory($tempOutputDir);
  }

  public function test_build_site_includes_drafts_when_requested(): void
  {
    $tempContentDir = base_path('tests/tmp_content');
    $tempOutputDir = base_path('tests/tmp_output');

    File::deleteDirectory($tempContentDir);
    File::deleteDirectory($tempOutputDir);

    File::ensureDirectoryExists($tempContentDir);
    File::put("{$tempContentDir}/draft.md", <<<MD
---
title: Draft Post
draft: true
---

Content
MD);

    $originalCopyDirs = Config::get('scabbard.copy_dirs');
    $originalContentDirs = Config::get('scabbard.content_dirs');
    $originalRoutes = Config::get('scabbard.routes');
    $originalOutputPath = Config::get('scabbard.output_path');

    Config::set('scabbard.copy_dirs', [$tempContentDir]);
    Config::set('scabbard.content_dirs', [$tempContentDir]);
    Config::set('scabbard.routes', []);
    Config::set('scabbard.output_path', $tempOutputDir);

    Artisan::call('scabbard:build', ['--drafts' => true]);

    $this->assertTrue(File::exists("{$tempOutputDir}/draft.md"));

    Config::set('scabbard.copy_dirs', $originalCopyDirs);
    Config::set('scabbard.content_dirs', $originalContentDirs);
    Config::set('scabbard.routes', $originalRoutes);
    Config::set('scabbard.output_path', $originalOutputPath);

    File::deleteDirectory($tempContentDir);
    File::deleteDirectory($tempOutputDir);
  }
}
