# Persistence

- [Introduction](#introduction)
- [Converting to a model](#converting-to-a-model)
- [Saving an Expressive object](#saving-an-expressive-object)
- [Fillable attributes](#fillable-attributes)
- [In-memory relationships](#in-memory-relationships)
- [Supported relationship persistence](#supported-relationship-persistence)
- [Unsupported persistence](#unsupported-persistence)

## Introduction

Expressive can convert typed objects back into Eloquent models. This is useful when application code builds or modifies an Expressive object and then needs to hand persistence back to Eloquent.

Eloquent still owns database writes. Expressive does not provide deletion APIs, attach or sync APIs, or a replacement for model events and relationship methods.

## Converting to a model

Use `model()` to create an unsaved Eloquent model from an Expressive object:

```php
$expressive = new App\Expressive\User([
    'name' => 'Wendell',
    'email' => 'wendell@example.com',
]);

$model = $expressive->model();
```

The returned model is filled with mapped, fillable, non-virtual, non-relationship properties. Virtual properties are ignored because they do not represent persisted attributes.

## Saving an Expressive object

Use `save()` when you want Expressive to create a model, save it, and persist supported direct relationships:

```php
$saved = (new App\Expressive\User([
    'name' => 'Wendell',
    'email' => 'wendell@example.com',
]))->save();
```

The method returns the fresh Eloquent model when Laravel can reload it, otherwise it returns the saved model instance.

## Fillable attributes

Expressive respects Eloquent mass-assignment rules. If a mapped property points to an attribute the model will not fill, Expressive ignores that property by default:

```php
$expressive = new App\Expressive\User([
    'id' => 99,
    'name' => 'Wendell',
]);

$model = $expressive->model();
```

If `id` is not fillable, the model's key is not set by Expressive. Enable `expressive.diagnostics.throw_on_unfillable` when you want those ignored properties to fail fast during adoption.

## In-memory relationships

When `model()` receives relationship properties, Expressive converts them to Eloquent models and attaches them in memory with `setRelation()`:

```php
$expressive = new App\Expressive\User([
    'name' => 'Wendell',
    'address' => new App\Expressive\Address([
        'street' => 'Main',
        'city' => 'Lisbon',
    ]),
]);

$model = $expressive->model();

$model->relationLoaded('address'); // true
```

Calling `model()` does not persist the related records. It only prepares the model instance and its loaded relationship values.

## Supported relationship persistence

Calling `save()` persists the root model and then writes supported direct relationships:

| Relationship | Persistence behavior |
| --- | --- |
| `BelongsTo` | Saves the related model, associates it, and saves the root model again. |
| `HasOne` | Saves the related model through the relationship. |
| `MorphOne` | Saves the related model through the relationship. |
| `HasMany` | Saves all related models through the relationship. |
| `MorphMany` | Saves all related models through the relationship. |

Single relationship values may be Expressive objects or Eloquent models. Many relationship values may be arrays or Laravel collections containing Expressive objects or Eloquent models.

## Unsupported persistence

The following relationship types are not persisted by `Expressive::save()`:

| Relationship | Why it is unsupported |
| --- | --- |
| `BelongsToMany` | Requires explicit attach, sync, or detach semantics. |
| `MorphToMany` / `morphedByMany()` | Requires explicit attach, sync, or detach semantics. |
| `HasOneThrough` | Writes through an intermediate model rather than a direct target. |
| `HasManyThrough` | Writes through an intermediate model rather than a direct target. |
| Custom relation classes | Expressive cannot know the correct write semantics. |

Unsupported persistence does not prevent model-to-Expressive conversion. Loaded relationships can still be converted into Expressive properties when the class exposes them. Only writing those relationships through `save()` is unsupported.

Deletion is not provided on Expressive objects. Convert to an Eloquent model and call Eloquent deletion methods explicitly when needed.
