<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use WendellAdriel\Expressive\Tests\Fixtures\Casts\MalformedCustomCast;

final readonly class CastValueObject implements Castable
{
    public function __construct(public string $value) {}

    /**
     * @param  array<int, mixed>  $arguments
     */
    public static function castUsing(array $arguments): string
    {
        return MalformedCustomCast::class;
    }
}
