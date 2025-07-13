<?php

namespace Scabbard\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [\Scabbard\ScabbardServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('scabbard.output_path', __DIR__.'/output');
        $app['config']->set('view.paths', [__DIR__.'/views']);
    }
}

