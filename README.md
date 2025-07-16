# Scabbard ⚔️

Scabbard is a minimalist static site generator (SSG) built on top of Laravel and Blade. It provides a zero-dependency workflow for generating static sites from your Laravel views.

Features:

- **File watching** – Monitors specified directories for changes
- **Static builds** – Outputs your site to a target directory (`output/` by default)
- **Hot rebuilding** – Rebuilds automagically when changes are detected
- **Local server** – Serves the site locally on the port of your choice

All built in pure PHP.

Just run:

```
php artisan scabbard:serve
```

And you're off to the races!

## Installation

```
composer require dempe/scabbard --dev
php artisan vendor:publish --tag=scabbard-config
```

Scabbard requires a Laravel 10 or 11 installation. Composer will install the
necessary `illuminate/*` packages that match your framework version.

## Configuration

Configs are in `config/scabbard.php`.

### Directory-based Routing

Scabbard assumes directory-based routing so a request like `/blog/my-post`
will load the file `blog/my-post/index.html`.  Define your routes to point at
`index.html` within each directory:

```php
'routes' => [
    '/blog/my-post' => 'blog/my-post/index.html',
],
```

This keeps URLs free of `.html` extensions for cleaner SEO‑friendly links.

### Directories to Watch

These directories trigger a new build if they're updated while you're running the server

### Output Directory

Where to build the static site to (default `./output`).

### Directories to Copy

These are directories that are copied wholesale into your configured output directory.  The default is simple `./public`.

### Routes to Render

This is a mapping of routes (defined in your app's `routes/web.php`) to their filenames in your configured output directory.

### Dynamic Routes

Dynamic routes allow generating multiple pages from a single route pattern. Define `dynamic_routes` in your `scabbard.php` config with a mapping of output file patterns to a closure that returns the placeholder values. For example:

```php
'dynamic_routes' => [
    '/posts/{slug}/index.html' => fn () => App\Models\Post::pluck('slug'),
],
```

The closure should return an iterable of values. When building, each value replaces the `{slug}` placeholder to produce both the request URI and output file path.

### Server Port

The port your server runs on (default `8000`).

### 404 Page

Path to the `404` page relative to your output directory. This is served when
no matching file is found while running `scabbard:serve` (default `/404.html`).

## Additional Commands

### Build

If you don't want to run the server, you can just produce the static output.

This builds the site once and exits:

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

