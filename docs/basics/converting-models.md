# Converting models

- [Introduction](#introduction)
- [Preparing a model](#preparing-a-model)
- [Converting a single model](#converting-a-single-model)
- [Loading accessors](#loading-accessors)
- [Converting collections](#converting-collections)
- [Converting builders](#converting-builders)
- [Converting large result sets](#converting-large-result-sets)

## Introduction

Expressive converts Eloquent models into typed objects by reading the public properties on the resolved Expressive class. You may convert one model, an Eloquent collection, or a builder result.

Requested relationships are loaded before conversion. Requested virtual attributes are appended before conversion. Unrequested relationships and virtual properties remain `null`.

## Preparing a model

Add the `IsExpressive` trait to each Eloquent model that should expose an `expressive()` method:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Concerns\IsExpressive;

final class User extends Model
{
    use IsExpressive;
}
```

The trait adds an `expressive()` method to the model. Expressive then resolves the target class through `#[Expressive]` or the configured namespace and suffix.

## Converting a single model

You may convert a model by calling `expressive()`:

```php
$user = User::query()->findOrFail(1)->expressive();
```

The returned object is an instance of the mapped Expressive class. Scalar attributes and cast values are copied into matching public properties.

## Loading accessors

Virtual properties backed by accessors are not populated unless you request them:

```php
$user = User::query()->findOrFail(1)->expressive(
    attributes: ['display_name'],
);
```

Expressive calls Eloquent's `append()` method for the requested attributes before mapping the model. The Expressive property should use `#[Virtual]` and must be nullable.

## Converting collections

Expressive registers an `expressive()` macro on Eloquent collections:

```php
$users = User::query()->get()->expressive();
```

You may pass relationships and attributes to the collection macro:

```php
$users = User::query()->get()->expressive(
    relationships: ['posts'],
    attributes: ['display_name'],
);
```

Relationships are loaded with collection-level `loadMissing()`, so a requested relationship is loaded for the whole collection before individual models are converted.

## Converting builders

Expressive also registers an `expressive()` macro on Eloquent builders:

```php
$users = User::query()
    ->where('active', true)
    ->expressive(relationships: ['posts']);
```

Calling `expressive()` on a builder executes `get()` immediately and returns an in-memory `Illuminate\Support\Collection` of Expressive objects.

## Converting large result sets

For larger result sets, use the `expressiveChunk()` builder macro:

```php
use Illuminate\Support\Collection;

User::query()->orderBy('id')->expressiveChunk(
    500,
    function (Collection $users): void {
        foreach ($users as $user) {
            // $user is an App\Expressive\User instance.
        }
    },
    relationships: ['posts'],
);
```

The chunk size must be at least `1`. The macro uses Laravel's `chunk()` method and converts each retrieved Eloquent collection before passing it to your callback.

Cursor-based conversion is not provided because Laravel cursors cannot eager load relationships.
