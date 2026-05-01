---
name: expressive-development
description: >
  Configure and apply the Expressive package in Laravel applications.
license: MIT
metadata:
  author: Wendell Adriel
---

# Expressive

Use this skill when a Laravel application needs to integrate the Expressive package.

## Primary Goal

- apply the `wendelladriel/laravel-expressive` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Apply the package's public API

- install `wendelladriel/laravel-expressive` and publish package resources with `php artisan vendor:publish --tag="expressive"` when the app needs custom defaults or stub customization
- set `expressive.namespace` and `expressive.suffix` before relying on implicit class lookup or the generator
- add `WendellAdriel\Expressive\Concerns\IsExpressive` to Eloquent models that should convert to typed objects
- create Expressive classes under the configured namespace, or run `php artisan make:expressive User --model="App\Models\User"`
- use `#[Map]` for custom attribute keys, `#[Relationship]` for relationship properties, `#[Virtual]` for accessors/appended values, and `#[Model]` for explicit Expressive-to-model mapping
- keep relationship and virtual properties nullable because unloaded relationships and unavailable accessors map to `null`
- type single loaded relationships as the related Expressive class and document many relationships as `Collection<int, RelatedExpressive>|null`
- use `$model->expressive()`, `$collection->expressive()`, or `$builder->expressive()` to convert from Eloquent
- use `$expressive->model()` for in-memory Eloquent conversion and `$expressive->save()` for explicit persistence
- keep deletion explicit through Eloquent, for example `$expressive->model()->delete()`

## Rules, References, and Templates

Read before executing:

- `config/expressive.php`
- `src/Concerns/IsExpressive.php`
- `src/Expressive.php`
- `src/Attributes/`
- `src/Console/Commands/MakeExpressiveCommand.php`

## Examples

- generate `App\Expressive\User` from `App\Models\User`, add the `IsExpressive` trait to the model, then return `User::query()->expressive(relationships: ['posts'])` from an application service
- create a custom typed object with `#[Model(User::class)]`, hydrate it from validated input, call `$object->save()`, and let Eloquent persist fillable root attributes and supported relationships

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not make relationship or virtual properties non-nullable
- do not expect `model()` to save or sync database records
- do not use Expressive deletion APIs; v1 intentionally leaves deletion to Eloquent
