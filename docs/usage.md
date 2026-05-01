# Usage

## Model to Expressive

Use the `IsExpressive` trait on any Eloquent model that should expose typed proxy objects:

```php
use WendellAdriel\Expressive\Concerns\IsExpressive;

class User extends Model
{
    use IsExpressive;
}
```

By default, `App\Models\User` maps to `App\Expressive\User`. Set `expressive.suffix` to generate and resolve names like `UserExpressive`.

```php
$user = User::findOrFail(1)->expressive();
$user = User::findOrFail(1)->expressive(attributes: ['display_name']);
$user = User::findOrFail(1)->expressive(relationships: ['posts']);
```

For explicit mapping, add `#[Expressive(CustomUser::class)]` to the model.

## Expressive Classes

Expressive objects extend `WendellAdriel\Expressive\Expressive` and use public typed properties:

```php
use App\Expressive\Post;
use App\Models\User as UserModel;
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Attributes\Map;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Attributes\Virtual;
use WendellAdriel\Expressive\Expressive;

/**
 * @extends Expressive<UserModel>
 */
#[Model(UserModel::class)]
final class User extends Expressive
{
    public ?int $id = null;
    public string $name;
    public string $email;

    #[Map('remember_token')]
    public ?string $rememberKey = null;

    /** @var Collection<int, Post>|null */
    #[Relationship]
    public ?Collection $posts = null;

    #[Virtual]
    public ?string $displayName = null;
}
```

Relationships and virtual properties must be nullable because unloaded relationships and unavailable accessors map to `null` without triggering lazy loading. Loaded relationships are converted recursively, so a `HasOne` relation becomes that related model's Expressive object and a `HasMany` relation becomes a `Collection` of Expressive objects.

## Collections and Builders

Expressive registers `expressive()` macros on Eloquent collections and builders:

```php
$users = User::query()->where('active', true)->expressive();
$users = User::query()->get()->expressive(relationships: ['posts']);
```

Collection conversion uses collection-level `loadMissing()` for requested relationships.

Calling `expressive()` on a builder executes `get()` immediately and returns an in-memory `Collection` of Expressive objects. For larger datasets, use `expressiveChunk()` to convert one Eloquent chunk at a time:

```php
User::query()->expressiveChunk(500, function (Collection $users): void {
    foreach ($users as $user) {
        // $user is an App\Expressive\User instance.
    }
}, relationships: ['posts']);
```

`expressiveChunk()` uses Laravel's builder `chunk()` method and converts each retrieved Eloquent collection with the same relationship and attribute options. You can also use Laravel's chunking APIs directly:

```php
User::query()->chunkById(500, fn ($users) => $users->expressive(relationships: ['posts']));
```

Cursor-based conversion is not provided because Laravel cursors cannot eager load relationships.

## Serialization

Expressive objects implement `Arrayable` and `JsonSerializable`:

```php
$array = $user->toArray();
$json = $user->toJson();
$json = json_encode($user);
```

Serialization uses initialized public Expressive property names as keys, including mapped property names like `rememberKey`. Nested Expressive objects and collections of Expressive objects are converted recursively. Uninitialized typed properties are skipped, and nullable relationship properties serialize as `null` when they were not loaded or assigned.

Expressive serialization applies the mapped Eloquent model's `hidden` and `visible` rules using the underlying model attribute or relationship key. For example, a property mapped with `#[Map('remember_token')]` is omitted when `remember_token` is hidden on the model, even though the serialized key would otherwise be `rememberKey`.

`toJson()` encodes the same filtered array representation and accepts normal JSON encoding options:

```php
$json = $user->toJson(JSON_PRETTY_PRINT);
```

Set `expressive.serialization.case` to `snake` when API responses should use snake-case keys. The default is `preserve`, and filtering still uses the mapped Eloquent keys:

```php
'serialization' => [
    'case' => 'snake',
],
```

Expressive serialization does not replace API resources or automatically append unavailable accessors. Keep API-specific response shapes in your application layer.

## Expressive to Eloquent

Use `model()` for in-memory conversion and `save()` for explicit persistence:

```php
$expressive = new App\Expressive\User([
    'name' => 'Wendell',
    'email' => 'wendell@example.com',
]);

$model = $expressive->model();
$saved = $expressive->save();
```

`model()` fills only fillable, non-virtual, non-relationship properties and attaches relationship values in memory with `setRelation()`.

`save()` saves the root model and only persists relationships that have direct, non-destructive Eloquent write semantics:

| Relationship | `save()` persistence |
|--------------|----------------------|
| `BelongsTo` | Supported |
| `HasOne` | Supported |
| `MorphOne` | Supported |
| `HasMany` | Supported |
| `MorphMany` | Supported |
| `BelongsToMany` | Unsupported |
| `MorphToMany` / `morphedByMany()` | Unsupported |
| `HasOneThrough` | Unsupported |
| `HasManyThrough` | Unsupported |
| Custom or future relation classes | Unsupported unless explicitly documented |

Unsupported persistence does not mean unsupported conversion. Loaded or requested relationships can still be converted into Expressive properties when the class exposes them. The unsupported part is writing those relationships through `Expressive::save()`.

Many-to-many persistence is intentionally unsupported until an explicit attach or sync API is designed. Through relationships are also unsupported for `save()` because the related records are reached through an intermediate model rather than a direct foreign key owned by the relationship target.

Deletion is not provided on Expressive objects. Convert to an Eloquent model and call Eloquent deletion explicitly when needed.

## Diagnostics

By default, Expressive keeps Laravel's mass-assignment behavior conservative and silently ignores non-fillable mapped attributes during `model()` and `save()` conversion.

Enable diagnostics when adopting Expressive in an existing application and you want unpersistable mapped properties to fail fast:

```php
'diagnostics' => [
    'throw_on_unfillable' => true,
],
```

This differs from Laravel's `preventSilentlyDiscardingAttributes()` because it runs against Expressive properties before the package calls `fill()`. Virtual properties and relationship properties are excluded from this diagnostic.

## Generator

Generate a class from a model with:

```bash
php artisan make:expressive User --model="App\Models\User"
```

Useful options:

- `--namespace="App\Data"`
- `--suffix="Expressive"`
- `--with-attributes`
- `--without-attributes`
- `--attributes="name,email,display_name"`
- `--with-relationships`
- `--without-relationships`
- `--relationships="posts,address"`
- `--include-hidden`
- `--exclude-hidden`
- `--hint-morph-map`
- `--dry-run`
- `--force`

Use `expressive:sync` to validate an existing Expressive class against the current model shape:

```bash
php artisan expressive:sync User --model="App\Models\User"
```

The command reports missing attributes, stale properties, relationship drift, and invalid generated types. It never changes files unless `--write` is passed. Write mode only rewrites when the class appears safely generated; otherwise it fails with an actionable message.

## Configuration

The generator inspects the model table, casts, relationships, and accessors to create public properties with the needed Expressive attributes.

Project-wide generator defaults live under `expressive.generator` and are overridden by explicit CLI flags for a single run:

```php
'generator' => [
    'with_attributes' => true,
    'with_relationships' => true,
    'exclude_hidden' => false,
    'hint_morph_map' => false,
],
```
