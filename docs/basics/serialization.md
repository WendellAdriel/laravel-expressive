# Serialization

- [Introduction](#introduction)
- [Array serialization](#array-serialization)
- [JSON serialization](#json-serialization)
- [Nested objects and collections](#nested-objects-and-collections)
- [Hidden and visible attributes](#hidden-and-visible-attributes)
- [Key casing](#key-casing)
- [Uninitialized properties](#uninitialized-properties)

## Introduction

Expressive objects implement Laravel's `Arrayable` contract and PHP's `JsonSerializable` interface. You may return their array representation from your own application layer or encode them as JSON.

Serialization follows Expressive properties, not the full Eloquent model array output. Use API resources or transformers when your response shape should differ from the Expressive object.

## Array serialization

Use `toArray()` to serialize an Expressive object:

```php
$array = $user->toArray();
```

The array contains initialized public Expressive properties that pass the mapped model's visibility rules. Mapped property names are used as output keys by default:

```php
[
    'name' => 'Wendell',
    'emailVerifiedAt' => null,
]
```

## JSON serialization

Use `toJson()` to encode the same array representation:

```php
$json = $user->toJson();
```

You may pass normal JSON encoding options:

```php
$json = $user->toJson(JSON_PRETTY_PRINT);
```

Calling `json_encode()` on an Expressive object also uses the `toArray()` representation because Expressive implements `JsonSerializable`.

## Nested objects and collections

Nested Expressive objects are serialized recursively:

```php
[
    'address' => [
        'street' => 'Main',
        'city' => 'Lisbon',
    ],
]
```

Collections are mapped item by item. If a collection contains Expressive objects, each object is converted to its array representation.

## Hidden and visible attributes

Expressive applies the mapped Eloquent model's `hidden` and `visible` rules during serialization. Filtering uses the underlying Eloquent key, not only the public property name.

For example, this Expressive property maps to `remember_token`:

```php
use WendellAdriel\Expressive\Attributes\Map;

#[Map('remember_token')]
public ?string $rememberKey = null;
```

If the model hides `remember_token`, the serialized array omits `rememberKey` even though the Expressive property is initialized.

## Key casing

By default, Expressive preserves public property names:

```php
'serialization' => [
    'case' => 'preserve',
],
```

Set the option to `snake` when serialized keys should use snake case:

```php
'serialization' => [
    'case' => 'snake',
],
```

With snake-case serialization, `emailVerifiedAt` becomes `email_verified_at`. Visibility filtering still uses the mapped Eloquent key.

Unsupported casing values throw an `InvalidArgumentException` when serialization runs.

## Uninitialized properties

Expressive skips uninitialized typed properties during serialization. Nullable properties with default `null` values are initialized and will appear unless filtered by visibility rules.

```php
final class User extends Expressive
{
    public string $name;

    public ?string $displayName = null;
}
```

In this example, a new `User` object without a `name` value skips `name` and includes `displayName` as `null` unless the mapped model hides it.
