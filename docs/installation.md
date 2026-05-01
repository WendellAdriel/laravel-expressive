# Installation

- [Requirements](#requirements)
- [Installing Expressive](#installing-expressive)
- [Publishing package files](#publishing-package-files)
- [Next steps](#next-steps)

## Requirements

Expressive requires PHP 8.3 or higher and supports Laravel 12 and 13.

## Installing Expressive

You may install Expressive into a Laravel application with Composer:

```shell
composer require wendelladriel/laravel-expressive
```

Laravel discovers the package service provider automatically. The service provider registers the configuration file, Artisan commands, and Eloquent macros used by the package.

## Publishing package files

You may publish all Expressive resources with the package's umbrella tag:

```shell
php artisan vendor:publish --tag="expressive"
```

This publishes the configuration file to `config/expressive.php` and the generator stub to `stubs/expressive.stub`.

If you only need one resource, you may publish the config file or stub separately:

```shell
php artisan vendor:publish --tag="expressive-config"
php artisan vendor:publish --tag="expressive-stubs"
```

Publishing the stub is optional. Expressive uses its bundled stub unless `stubs/expressive.stub` exists in your application.

## Next steps

After installation, review the [configuration options](configuration.md), add the `IsExpressive` trait to a model, and define or [generate an Expressive class](generator.md) for that model.
