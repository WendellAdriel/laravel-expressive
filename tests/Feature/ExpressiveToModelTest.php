<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use WendellAdriel\Expressive\Exceptions\InvalidConversionValueException;
use WendellAdriel\Expressive\Exceptions\UnfillableExpressivePropertyException;
use WendellAdriel\Expressive\Exceptions\UnsupportedRelationshipPersistenceException;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Address as ExpressiveAddress;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Comment as ExpressiveComment;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Post as ExpressivePost;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Profile as ExpressiveProfile;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Tag as ExpressiveTag;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Address;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Comment;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Profile;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Tag;
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

it('keeps ignoring unfillable mapped attributes by default', function (): void {
    $expressive = new ExpressiveUser([
        'id' => 99,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
    ]);

    expect($expressive->model()->getKey())->toBeNull();
});

it('throws for unfillable mapped attributes during model conversion when diagnostics are enabled', function (): void {
    config()->set('expressive.diagnostics.throw_on_unfillable', true);

    $expressive = new ExpressiveUser([
        'id' => 99,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
    ]);

    expect(fn () => $expressive->model())
        ->toThrow(UnfillableExpressivePropertyException::class, 'Expressive\\Tests\\Fixtures\\Expressives\\User::$id');
});

it('throws for unfillable mapped attributes during save before writing rows', function (): void {
    config()->set('expressive.diagnostics.throw_on_unfillable', true);

    $expressive = new ExpressiveUser([
        'id' => 99,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
    ]);

    expect(fn () => $expressive->save())->toThrow(UnfillableExpressivePropertyException::class)
        ->and(User::query()->count())->toBe(0);
});

it('excludes virtual and relationship properties from fillable diagnostics', function (): void {
    config()->set('expressive.diagnostics.throw_on_unfillable', true);

    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
        'displayName' => 'Ignored',
        'address' => new ExpressiveAddress(['street' => 'Main', 'city' => 'Lisbon']),
    ]);

    expect($expressive->model()->relationLoaded('address'))->toBeTrue();
});

it('respects guarded model fillable behavior in diagnostics', function (): void {
    config()->set('expressive.diagnostics.throw_on_unfillable', true);

    expect((new ExpressivePost(['title' => 'First']))->model())->toBeInstanceOf(Post::class);
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

it('saves morph many relationship state explicitly', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'comments' => collect([
            new ExpressiveComment(['body' => 'First']),
            new ExpressiveComment(['body' => 'Second']),
        ]),
    ]);

    $model = $expressive->save();

    expect(Comment::query()->where('commentable_type', $model::class)->where('commentable_id', $model->getKey())->pluck('body')->all())
        ->toBe(['First', 'Second']);
});

it('supports string non-incrementing primary keys', function (): void {
    $model = (new ExpressiveProfile(['uuid' => 'profile-1', 'name' => 'Profile']))->save();

    expect($model)->toBeInstanceOf(Profile::class)
        ->and($model->getKey())->toBe('profile-1')
        ->and(Profile::query()->whereKey('profile-1')->exists())->toBeTrue();
});

it('throws for unsupported belongs to many relationship persistence without writing pivot rows', function (): void {
    $post = Post::query()->create(['title' => 'First']);
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'unsupportedPosts' => collect([$post]),
    ]);

    expect(fn () => $expressive->save())
        ->toThrow(UnsupportedRelationshipPersistenceException::class, 'explicitly unsupported');

    expect(DB::table('expressive_post_user')->count())->toBe(0);
});

it('throws for unsupported morph to many relationship persistence without writing pivot rows', function (): void {
    $tag = Tag::query()->create(['name' => 'Laravel']);
    $expressive = new ExpressivePost([
        'title' => 'First',
        'tags' => collect([new ExpressiveTag(['id' => $tag->id, 'name' => $tag->name])]),
    ]);

    expect(fn () => $expressive->save())
        ->toThrow(UnsupportedRelationshipPersistenceException::class, 'explicitly unsupported');

    expect(DB::table('expressive_taggables')->count())->toBe(0);
});

it('includes relationship value context when conversion fails', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'posts' => collect(['invalid']),
    ]);

    expect(fn () => $expressive->model())
        ->toThrow(InvalidConversionValueException::class, 'Expressives\\User::$posts] contains [string]');
});
