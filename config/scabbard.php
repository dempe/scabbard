<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Directories to Watch
    |--------------------------------------------------------------------------
    |
    | These directories will be monitored for changes when running
    | `php artisan scabbard:serve`. Any change triggers a rebuild.
    |
    */
  'watch_dirs' => [
    base_path('resources'),
    base_path('app'),
    base_path('public'),
  ],

  /*
    |--------------------------------------------------------------------------
    | Directories to Copy
    |--------------------------------------------------------------------------
    |
  | These directories will be copied recursively to the output directory
  | before rendering the configured routes.
    |
    */
  'copy_dirs' => [
    base_path('public'),
  ],

  /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | The location where the generated static files will be written.
    |
    */
  'output_path' => base_path('output'),

  /*
    |--------------------------------------------------------------------------
    | Routes to Render
    |--------------------------------------------------------------------------
    |
    | Mapping of URIs to output file names. Routes should be defined in your app's `routes/web.php`.
    |
    */
  'routes' => [
    '/'        => 'index.html',
    '/404'     => '404.html',
  ],

  /*
    |--------------------------------------------------------------------------
    | Local Development Server Port
    |--------------------------------------------------------------------------
    |
    | The `scabbard:serve` Artisan command will use this port when starting the
    | built‑in PHP server to preview the generated site locally.
    |
    */
  'serve_port' => 8000,
];
