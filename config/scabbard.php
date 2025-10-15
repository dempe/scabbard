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
    | Drafts Directory
    |--------------------------------------------------------------------------
    |
    | Content stored in this directory will be excluded from builds unless the
    | `--drafts` option is provided when running the build or serve commands.
    |
    */
  'drafts_path' => base_path('drafts'),

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
    | Generate multiple pages from a single route pattern.
    | 
    | Each dynamic route defined must map to:
    | output: the name of the file you want the parsed view to be written to.
    | values: Specify both a model and an attribute in Class@attribute notation.
    |
    | During the build, Scabbard will call `pluck` on the model for the specified attribute (e.g., App\Models\Post::pluck('slug');) and generate a new file for each.
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
    | Fingerprint Patterns
    |--------------------------------------------------------------------------
    |
    | Array of file patterns that should be fingerprinted. Patterns use shell-
    | style wildcards (e.g., `**\/*.css`). If empty, no files are fingerprinted.
    |
    */
  'fingerprint' => [
    // 'assets/**', // Fingerprint everying in `assets` directory
  ],

  /*
    |--------------------------------------------------------------------------
    | Not Found Page
    |--------------------------------------------------------------------------
    |
    | The relative path to the 404 page within your generated output
    | directory.  This is only applicable to the server. It specifies
    | the file that will be served whenever a requested resource
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
