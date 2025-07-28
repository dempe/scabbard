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
    '/404'     => '404/index.html',
  ],

  /*
    |--------------------------------------------------------------------------
    | Dynamic Routes
    |--------------------------------------------------------------------------
    |
    | Mapping of route patterns containing placeholders to an array with an
    | `output` path and a `values` callback that returns the values for those
    | placeholders. The callback should return an iterable. When a single
    | placeholder is used, scalar values are accepted. For multiple
    | placeholders, return associative arrays keyed by placeholder name.
    |
    */
  'dynamic_routes' => [
    // '/posts/{slug}' => [
    //   'output' => '/posts/{slug}/index.html',
    //   'values' => 'App\\Models\\Post@slug',
    // ],
  ],

  /*
    |--------------------------------------------------------------------------
    | Not Found Page
    |--------------------------------------------------------------------------
    |
    | The relative path to the 404 page within your generated output
    | directory.  This file will be served whenever a requested resource
    | cannot be found while running `scabbard:serve`.
    |
    */
  'not_found_page' => '/404.html',

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
