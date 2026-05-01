<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class NonNullablePropertyException extends ExpressiveException
{
    public static function forProperty(string $class, string $property): self
    {
        return new self("Property [{$class}::\${$property}] must be nullable because Expressive may map missing attributes, unloaded relationships, or virtual values to null.");
    }
}
