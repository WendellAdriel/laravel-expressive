<?php

declare(strict_types=1);

use WendellAdriel\Expressive\Actions\ExpressiveMetadata;
use WendellAdriel\Expressive\Exceptions\InvalidExpressiveMappingException;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\InvalidNonPublicMap;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User;

it('reads public expressive property metadata', function (): void {
    $metadata = collect((new ExpressiveMetadata)->handle(User::class));

    expect($metadata->firstWhere('name', 'rememberKey')?->key)->toBe('remember_token')
        ->and($metadata->firstWhere('name', 'address')?->relationship)->toBeTrue()
        ->and($metadata->firstWhere('name', 'displayName')?->virtual)->toBeTrue();
});

it('rejects mapping attributes on non-public properties', function (): void {
    expect(fn () => (new ExpressiveMetadata)->handle(InvalidNonPublicMap::class))
        ->toThrow(InvalidExpressiveMappingException::class);
});
