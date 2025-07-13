<?php

namespace Scabbard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;


class BuildSite extends Command
{
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
    if ($this->option('watch')) {
      Artisan::call('scabbard:watch', [], $this->output);
      return;
    }

    $this->buildSite();
  }

  /**
   *  Generate the static site by clearing views, copying configured directories,
   *  rendering any configured routes, and writing HTML files.
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

    $views = Config::get('scabbard.views', []);
    foreach ($views as $filename => $view) {
      try {
        File::put("$outputPath/{$filename}", view($view)->render());
      } catch (\InvalidArgumentException $e) {
        // Skip views that cannot be rendered
        continue;
      }
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
