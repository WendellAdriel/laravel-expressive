---
name: expressive-development
description: >
  Integrate and use `wendelladriel/laravel-expressive` in Laravel applications.
  Use this when installing Expressive, configuring class lookup, creating typed
  Expressive objects, converting Eloquent models or collections, generating or
  syncing Expressive classes, serializing objects, or persisting Expressive
  objects back through Eloquent.
license: MIT
metadata:
  author: Wendell Adriel
---

# Expressive Development

Use this skill when a Laravel application needs a typed DTO/Data Mapper Layer on top of Eloquent using Expressive.

## Primary Goal

- apply Expressive through its public Laravel integration points only
- keep Eloquent responsible for querying, relationship semantics, model events, mass assignment, and database writes
- prefer the smallest class, config, command, or mapping change that gives the application typed public properties

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project and identify its Laravel/PHP versions before recommending installation
- inspect the target Eloquent model, casts, accessors, relationships, `$fillable`/`$guarded`, `$hidden`, and `$visible`
- check whether the application already uses DTOs, resources, actions, or domain objects so Expressive fits the existing boundary instead of replacing unrelated patterns
- decide whether the Expressive class should mirror the model shape, expose a smaller application boundary, or be generated and then edited

### 2. Install and configure only what is needed

- install with `composer require wendelladriel/laravel-expressive`; Laravel auto-discovers the service provider
- publish with `php artisan vendor:publish --tag="expressive"` only when the app needs `config/expressive.php` or `stubs/expressive.stub`
- publish only config with `php artisan vendor:publish --tag="expressive-config"` when changing namespace, suffix, diagnostics, serialization casing, or generator defaults
- publish only the generator stub with `php artisan vendor:publish --tag="expressive-stubs"` when generated class formatting must be customized
- set `expressive.namespace` and `expressive.suffix` before relying on implicit lookup or running `make:expressive`
- set `expressive.diagnostics.throw_on_unfillable` to `true` during adoption when ignored unfillable properties should fail fast
- set `expressive.serialization.case` to `snake` only when serialized keys should be snake case globally

### 3. Define mappings deliberately

- add `WendellAdriel\Expressive\Concerns\IsExpressive` only to Eloquent models that should expose `$model->expressive()`
- create Expressive classes under the configured namespace, or run `php artisan make:expressive User --model="App\Models\User"`
- Expressive classes must extend `WendellAdriel\Expressive\Expressive` and expose mapped data as public typed properties
- use `#[Model(User::class)]` on an Expressive class when the model cannot be inferred from namespace and suffix
- use `#[Expressive(UserData::class)]` on a model when it should resolve a specific Expressive class
- use `#[Map('remember_token')]` when a property maps to a non-default Eloquent key; unmapped non-relationship properties default to snake-case keys
- use `#[Relationship]` only for relationship properties, and `#[Virtual]` only for accessor/appended values
- keep relationship and virtual properties nullable because unloaded relationships and unrequested virtual accessors map to `null`
- type single relationships as `?RelatedExpressive`; type many relationships as `?Illuminate\Support\Collection` with `@var Collection<int, RelatedExpressive>|null`
- do not put mapping attributes on private or protected properties; Expressive only maps public non-static properties

### 4. Convert from Eloquent at explicit boundaries

- use `$model->expressive(relationships: [...], attributes: [...])` for a single opted-in model
- use `$eloquentCollection->expressive(relationships: [...], attributes: [...])` to convert an Eloquent collection without mutating the original collection
- use `$builder->expressive(relationships: [...], attributes: [...])` when it is acceptable to execute `get()` immediately and return an in-memory `Illuminate\Support\Collection`
- use `$builder->expressiveChunk($count, $callback, relationships: [...], attributes: [...])` for large result sets that can be processed with Laravel `chunk()`; the count must be at least `1`
- pass relationship names to `relationships` instead of relying on lazy loading; Expressive uses `loadMissing()` and leaves unloaded relationships as `null`
- pass accessor keys to `attributes` for virtual properties; Expressive calls Eloquent `append()` and leaves unrequested virtual values as `null`
- use API resources or transformers when the response contract differs from the Expressive object shape

### 5. Generate and sync classes safely

- use `php artisan make:expressive User --model="App\Models\User"` to generate a starting class from model columns, casts, relationships, and detected accessors
- use `--namespace`, `--suffix`, `--attributes`, `--without-attributes`, `--relationships`, `--without-relationships`, `--include-hidden`, `--exclude-hidden`, `--hint-morph-map`, `--dry-run`, and `--force` only for the current generation need
- remember hidden attributes are generated by default, but serialization still respects the mapped model's `hidden` and `visible` rules
- use `php artisan expressive:sync User --model="App\Models\User"` to detect drift; `--model` is required
- add `--write` only for generated classes that are safe to rewrite; if the class has custom methods or intentional shape changes, update it manually
- treat sync output as review input because missing, stale, or type differences may be intentional application design

### 6. Serialize or persist intentionally

- use `$expressive->toArray()`, `$expressive->toJson()`, or `json_encode($expressive)` for direct serialization of initialized public properties
- expect nested Expressive objects and collections to serialize recursively
- expect uninitialized typed properties to be skipped; nullable properties with default `null` are initialized and may appear unless hidden or invisible
- use `$expressive->model()` to create an unsaved Eloquent model filled only with mapped, fillable, non-virtual, non-relationship properties
- use `$expressive->save()` to save the root model and supported direct relationships: `BelongsTo`, `HasOne`, `MorphOne`, `HasMany`, and `MorphMany`
- keep `BelongsToMany`, morph-to-many, through relationships, pivot semantics, and deletion in explicit Eloquent application code

## Rules, References, and Templates

Read before executing:

- official documentation: `https://wendelladriel.github.io/laravel-expressive`
- installed package config after publishing: `config/expressive.php`
- installed package stub after publishing: `stubs/expressive.stub`
- target application models under `app/Models/`
- target application Expressive classes under the configured `expressive.namespace`
- Artisan help in the consuming app: `php artisan help make:expressive` and `php artisan help expressive:sync`

## Examples

- For a read boundary, add `IsExpressive` to `App\Models\User`, run `php artisan make:expressive User --model="App\Models\User"`, keep `#[Relationship] public ?Collection $posts = null;`, then return `User::query()->where('active', true)->expressive(relationships: ['posts'])` from an application service.
- For a custom boundary, create `App\Data\PublicUser extends Expressive`, add `#[Model(App\Models\User::class)]`, include only public properties the service needs, and call `$user->expressive(attributes: ['display_name'])` when a nullable `#[Virtual]` property should be populated.
- For write input, hydrate an Expressive object from validated data, call `$object->model()` when application code should decide how to save, or call `$object->save()` only when root fillable attributes and supported direct relationships should be persisted immediately.
- For CI drift checks, run `php artisan expressive:sync User --model="App\Models\User"` and review differences before choosing either a manual class edit or a safe `--write` rewrite.

## Anti-patterns

- do not use Expressive as a replacement for Eloquent querying, resources, validation, authorization, model events, or relationship APIs
- do not make relationship or virtual properties non-nullable
- do not rely on lazy loading during conversion; request relationships explicitly
- do not expect virtual accessors to populate unless passed through `attributes`
- do not expect `model()` to save records or persist relationships
- do not expect `save()` to handle many-to-many, morph-to-many, through relationships, pivot sync, detach, attach, or deletion
- do not use `make:expressive --force` or `expressive:sync --write` over custom classes without reviewing intentional user edits
- do not document private package classes as application-facing APIs
