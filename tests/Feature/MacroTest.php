<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

beforeEach(function (): void {
    config()->set('expressive.namespace', 'WendellAdriel\\Expressive\\Tests\\Fixtures\\Expressives');
});

it('maps eloquent collections into new expressive collections', function (): void {
    User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);
    User::query()->create(['name' => 'Two', 'email' => 'two@example.com', 'role' => UserRole::Admin]);

    $models = User::query()->get();
    $expressives = $models->expressive();

    expect($expressives)->not->toBe($models)
        ->and($expressives)->toHaveCount(2)
        ->toContainOnlyInstancesOf(ExpressiveUser::class)
        ->sequence(
            fn ($expressive) => $expressive->name->toBe('One'),
            fn ($expressive) => $expressive->name->toBe('Two'),
        )
        ->and($models)->toContainOnlyInstancesOf(User::class);
});

it('maps builder results through the expressive macro', function (): void {
    User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);
    User::query()->create(['name' => 'Two', 'email' => 'two@example.com', 'role' => UserRole::Admin]);
    User::query()->create(['name' => 'Three', 'email' => 'three@example.com', 'role' => UserRole::Admin]);

    $expressives = User::query()->where('role', UserRole::Admin->value)->expressive();

    expect($expressives)->toHaveCount(2)
        ->toContainOnlyInstancesOf(ExpressiveUser::class)
        ->sequence(
            fn ($expressive) => $expressive->name->toBe('Two'),
            fn ($expressive) => $expressive->name->toBe('Three'),
        );
});

it('eagerly executes builder expressive and returns an in-memory collection', function (): void {
    User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);

    $expressives = User::query()->expressive();

    expect($expressives)->toBeInstanceOf(Collection::class)
        ->toContainOnlyInstancesOf(ExpressiveUser::class);
});

it('maps builder chunks through the expressive chunk macro', function (): void {
    User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);
    User::query()->create(['name' => 'Two', 'email' => 'two@example.com', 'role' => UserRole::Admin]);
    User::query()->create(['name' => 'Three', 'email' => 'three@example.com', 'role' => UserRole::Admin]);

    $chunks = [];

    $result = User::query()->orderBy('id')->expressiveChunk(2, function ($expressives) use (&$chunks): void {
        $chunks[] = $expressives->pluck('name')->all();
    });

    expect($result)->toBeTrue()
        ->and($chunks)->toBe([
            ['One', 'Two'],
            ['Three'],
        ]);
});

it('loads requested expressive chunk relationships at collection level', function (): void {
    $one = User::query()->create(['name' => 'One', 'email' => 'one@example.com', 'role' => UserRole::User]);
    $two = User::query()->create(['name' => 'Two', 'email' => 'two@example.com', 'role' => UserRole::Admin]);
    Post::query()->create(['user_id' => $one->id, 'title' => 'First']);
    Post::query()->create(['user_id' => $two->id, 'title' => 'Second']);

    DB::enableQueryLog();

    User::query()->orderBy('id')->expressiveChunk(2, function ($expressives): void {
        expect($expressives->first()->posts)->toHaveCount(1);
    }, relationships: ['posts']);

    $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains($query, 'expressive_posts'));

    expect($queries)->toHaveCount(1);
});

it('rejects invalid expressive chunk sizes', function (): void {
    expect(fn () => User::query()->expressiveChunk(0, fn (): null => null))->toThrow(InvalidArgumentException::class);
});
