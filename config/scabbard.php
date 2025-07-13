<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Directories to Watch
    |--------------------------------------------------------------------------
    |
    | These directories will be monitored for changes when running
    | `php artisan site:build --watch`. Any change triggers a rebuild.
    |
    */
    'watch_dirs' => [
        base_path('resources'),
        base_path('app'),
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
    | Directories to Copy
    |--------------------------------------------------------------------------
    |
    | These directories will be copied recursively to the output directory
    | before rendering the configured views.
    |
    */
    'copy_dirs' => [
        base_path('public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Views to Render
    |--------------------------------------------------------------------------
    |
    | Mapping of output file names to Blade views that should be rendered
    | and saved during the build process.
    |
    */
    'views' => [
        'index.html' => 'home',
        'athena.html' => 'athena',
        '404.html'   => '404',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes to Render
    |--------------------------------------------------------------------------
    |
    | Mapping of URIs to output file names. Each route will be processed through
    | Laravel's routing layer and saved to the specified file.
    |
    */
    'routes' => [
        '/'        => 'index.html',
        '/athena'  => 'athena.html',
        '/404'     => '404.html',
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Development Server Port
    |--------------------------------------------------------------------------
    |
    | The `site:serve` Artisan command will use this port when starting the
    | built‑in PHP server to preview the generated site locally.
    |
    */
    'serve_port' => 8000,
];
