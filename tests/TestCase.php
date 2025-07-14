<?php

namespace Scabbard\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base test case for package tests.
 *
 * @property \Illuminate\Foundation\Application $app
 */
abstract class TestCase extends BaseTestCase
{
  protected function getPackageProviders($app)
  {
    return [\Scabbard\ScabbardServiceProvider::class];
  }

  protected function setUp(): void
  {
    parent::setUp();
    $configFile = base_path('config/scabbard.php');
    \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($configFile));
    if (! file_exists($configFile)) {
      file_put_contents($configFile, "<?php return [];\n");
    }
  }

  protected function getEnvironmentSetUp($app)
  {
    $app['config']->set('scabbard.output_path', __DIR__ . '/output');
    $app['config']->set('view.paths', [__DIR__ . '/views']);
  }
}
