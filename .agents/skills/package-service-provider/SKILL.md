---
name: package-service-provider
description: "Use this skill when changing a Laravel package service provider: config merges, routes, views, translations, publishing, migrations, assets, Blade components, commands, console-only behavior, or package metadata."
license: MIT
metadata:
  author: laravel
---

# Package Service Provider

## Primary Goal

Wire package capabilities through explicit Laravel service provider code without defaulting to `spatie/laravel-package-tools`.

## Workflow

1. Inspect the package service provider and keep its existing method split. If the package uses names like `registerResources`, `registerPublishing`, `registerBladeComponents`, `registerConsole`, or `registerAboutMetadata`, add new behavior to the matching concern.
2. Put container bindings and `mergeConfigFrom` calls in `register()` when the host app must be able to override configuration.
3. Put resource loading in boot-time methods with Laravel-native APIs such as `loadRoutesFrom`, `loadViewsFrom`, and `loadTranslationsFrom`.
4. Guard console-only publishing and command registration with `runningInConsole()` before calling `publishes`, `publishesMigrations`, or `commands`.
5. Add tests for the observable package behavior: merged config, loaded routes, publish tags, command registration, or about metadata.

## References

- `src/*ServiceProvider.php`
- `config/*.php`
- `routes/*.php`
- `resources/views/`
- `lang/`
- `database/migrations/`
- `src/Console/Commands/`

## Examples

- Wire a new publish tag by adding a `publishes` map inside the existing console-guarded publishing method and naming the tag with `expressive-*`.
- Add a command by creating the command class, importing it in the provider, and adding it to the `commands` array inside the `runningInConsole()` guard.

## Anti-Patterns

- Loading host app state too early during provider registration.
- Calling `env()` outside config files; avoid `env() outside config` by using config values after `mergeConfigFrom` instead.
- Registering web-only concerns unconditionally when the package can run in console contexts.
- Replacing explicit provider methods with `spatie/laravel-package-tools` by default.
