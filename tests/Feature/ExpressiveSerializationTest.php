<?php

declare(strict_types=1);

use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Address as ExpressiveAddress;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\Post as ExpressivePost;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\VisibleUser as ExpressiveVisibleUser;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

it('serializes initialized public properties with expressive property names', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
        'address' => null,
    ]);

    expect($expressive->toArray())->toMatchArray([
        'id' => null,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
        'address' => null,
    ])->not->toHaveKey('remember_token');
});

it('serializes nested expressive objects and collections recursively', function (): void {
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

    $array = $expressive->toArray();

    expect($array['address'])->toMatchArray(['street' => 'Main', 'city' => 'Lisbon'])
        ->and($array['posts'][0])->toMatchArray(['title' => 'First'])
        ->and($array['posts'][1])->toMatchArray(['title' => 'Second']);
});

it('json serializes to the same array representation', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
    ]);

    expect($expressive->jsonSerialize())->toBe($expressive->toArray())
        ->and(json_decode((string) json_encode($expressive), true)['role'])->toBe('user');
});

it('skips uninitialized typed properties without triggering typed property errors', function (): void {
    $expressive = new ExpressiveUser;

    expect($expressive->toArray())->toBe([
        'id' => null,
        'emailVerifiedAt' => null,
        'createdAt' => null,
        'updatedAt' => null,
        'address' => null,
        'posts' => null,
        'unsupportedPosts' => null,
        'comments' => null,
        'displayName' => null,
    ]);
});

it('applies eloquent hidden rules using mapped attribute keys', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'password' => 'secret',
        'rememberKey' => 'token',
    ]);

    expect($expressive->toArray())
        ->not->toHaveKey('password')
        ->not->toHaveKey('rememberKey');
});

it('applies eloquent visible rules using mapped attribute keys', function (): void {
    $expressive = new ExpressiveVisibleUser([
        'id' => 1,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'password' => 'secret',
    ]);

    expect($expressive->toArray())->toBe([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
    ]);
});
