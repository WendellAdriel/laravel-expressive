<?php

declare(strict_types=1);

use WendellAdriel\Expressive\Exceptions\JsonEncodingException;
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

it('encodes expressive objects to json from the array representation', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
    ]);

    expect($expressive->toJson())->toBe(json_encode($expressive->toArray(), JSON_THROW_ON_ERROR));
});

it('passes json encoding options through to toJson', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::Admin,
    ]);

    expect($expressive->toJson(JSON_PRETTY_PRINT))->toBe(json_encode($expressive->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
});

it('keeps hidden and visible rules applied in toJson output', function (): void {
    $expressive = new ExpressiveVisibleUser([
        'id' => 1,
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'password' => 'secret',
    ]);

    expect(json_decode($expressive->toJson(), true))->toBe([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
    ]);
});

it('recursively encodes nested expressive objects and collections to json', function (): void {
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

    $json = json_decode($expressive->toJson(), true);

    expect($json['address'])->toMatchArray(['street' => 'Main', 'city' => 'Lisbon'])
        ->and($json['posts'][0])->toMatchArray(['title' => 'First'])
        ->and($json['posts'][1])->toMatchArray(['title' => 'Second']);
});

it('wraps json encoding failures in a package exception', function (): void {
    $expressive = new ExpressiveUser([
        'name' => "\xB1\x31",
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
    ]);

    expect(fn (): string => $expressive->toJson())->toThrow(JsonEncodingException::class, 'Error encoding expressive');
});

it('preserves expressive property names by default', function (): void {
    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'rememberKey' => 'token',
    ]);

    expect($expressive->toArray())->toHaveKey('emailVerifiedAt')
        ->and($expressive->toArray())->not->toHaveKey('email_verified_at')
        ->and($expressive->toArray())->not->toHaveKey('rememberKey');
});

it('serializes output keys as snake case when configured', function (): void {
    config()->set('expressive.serialization.case', 'snake');

    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'rememberKey' => 'token',
    ]);

    expect($expressive->toArray())->toHaveKey('email_verified_at')
        ->not->toHaveKey('emailVerifiedAt')
        ->not->toHaveKey('remember_key')
        ->not->toHaveKey('rememberKey');
});

it('serializes nested expressive objects relationships and collections as snake case when configured', function (): void {
    config()->set('expressive.serialization.case', 'snake');

    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
        'emailVerifiedAt' => null,
        'address' => new ExpressiveAddress(['street' => 'Main', 'city' => 'Lisbon']),
        'posts' => collect([
            new ExpressivePost(['userId' => 1, 'title' => 'First']),
        ]),
    ]);

    $array = $expressive->toArray();

    expect($array)->toHaveKey('email_verified_at')
        ->and($array)->toHaveKey('unsupported_posts')
        ->and($array['address'])->toHaveKey('created_at')
        ->and($array['posts'][0])->toHaveKey('user_id')
        ->and($array['posts'][0])->not->toHaveKey('userId');
});

it('fails fast for unsupported serialization casing', function (): void {
    config()->set('expressive.serialization.case', 'studly');

    $expressive = new ExpressiveUser([
        'name' => 'Wendell',
        'email' => 'wendell@example.com',
        'role' => UserRole::User,
    ]);

    expect(fn (): array => $expressive->toArray())->toThrow(InvalidArgumentException::class, 'serialization.case');
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
