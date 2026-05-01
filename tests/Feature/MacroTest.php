<?php

declare(strict_types=1);

use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
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
