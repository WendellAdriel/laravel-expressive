<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use WendellAdriel\Expressive\Exceptions\NonExistingExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\NonNullablePropertyException;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Comment as ExpressiveComment;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Post as ExpressivePost;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Suffixed\UserExpressive;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Address;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Comment;
use WendellAdriel\Expressive\Tests\Fixtures\Models\ExplicitUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\InvalidRelationshipUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

beforeEach(function (): void {
    config()->set('expressive.namespace', 'WendellAdriel\\Expressive\\Tests\\Fixtures\\Expressives');
    config()->set('expressive.suffix', '');
});

it('converts scalar attributes, casts, hidden fields, and mapped fields to expressive objects', function (): void {
    $user = User::query()->create([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
        'password' => 'secret',
        'email_verified_at' => '2026-05-01 12:00:00',
    ]);
    $user->forceFill(['remember_token' => 'token'])->save();
    $user->refresh();

    $expressive = $user->expressive();

    expect($expressive)->toBeInstanceOf(ExpressiveUser::class)
        ->and($expressive->id)->toBe($user->id)
        ->and($expressive->name)->toBe('Wendell')
        ->and($expressive->role)->toBe(UserRole::Admin)
        ->and($expressive->rememberKey)->toBe('token')
        ->and($expressive->emailVerifiedAt?->toDateString())->toBe('2026-05-01')
        ->and($expressive->displayName)->toBeNull();
});

it('loads requested attributes and relationships without lazy loading unloaded relationships', function (): void {
    $user = User::query()->create(['name' => 'Wendell', 'email' => 'wendell@example.com', 'role' => UserRole::User]);
    Address::query()->create(['user_id' => $user->id, 'street' => 'Main', 'city' => 'Lisbon']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'First']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'Second']);

    Model::preventLazyLoading(true);

    try {
        $withoutRelations = $user->fresh()->expressive();

        expect($withoutRelations->address)->toBeNull()
            ->and($withoutRelations->posts)->toBeNull();

        $withRelations = $user->fresh()->expressive(relationships: ['address', 'posts'], attributes: ['display_name']);

        expect($withRelations->address?->city)->toBe('Lisbon')
            ->and($withRelations->posts)->toHaveCount(2)
            ->toContainOnlyInstancesOf(ExpressivePost::class)
            ->sequence(
                fn ($post) => $post->title->toBe('First'),
                fn ($post) => $post->title->toBe('Second'),
            )
            ->and($withRelations->displayName)->toBe('Wendell (user)');
    } finally {
        Model::preventLazyLoading(false);
    }
});

it('uses explicit model attributes before implicit lookup', function (): void {
    $model = new ExplicitUser;

    $model->setRawAttributes([
        'name' => 'Explicit',
        'email' => 'explicit@example.com',
        'role' => 'user',
    ], true);

    expect($model->expressive())->toBeInstanceOf(ExpressiveUser::class);
});

it('uses suffix config for implicit lookup', function (): void {
    config()->set('expressive.namespace', 'WendellAdriel\\Expressive\\Tests\\Fixtures\\Expressives\\Suffixed');
    config()->set('expressive.suffix', 'Expressive');

    $user = User::query()->create(['name' => 'Suffix', 'email' => 'suffix@example.com', 'role' => UserRole::User]);

    expect($user->expressive())->toBeInstanceOf(UserExpressive::class);
});

it('throws for missing implicit expressive classes and non-nullable optional properties', function (): void {
    config()->set('expressive.namespace', 'Missing\\Expressives');

    $user = User::query()->create(['name' => 'Missing', 'email' => 'missing@example.com', 'role' => UserRole::User]);

    expect(fn () => $user->expressive())->toThrow(NonExistingExpressiveClassException::class);

    config()->set('expressive.namespace', 'WendellAdriel\\Expressive\\Tests\\Fixtures\\Expressives');
    config()->set('expressive.suffix', '');

    $invalid = new InvalidRelationshipUser;
    $invalid->setRawAttributes(['name' => 'Invalid', 'email' => 'invalid@example.com', 'role' => 'user'], true);

    expect(fn () => $invalid->expressive())->toThrow(NonNullablePropertyException::class);
});

it('bulk loads requested relationships for collection conversion', function (): void {
    User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);
    User::query()->create(['name' => 'Two', 'email' => 'two@example.com', 'role' => UserRole::User]);

    DB::enableQueryLog();

    $users = User::query()->get()->expressive(relationships: ['posts']);

    $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains($query, 'expressive_posts'));

    expect($users)->toHaveCount(2)
        ->toContainOnlyInstancesOf(ExpressiveUser::class)
        ->and($queries)->toHaveCount(1);
});

it('converts loaded morph many relations without lazy loading', function (): void {
    $user = User::query()->create(['name' => 'Wendell', 'email' => 'wendell@example.com', 'role' => UserRole::User]);
    Comment::query()->create(['body' => 'First', 'commentable_type' => $user::class, 'commentable_id' => $user->id]);
    Comment::query()->create(['body' => 'Second', 'commentable_type' => $user::class, 'commentable_id' => $user->id]);

    Model::preventLazyLoading(true);

    try {
        $expressive = $user->fresh()->expressive(relationships: ['comments']);

        expect($expressive->comments)->toHaveCount(2)
            ->toContainOnlyInstancesOf(ExpressiveComment::class)
            ->sequence(
                fn ($comment) => $comment->body->toBe('First'),
                fn ($comment) => $comment->body->toBe('Second'),
            );
    } finally {
        Model::preventLazyLoading(false);
    }
});

it('does not change runtime morph conversion assumptions when a morph map is configured', function (): void {
    Relation::morphMap(['expressive-user' => User::class]);

    try {
        $user = User::query()->create(['name' => 'Wendell', 'email' => 'wendell@example.com', 'role' => UserRole::User]);
        $user->comments()->create(['body' => 'Mapped']);

        $expressive = $user->fresh()->expressive(relationships: ['comments']);

        expect($expressive->comments)->toHaveCount(1)
            ->and($expressive->comments->first())->toBeInstanceOf(ExpressiveComment::class)
            ->and($expressive->comments->first()->commentableType)->toBe('expressive-user');
    } finally {
        Relation::morphMap([], false);
    }
});
