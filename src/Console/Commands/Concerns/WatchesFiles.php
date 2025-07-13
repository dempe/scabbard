<?php

namespace Scabbard\Console\Commands\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

trait WatchesFiles
{
  protected function hashAllWatchedFiles(): string
  {
    $paths = Config::get('scabbard.watch_dirs');
    $buffer = [];

    foreach ($paths as $path) {
      if (! File::isDirectory($path)) {
        continue;
      }

      $files = File::allFiles($path);
      foreach ($files as $file) {
        $buffer[] = md5_file($file->getRealPath());
      }
    }

    sort($buffer);  // Sort file paths before hashing to avoid nondeterministic hash ordering
    return md5(implode('', $buffer));
  }
}
