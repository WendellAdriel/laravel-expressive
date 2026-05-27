# Configuration

- [Introduction](#introduction)
- [Expressive namespace](#expressive-namespace)
- [Class suffixes](#class-suffixes)
- [Diagnostics](#diagnostics)
- [Serialization casing](#serialization-casing)
- [Generator defaults](#generator-defaults)

## Introduction

Expressive stores its application-level options in `config/expressive.php`. You may publish this file with the `expressive-config` tag:

```shell
php artisan vendor:publish --tag="expressive-config"
```

The default configuration is intentionally small. It controls implicit class lookup, conversion diagnostics, serialization key casing, and generator defaults.

## Expressive namespace

The `namespace` option defines where Expressive classes are generated and where implicit mapping looks for them:

```php
'namespace' => 'App\\Expressive',
```

With the default value, `App\Models\User` maps to `App\Expressive\User`. If your application keeps data objects in another namespace, update this value before generating classes:

```php
'namespace' => 'App\\Data',
```

You may still override the mapping for a single model or Expressive class with attributes. Use `#[Expressive]` on a model when the model should resolve a specific Expressive class, or use `#[Model]` on an Expressive class when it should map back to a specific Eloquent model.

## Class suffixes

The `suffix` option appends a suffix to generated class names and implicit lookups:

```php
'suffix' => 'Expressive',
```

With this configuration, `App\Models\User` maps to `App\Expressive\UserExpressive`. The same suffix is used when resolving an Expressive object back to its model.

Keep the suffix empty when your Expressive classes should use the same base name as the mapped Eloquent model:

```php
'suffix' => '',
```

## Diagnostics

Expressive uses Eloquent's fillable rules when converting an Expressive object back into a model. By default, mapped properties that are not fillable are ignored in the same conservative spirit as normal mass-assignment behavior:

```php
'diagnostics' => [
    'throw_on_unfillable' => false,
],
```

You may enable diagnostics while adopting Expressive in an existing application:

```php
'diagnostics' => [
    'throw_on_unfillable' => true,
],
```

When enabled, Expressive throws before calling `fill()` if a non-virtual, non-relationship property maps to an attribute the model will not fill. Virtual properties and relationship properties are excluded from this check.

## Serialization casing

Expressive serializes public property names by default:

```php
'serialization' => [
    'case' => 'preserve',
],
```

For example, an `emailVerifiedAt` property serializes as `emailVerifiedAt`. If your API responses should use snake-case keys, set the option to `snake`:

```php
'serialization' => [
    'case' => 'snake',
],
```

Visibility filtering still uses the mapped Eloquent key. A property mapped to `remember_token` is filtered by the model's `hidden` or `visible` rules for `remember_token`, even when the serialized property name would be `rememberKey`.

## Generator defaults

The `generator` options define the defaults used by `make:expressive` when a matching CLI flag is not passed:

```php
'generator' => [
    'with_attributes' => true,
    'with_relationships' => true,
    'exclude_hidden' => false,
    'hint_morph_map' => false,
],
```

Explicit command options always win for a single run. For example, `--without-relationships` disables relationship generation even when `with_relationships` is `true` in the config file.
