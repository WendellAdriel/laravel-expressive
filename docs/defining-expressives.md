# Defining Expressive classes

- [Introduction](#introduction)
- [Creating a class](#creating-a-class)
- [Mapping models explicitly](#mapping-models-explicitly)
- [Mapping attribute names](#mapping-attribute-names)
- [Virtual properties](#virtual-properties)
- [Nullable optional properties](#nullable-optional-properties)

## Introduction

An Expressive class is a typed PHP object that describes the data your application wants to work with. It extends `WendellAdriel\Expressive\Expressive` and exposes public typed properties.

Expressive reads those public properties through reflection. Private and protected properties are ignored, and mapping attributes such as `#[Map]`, `#[Relationship]`, and `#[Virtual]` must be placed on public properties.

## Creating a class

A minimal Expressive class may rely on implicit model mapping:

```php
namespace App\Expressive;

use App\Models\User as UserModel;
use WendellAdriel\Expressive\Expressive;

/**
 * @extends Expressive<UserModel>
 */
final class User extends Expressive
{
    public string $name;

    public string $email;
}
```

With the default configuration, this class maps to `App\Models\User` because it lives in `App\Expressive` and has the same base name as the model. The generic `@extends` annotation is optional at runtime, but it helps static analysis understand which model type `model()` and `save()` return.

You may instantiate an Expressive object with an array of property values:

```php
$user = new App\Expressive\User([
    'name' => 'Wendell',
    'email' => 'wendell@example.com',
]);
```

Unknown array keys are ignored by the constructor.

## Mapping models explicitly

Use the `#[Model]` attribute when an Expressive class should map back to a model that cannot be resolved through the configured namespace and suffix:

```php
namespace App\Data;

use App\Models\User as UserModel;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;

/**
 * @extends Expressive<UserModel>
 */
#[Model(UserModel::class)]
final class PublicUser extends Expressive
{
    public string $name;
}
```

Use the `#[Expressive]` attribute on a model when the model should resolve a specific Expressive class:

```php
namespace App\Models;

use App\Data\PublicUser;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Attributes\Expressive;
use WendellAdriel\Expressive\Concerns\IsExpressive;

#[Expressive(PublicUser::class)]
final class User extends Model
{
    use IsExpressive;
}
```

Explicit attributes take precedence over implicit lookup.

## Mapping attribute names

By default, Expressive maps a property name to the snake-case Eloquent attribute key. The `rememberKey` property maps to `remember_key`, and the `emailVerifiedAt` property maps to `email_verified_at`.

Use `#[Map]` when the Eloquent key differs from the default conversion:

```php
use WendellAdriel\Expressive\Attributes\Map;

#[Map('remember_token')]
public ?string $rememberKey = null;
```

This affects model conversion, serialization visibility filtering, and persistence. The serialized key remains the Expressive property name unless you configure snake-case serialization.

## Virtual properties

Use `#[Virtual]` for values backed by Eloquent accessors instead of table columns:

```php
use WendellAdriel\Expressive\Attributes\Virtual;

#[Virtual]
public ?string $displayName = null;
```

Virtual properties are only populated when you request the matching accessor during conversion:

```php
$user = User::query()->findOrFail(1)->expressive(attributes: ['display_name']);
```

Expressive does not automatically append unavailable accessors. If you do not request the accessor, the virtual property is `null`.

## Nullable optional properties

Relationship and virtual properties must be nullable. Expressive uses `null` for relationships that were not loaded and for virtual accessors that were not requested.

```php
#[Virtual]
public ?string $displayName = null;
```

If a non-nullable property receives `null` during conversion, Expressive throws a `NonNullablePropertyException`. This protects typed objects from silently carrying invalid state.
