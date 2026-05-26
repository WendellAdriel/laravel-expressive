<div align="center">
    <img src="/banner.png" alt="Expressive" style="width: 520px; max-width: 100%; height: auto; margin-bottom: 2rem;">
</div>

# Expressive

- [Introduction](#introduction)
- [When to use Expressive](#when-to-use-expressive)
- [How Expressive works](#how-expressive-works)
- [Documentation](#documentation)

## Introduction

Expressive provides Typed Objects for Eloquent. It converts Eloquent models into typed PHP objects and can convert those objects back into Eloquent models when the application needs to persist data.

Eloquent remains responsible for querying, relationships, casts, visibility rules, mass assignment, and database writes. Expressive gives the rest of your application a typed object boundary without replacing Eloquent.

## When to use Expressive

Expressive is useful when you want application or domain code to work with public typed properties instead of passing Eloquent models through every layer. This keeps database behavior close to Eloquent while allowing services, actions, and tests to depend on small data objects.

You may use Expressive objects for read models, command inputs, workflow state, or other application boundaries where typed properties make the code easier to follow. Keep API-specific response shaping in resources or controllers when your response contract is different from the Expressive object.

## How Expressive works

Models opt in with the `IsExpressive` trait. Calling `expressive()` resolves the matching Expressive class, reads its public properties, and fills those properties from the model's attributes, requested accessors, and requested relationships.

```php
$user = User::query()->findOrFail(1)->expressive(
    relationships: ['posts'],
    attributes: ['display_name'],
);
```

Expressive classes extend `WendellAdriel\Expressive\Expressive`. They declare public typed properties and may use attributes such as `#[Map]`, `#[Relationship]`, `#[Virtual]`, and `#[Model]` to describe how those properties map to Eloquent.

```php
use App\Models\User as UserModel;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;

/**
 * @extends Expressive<UserModel>
 */
#[Model(UserModel::class)]
final class User extends Expressive
{
    public string $name;

    public string $email;
}
```

## Documentation

Read the docs in this order if you are adding Expressive to an application for the first time:

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Defining Expressive classes](defining-expressives.md)
- [Converting models](converting-models.md)
- [Relationships](relationships.md)
- [Serialization](serialization.md)
- [Persistence](persistence.md)
- [Generating Expressive classes](generator.md)
- [Syncing generated classes](syncing.md)
