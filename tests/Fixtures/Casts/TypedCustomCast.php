<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Tests\Fixtures\ValueObjects\CastValueObject;

/**
 * @implements CastsAttributes<CastValueObject, array|string|null>
 */
final class TypedCustomCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): CastValueObject
    {
        return new CastValueObject((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
