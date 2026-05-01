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

`model()` fills only fillable, non-virtual, non-relationship properties and attaches relationship values in memory with `setRelation()`. `save()` saves the root model and supported `HasOne`, `BelongsTo`, and `HasMany` relationship values. Many-to-many and polymorphic persistence are intentionally unsupported in v1.

Deletion is not provided on Expressive objects. Convert to an Eloquent model and call Eloquent deletion explicitly when needed.

## Generator

Generate a class from a model with:

```bash
php artisan make:expressive User --model="App\Models\User"
```

Useful options:

- `--namespace="App\Data"`
- `--suffix="Expressive"`
- `--force`

## Configuration

The generator inspects the model table, casts, relationships, and accessors to create public properties with the needed Expressive attributes.
