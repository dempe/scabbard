<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Scabbard\Console\Commands\Concerns\WatchesFiles;

class Build extends Command
{
  use WatchesFiles;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'scabbard:build {--watch} {--once}';

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
    if ($this->option('watch')) {
      $this->info('[' . now()->format('H:i:s') . '] ' . 'Watching for changes...');

      $lastHash = null;

      do {
        $currentHash = $this->hashAllWatchedFiles();

        if ($lastHash !== $currentHash) {
          $lastHash = $currentHash;
          $this->info('[' . now()->format('H:i:s') . '] ' . 'Rebuilding...');
          $this->buildSite();
        }

        $this->trap(SIGINT, function () {
          $this->info('[' . now()->format('H:i:s') . '] ' . 'Watcher interrupted. Exiting.');
          exit;
        });

        usleep(500000);
      } while (! $this->option('once'));

      return;
    }

    $this->buildSite();
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
      $response = app()->handle(Request::create($uri));
      File::put("$outputPath/{$filename}", $response->getContent());
    }


    $this->info('[' . now()->format('H:i:s') . '] ' . "Site copied to: $outputPath");
    $this->info('[' . now()->format('H:i:s') . '] ' . 'Site build complete.');
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
