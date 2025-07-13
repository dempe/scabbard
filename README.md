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

## Configuration

Configs are in `config/scabbard.php`.

## Additional Commands

### Build

If you don't want to run the server, you can just produce the static output:

```
php artisan scabbard:build
```

Or if you don't want to run the server *and* want to continually build the site with hot rebuilds:

```
php artisan scabbard:build --watch
```

### Watch

```
php artisan scabbard:watch
```

This is just an alias for `php artisan scabbard:build --watch`

