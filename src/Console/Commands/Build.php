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
    foreach ($dynamicRoutes as $routePattern => $config) {
      $outputPattern = $config['output'];
      $callback = $this->callbackFromString($config['values']);

      if (! is_callable($callback) || ! is_string($outputPattern)) {
        $this->error($this->timestampPrefix() . "Dynamic route {$routePattern} is not callable or missing output path.");
        continue;
      }

      $items = $callback();
      if (! is_iterable($items)) {
        $this->error($this->timestampPrefix() . "Callback for dynamic route {$routePattern} does not produce iterable output.");
        continue;
      }

      preg_match_all('/\{([^}]+)\}/', $routePattern, $matches);
      $variables = $matches[1];

      foreach ($items as $item) {
        if (count($variables) === 1 && ! is_array($item)) {
          $params = [$variables[0] => $item];
        } elseif (is_array($item)) {
          $params = array_combine($variables, array_values($item));
        } else {
          $this->error($this->timestampPrefix() . "Dynamic route {$routePattern} has invalid parameters.");
          continue;
        }

        $uri = $routePattern;
        $filePath = $outputPattern;
        foreach ($params as $var => $val) {
          $uri = str_replace('{' . $var . '}', $val, $uri);
          $filePath = str_replace('{' . $var . '}', $val, $filePath);
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

    $this->fingerprintFiles($outputPath);

    $this->info($this->timestampPrefix() . "Site copied to: $outputPath");
    $this->info($this->timestampPrefix() . 'Site build complete.');
  }

  /**
   * Create a values callback from a string specification.
   *
   * @param string $spec Class and attribute in `Class@attribute` form.
   * @return callable|null
   */
  protected function callbackFromString(string $spec): ?callable
  {
    if (! str_contains($spec, '@')) {
      $this->error($this->timestampPrefix() . "No attribute specified in callback {$spec}. Check your dynamic routes config.");
      return null;
    }

    [$class, $attribute] = explode('@', $spec, 2);

    if ($class === '') {
      $this->error($this->timestampPrefix() . "No class found in callback {$spec}. Check your dynamic routes config.");
      return null;
    }

    if ($attribute === '') {
      $this->error($this->timestampPrefix() . "No attribute found in callback {$spec}. Check your dynamic routes config.");
      return null;
    }

    if (! class_exists($class)) {
      $this->error($this->timestampPrefix() . "Class {$class} does not exist. Callback = {$spec}. Check your dynamic routes  config.");
      return null;
    }

    return function () use ($class, $attribute) {
      if (is_callable([$class, 'pluck'])) {
        return $class::pluck($attribute);
      }

      $this->error($this->timestampPrefix() . "Method `pluck` not found on model {$class}. Check your dynamic routes config.");
      return [];
    };
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

  /**
   * Fingerprint configured files in the output directory and update HTML references.
   *
   * @param string $outputPath
   */
  protected function fingerprintFiles(string $outputPath): void
  {
    $patterns = Config::get('scabbard.fingerprint', []);
    if ($patterns === [] || $patterns === null || ! is_array($patterns)) {
      return;
    }

    $this->info($this->timestampPrefix() . 'Fingerprint patterns: ' . json_encode($patterns));

    $fingerprinted = [];

    foreach (File::allFiles($outputPath) as $file) {
      $relative = ltrim(str_replace($outputPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
      $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

      foreach ($patterns as $pattern) {
        $regex = $this->patternToRegex($pattern);
        if (preg_match($regex, $relative) === 1) {
          $hash = substr((string) sha1_file($file->getPathname()), 0, 8);
          $info = pathinfo($file->getPathname());
          $newName = $info['filename'] . '.' . $hash . (isset($info['extension']) ? '.' . $info['extension'] : '');
          $newPath = ($info['dirname'] ?? dirname($file->getPathname())) . DIRECTORY_SEPARATOR . $newName;
          File::move($file->getPathname(), $newPath);
          $fingerprinted[$relative] = str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($outputPath, '', $newPath), DIRECTORY_SEPARATOR));
          $fingerprinted['/' . $relative] = '/' . $fingerprinted[$relative];
          $fingerprinted['./' . $relative] = './' . $fingerprinted[$relative];
          break;
        }
      }
    }

    if ($fingerprinted === []) {
      $this->info($this->timestampPrefix() . 'No files matched fingerprint patterns. Check your config.');
      return;
    }

    foreach (File::allFiles($outputPath) as $file) {
      if ($file->getExtension() !== 'html') {
        continue;
      }

      $contents = File::get($file->getPathname());
      $contents = (string) preg_replace_callback('/(href|src)=(["\'])(.*?)\2/', function ($m) use ($fingerprinted) {
        $value = $m[3];
        $parts = parse_url($value);
        $path = $parts['path'] ?? $value;
        if (! array_key_exists($path, $fingerprinted)) {
          return $m[0];
        }
        $new = $fingerprinted[$path];
        if (isset($parts['query'])) {
          $new .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
          $new .= '#' . $parts['fragment'];
        }
        return $m[1] . '=' . $m[2] . $new . $m[2];
      }, $contents);
      File::put($file->getPathname(), (string) $contents);
    }
  }

  /**
   * Convert a glob-style fingerprint pattern into a regex.
   * Supports ** for any directory levels.
   */
  protected function patternToRegex(string $pattern): string
  {
    $pattern = str_replace(DIRECTORY_SEPARATOR, '/', $pattern);
    $regex = preg_quote($pattern, '#');
    $regex = str_replace('\*\*', '.*', $regex);
    $regex = str_replace('\*', '[^/]*', $regex);
    $regex = str_replace('\?', '.', $regex);

    return '#^' . $regex . '$#';
  }
}
