<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class SiteServeTest extends TestCase
{
    public function test_site_serve_runs_watch_and_serves(): void
    {
        $tempOutputDir = base_path('tests/tmp_output');

        File::deleteDirectory($tempOutputDir);

        Config::set('buildsite.copy_dirs', []);
        Config::set('buildsite.views', ['serve.html' => 'home']);
        Config::set('buildsite.output_path', $tempOutputDir);
        Config::set('buildsite.serve_port', 5678);

        Artisan::call('site:serve', ['--once' => true]);
        $output = Artisan::output();

        $this->assertTrue(File::exists("{$tempOutputDir}/serve.html"));
        $this->assertStringContainsString('127.0.0.1:5678', $output);

        File::deleteDirectory($tempOutputDir);
    }
}
