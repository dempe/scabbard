# Scabbard ⚔️

Scabbard is a minimalistic, deterministic (i.e., config-based) static site generator (SSG) built on top of Laravel and Blade. It provides a zero-dependency workflow for generating static sites from your Laravel views.

Features:

- **File watching** 👀  – Monitors specified directories for changes
- **Static builds** 🔨  – Outputs your site to a target directory (`output/` by default)
- **Hot rebuilding** 🔥 – Rebuilds automagically when changes are detected
- **Local server** 🧑🏽‍💻   – Serves the site locally on the port of your choice

All built in pure PHP.

Just run:

```
php artisan scabbard:serve
```

And you're off to the races! 🏇🏻

## Installation

```
composer require dempe/scabbard --dev
php artisan vendor:publish --tag=scabbard-config
```

## Configuration

Configs are in `config/scabbard.php`.

### Routes

Map of routes to output filenames.

Each route (i.e., each key in the array) must be declared in a Laravel routes file. This is because Scabbard calls Laravel for each route allowing you to use controllers or other additional processing.

Note: these are *static* routes (dynamic routes are handled separately).

Example:

```php
'routes' => [
    '/blog/my-post' => 'blog/my-post/index.html',
],
```

### Directories to Watch

These directories trigger a new build if they're updated while you're running the server.

Default: `public`, `app`, `resources`.

### Output Directory

Where to build the static site.

Default: `./output`.

### Directories to Copy

These are directories that are copied wholesale into your configured output directory.

Default: `./public`.

### Dynamic Routes

Dynamic routes allow generating multiple pages from a single route pattern.
Define `dynamic_routes` in  `config/scabbard.php` with a mapping of the
route pattern to an array containing the output path pattern and a callback that
returns the values for the placeholders.

Example:

```php
'dynamic_routes' => [
    '/posts/{slug}' => [
        'output' => '/posts/{slug}/index.html',
        'values' => 'App\\Models\\Post@slug',
    ],
],
```

Specify the *model* class and *attribute* to `pluck` using the `Class@attribute`
notation. During the build, Scabbard will `pluck` the attribute values (e.g., `App\Models\Post::pluck('slug');`) and use
each one to replace the `{slug}` placeholder, producing both the request URI and
the output file path.

### Server Port

The port your server runs on. 

Default: `8000`.

### 404 Page

The 404 page that `scabbard:serve` will use. The path should be relative to your output directory.

Default:  `/404.html`.

## Additional Commands

### Build

This builds the site once and exits (useful for CICD):

```
php artisan scabbard:build
```

Use the `--watch` flag to enable hot rebuilds without running a server:

```
php artisan scabbard:build --watch
```


## Static Analysis

This project uses [PHPStan](https://phpstan.org/) for static code analysis. Run:

```
composer phpstan
```

to analyze the codebase.

## Notes

- Works with Laravel 10 and above.
- Scabbard does not require JavaScript, npm, or Node.js.
- You can use any Blade features you normally would in Laravel.

## Roadmap

- [ ] Markdown parsing
- [ ] Frontmatter parsing
- [ ] Syntax highlighting
- [ ] Deployment

