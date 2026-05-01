# Installation

Install Expressive into a Laravel application with Composer:

```bash
composer require wendelladriel/laravel-expressive
```

Publish the package resources with the umbrella tag:

```bash
php artisan vendor:publish --tag="expressive"
```

If the package ships migrations, run them after publishing:

```bash
php artisan migrate
```

Update this page with any configuration, environment variables, or install checks that Expressive requires.
