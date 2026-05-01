<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use WendellAdriel\Expressive\Exceptions\UnsupportedRelationshipPersistenceException;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Address as ExpressiveAddress;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Post as ExpressivePost;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Profile as ExpressiveProfile;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Address;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Profile;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

it('converts expressive objects to unsaved models with fillable mapped attributes only', function (): void {
    $expressive = new ExpressiveUser([
        'id' => 99,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
        'password' => 'secret',
        'rememberKey' => 'token',
        'displayName' => 'Ignored',
    ]);

    $model = $expressive->model();

    expect($model)->toBeInstanceOf(User::class)
        ->and($model->exists)->toBeFalse()
        ->and($model->name)->toBe('Wendell')
        ->and($model->role)->toBe(UserRole::Admin)
        ->and($model->getAttribute('remember_token'))->toBeNull()
        ->and($model->getAttributes())->not->toHaveKey('display_name')
        ->and($model->id)->toBeNull();
});

it('attaches converted relationships in memory without persisting them', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'address' => new ExpressiveAddress(['street' => 'Main', 'city' => 'Lisbon']),
        'posts' => collect([
            new ExpressivePost(['title' => 'First']),
            new ExpressivePost(['title' => 'Second']),
        ]),
    ]);

    $model = $expressive->model();

    expect($model->relationLoaded('address'))->toBeTrue()
        ->and($model->address)->toBeInstanceOf(Address::class)
        ->and($model->relationLoaded('posts'))->toBeTrue()
        ->and($model->posts)->toBeInstanceOf(EloquentCollection::class)
        ->and(User::query()->count())->toBe(0)
        ->and(Address::query()->count())->toBe(0)
        ->and(Post::query()->count())->toBe(0);

    $model->save();

    expect(User::query()->count())->toBe(1)
        ->and(Address::query()->count())->toBe(0)
        ->and(Post::query()->count())->toBe(0);
});

it('saves supported relationship state explicitly', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'address' => new ExpressiveAddress(['street' => 'Main', 'city' => 'Lisbon']),
        'posts' => collect([
            new ExpressivePost(['title' => 'First']),
            new ExpressivePost(['title' => 'Second']),
        ]),
    ]);

    $model = $expressive->save();

    expect($model->exists)->toBeTrue()
        ->and($model->getKey())->not->toBeNull()
        ->and(User::query()->count())->toBe(1)
        ->and(Address::query()->where('user_id', $model->getKey())->count())->toBe(1)
        ->and(Post::query()->where('user_id', $model->getKey())->count())->toBe(2);
});

it('supports string non-incrementing primary keys', function (): void {
    $model = (new ExpressiveProfile(['uuid' => 'profile-1', 'name' => 'Profile']))->save();

    expect($model)->toBeInstanceOf(Profile::class)
        ->and($model->getKey())->toBe('profile-1')
        ->and(Profile::query()->whereKey('profile-1')->exists())->toBeTrue();
});

it('throws for unsupported relationship persistence', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'unsupportedPosts' => collect([new ExpressivePost(['title' => 'First'])]),
    ]);

    expect(fn () => $expressive->save())->toThrow(UnsupportedRelationshipPersistenceException::class);
});
