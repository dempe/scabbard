<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

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

        Config::set('buildsite.copy_dirs', [$tempInputDir]);
        Config::set('buildsite.views', ['test.html' => 'home']);
        Config::set('buildsite.output_path', $tempOutputDir);

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

        Config::set('buildsite.copy_dirs', ['/missing-dir']);
        Config::set('buildsite.views', ['bad.html' => 'missing-view']);
        Config::set('buildsite.output_path', $tempOutputDir);

        Artisan::call('site:build');

        $this->assertFalse(File::exists("{$tempOutputDir}/bad.html"));

        File::deleteDirectory($tempOutputDir);
    }
}
