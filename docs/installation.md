# Installation

Install Expressive into a Laravel application with Composer:

```bash
composer require wendelladriel/laravel-expressive
```

Publish the package resources with the umbrella tag:

```bash
php artisan vendor:publish --tag="expressive"
```

You may also publish resources separately:

```bash
php artisan vendor:publish --tag="expressive-config"
php artisan vendor:publish --tag="expressive-stubs"
```

The config file contains the implicit lookup namespace and optional class suffix:

```php
return [
    'namespace' => 'App\\Expressive',
    'suffix' => '',
];
```
