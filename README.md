<div align="center">
    <img src="https://github.com/wendelladriel/laravel-expressive/raw/main/art/banner.png" alt="Expressive" height="300"/>
    <p>
        <h1>Expressive</h1>
        Typed Objects for Eloquent
    </p>
</div>

<p align="center">
    <a href="https://packagist.org/packages/wendelladriel/laravel-expressive"><img src="https://img.shields.io/packagist/v/wendelladriel/laravel-expressive.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/wendelladriel/laravel-expressive"><img src="https://img.shields.io/packagist/php-v/wendelladriel/laravel-expressive.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/wendelladriel/laravel-expressive"><img src="https://badge.laravel.cloud/badge/wendelladriel/laravel-expressive?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/wendelladriel/laravel-expressive/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/wendelladriel/laravel-expressive/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/wendelladriel/laravel-expressive"><img src="https://img.shields.io/packagist/dt/wendelladriel/laravel-expressive.svg?style=flat-square" alt="Total Downloads"></a>
</p>

## Installation

You can install the package via composer:

```bash
composer require wendelladriel/laravel-expressive
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="expressive"
```

## Usage

Add the `IsExpressive` trait to models that should convert to typed Expressive objects:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use WendellAdriel\Expressive\Concerns\IsExpressive;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use IsExpressive, Notifiable;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->name} ({$this->role->value})",
        );
    }
}
```

Generate an Expressive class from a model:

```bash
php artisan make:expressive User --model="App\Models\User"
```

The generated class will live in `App\Expressive` by default:

```php
use App\Enums\UserRole;
use App\Expressive\Address;
use App\Expressive\Post;
use App\Models\User as UserModel;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Attributes\Virtual;
use WendellAdriel\Expressive\Expressive;

/**
 * @extends Expressive<UserModel>
 */
final class User extends Expressive
{
    public ?int $id = null;

    public string $name;

    public string $email;

    public UserRole $role;

    public string $password;

    public ?string $rememberToken = null;

    public ?CarbonInterface $emailVerifiedAt = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;

    #[Relationship]
    public ?Address $address = null;

    /** @var Collection<int, Post>|null */
    #[Relationship]
    public ?Collection $posts = null;

    #[Virtual]
    public ?string $displayName = null;
}
```

Loaded relationships are converted recursively. A `HasOne` relation becomes the related model's Expressive object, and a `HasMany` relation becomes a `Collection` of Expressive objects.

Convert models, collections, and builders:

```php
$user = User::findOrFail(1)->expressive(attributes: ['display_name']);
```

```php
$users = User::query()->get()->expressive(relationships: ['posts']);
```

```php
$users = User::query()
    ->where('active', true)
    ->expressive(relationships: ['posts']);
```

Access the full documentation [here](https://laravel-expressive.wendelladriel.com).

## Changelog

Please see the [changelog](https://laravel-expressive.wendelladriel.com/getting-started/changelog) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Expressive! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Wendell Adriel](https://github.com/WendellAdriel)
- [All Contributors](../../contributors)

## License

Expressive is open-sourced software licensed under the [MIT license](LICENSE.md).
