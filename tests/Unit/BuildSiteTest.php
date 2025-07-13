<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Tests\TestCase;

class BuildSiteTest extends TestCase
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
        Config::set('scabbard.views', ['test.html' => 'home']);
        Config::set('scabbard.output_path', $tempOutputDir);

        Artisan::call('site:build');

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
        Config::set('scabbard.views', ['bad.html' => 'missing-view']);
        Config::set('scabbard.output_path', $tempOutputDir);

        Artisan::call('site:build');

        $this->assertFalse(File::exists("{$tempOutputDir}/bad.html"));

        File::deleteDirectory($tempOutputDir);
    }
}
