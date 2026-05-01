# Relationships

- [Introduction](#introduction)
- [Defining relationship properties](#defining-relationship-properties)
- [Loading relationships](#loading-relationships)
- [Relationship conversion](#relationship-conversion)
- [Polymorphic relationships](#polymorphic-relationships)
- [Relationship persistence](#relationship-persistence)

## Introduction

Expressive can convert loaded Eloquent relationships into nested Expressive objects. Relationship conversion is opt-in at the property level with the `#[Relationship]` attribute and opt-in at runtime by loading or requesting the relationship.

Expressive does not trigger lazy loading for unloaded relationships. If a relationship was not loaded, the Expressive property is `null`.

## Defining relationship properties

Single related models may be typed as the related Expressive class:

```php
use WendellAdriel\Expressive\Attributes\Relationship;

#[Relationship]
public ?Address $address = null;
```

Relationships that return many models should use `Illuminate\Support\Collection` and a PHPDoc item type:

```php
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Attributes\Relationship;

/** @var Collection<int, Post>|null */
#[Relationship]
public ?Collection $posts = null;
```

Relationship properties must be nullable because Expressive uses `null` when the relationship is unavailable.

## Loading relationships

Pass relationships to `expressive()` to load them before conversion:

```php
$user = User::query()->findOrFail(1)->expressive(
    relationships: ['address', 'posts'],
);
```

You may pass a single relationship name or an array of relationship names:

```php
$user = User::query()->findOrFail(1)->expressive(relationships: 'posts');
```

For collections and builders, requested relationships are loaded before each model is converted:

```php
$users = User::query()->get()->expressive(relationships: ['posts']);
```

## Relationship conversion

Loaded single-model relationships become nested Expressive objects:

```php
$user->address; // App\Expressive\Address|null
```

Loaded many-model relationships become collections of Expressive objects:

```php
$user->posts; // Illuminate\Support\Collection<int, App\Expressive\Post>|null
```

Nested conversion uses the same model-to-Expressive resolution rules as root conversion. The related model may use an explicit `#[Expressive]` attribute or the configured namespace and suffix.

## Polymorphic relationships

The generator can create broad PHPDoc hints for `MorphTo` relationships because the related model is not known from the relationship definition alone:

```php
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Attributes\Relationship;

/** @var Expressive<Model>|null */
#[Relationship]
public ?Expressive $imageable = null;
```

If your application has a morph map, the generator may add more specific PHPDoc hints when `--hint-morph-map` is enabled and the inverse relationship can be resolved confidently.

Runtime conversion does not change because of morph-map hints. Expressive converts the actual loaded related model.

## Relationship persistence

Expressive can persist direct relationships when you call `save()` on the Expressive object. Supported relationship writes are documented in the [persistence guide](persistence.md#supported-relationship-persistence).

Many-to-many and through relationship persistence is intentionally unsupported. Convert to Eloquent and use explicit application code for attach, sync, detach, or through-model workflows.
