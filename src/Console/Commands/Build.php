<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Scabbard\Console\Commands\Concerns\WatchesFiles;
use Scabbard\Console\Commands\Concerns\HasTimestampPrefix;
use Scabbard\Console\Commands\Concerns\RequiresScabbardConfig;

class Build extends Command
{
  use WatchesFiles;
  use HasTimestampPrefix;
  use RequiresScabbardConfig;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'scabbard:build {--watch}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Build the static site output';

  /**
   *  Generate a static copy of the site by rendering all routes and saving the output.
   *
   * @returns void No return value.
   */
  public function handle()
  {
    if (! $this->ensureScabbardConfigExists()) {
      return Command::FAILURE;
    }

    if (! $this->option('watch')) {
      $this->buildSite();
      return Command::SUCCESS;
    }

    $this->info($this->timestampPrefix() . 'Watching for changes...');

    $lastHash = null;

    // phpstan falsely reports the condition as always true but the loop is
    // intentionally infinite until interrupted.
    while (true) {
      $currentHash = $this->hashAllWatchedFiles();

      if ($lastHash !== $currentHash) {
        $lastHash = $currentHash;
        $this->info($this->timestampPrefix() . 'Rebuilding...');
        $this->buildSite();
      }

      $this->trap(SIGINT, function () {
        $this->info($this->timestampPrefix() . 'Watcher interrupted. Exiting.');
        exit;
      });

      usleep(500000);
    }
  }

  /**
   *  Generate the static site by copying configured directories and
   *  rendering any configured routes to HTML files.
   *
   * @returns void No return value.
   */
  protected function buildSite()
  {
    \Artisan::call('view:clear');

    // First, create the directory structure
    $outputPath = Config::get('scabbard.output_path', base_path('output'));
    $this->deleteAndCreate($outputPath);

    // Copy configured directories wholesale
    $copyDirs = Config::get('scabbard.copy_dirs', [base_path('public')]);
    foreach ($copyDirs as $dir) {
      if (File::isDirectory($dir)) {
        File::copyDirectory($dir, $outputPath);

        // Remove Laravel's front controller to ensure static 404s work
        $indexPhp = $outputPath . DIRECTORY_SEPARATOR . 'index.php';
        if (File::exists($indexPhp)) {
          File::delete($indexPhp);
        }
      }
    }

    $routes = Config::get('scabbard.routes', []);
    foreach ($routes as $uri => $filename) {
      try {
        $response = app()->handle(Request::create($uri));

        if ($response->getStatusCode() >= 400) {
          $this->error($this->timestampPrefix() . "Route {$uri} failed with status " . $response->getStatusCode());
          continue;
        }

        $filePath = $outputPath . DIRECTORY_SEPARATOR . ltrim($filename, DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, (string) $response->getContent());
      } catch (\Throwable $e) {
        $this->error($this->timestampPrefix() . "Exception rendering {$uri}: " . $e->getMessage());
      }
    }

    $dynamicRoutes = Config::get('scabbard.dynamic_routes', []);
    foreach ($dynamicRoutes as $pattern => $callback) {
      if (! is_callable($callback)) {
        $this->error($this->timestampPrefix() . "Dynamic route {$pattern} is not callable.");
        continue;
      }

      $items = $callback();
      if (! is_iterable($items)) {
        $this->error($this->timestampPrefix() . "Callback {$callback} for dynamic route {$pattern} does not produce iterable output.");
      }

      preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
      $variables = $matches[1];

      foreach ($items as $item) {
        if (count($variables) === 1 && ! is_array($item)) {
          $params = [$variables[0] => $item];
        } elseif (is_array($item)) {
          $params = array_combine($variables, array_values($item));
        } else {
          $this->error($this->timestampPrefix() . "Dynamic route {$pattern} has invalid parameters.");
          continue;
        }

        $filePath = $pattern;
        foreach ($params as $var => $val) {
          $filePath = str_replace('{' . $var . '}', $val, $filePath);
        }

        $uri = $filePath;
        if (str_ends_with($uri, '/index.html')) {
          $uri = substr($uri, 0, -10);
        } elseif (str_ends_with($uri, '.html')) {
          $uri = substr($uri, 0, -5);
        }

        try {
          $response = app()->handle(Request::create($uri));

          if ($response->getStatusCode() >= 400) {
            $this->error($this->timestampPrefix() . "Route {$uri} failed with status " . $response->getStatusCode());
            continue;
          }

          $outputFilePath = $outputPath . DIRECTORY_SEPARATOR . ltrim($filePath, DIRECTORY_SEPARATOR);
          File::ensureDirectoryExists(dirname($outputFilePath));
          File::put($outputFilePath, (string) $response->getContent());
        } catch (\Throwable $e) {
          $this->error($this->timestampPrefix() . "Exception rendering {$uri}: " . $e->getMessage());
        }
      }
    }


    $this->info($this->timestampPrefix() . "Site copied to: $outputPath");
    $this->info($this->timestampPrefix() . 'Site build complete.');
  }

  /**
   *  Remove the directory if it exists and then create a fresh one.
   *
   * @param dir - The directory path.
   * @returns void No return value.
   */
  protected function deleteAndCreate(string $dir)
  {
    if (File::exists($dir)) {
      File::deleteDirectory($dir);
    }
    File::makeDirectory($dir);
  }
}
