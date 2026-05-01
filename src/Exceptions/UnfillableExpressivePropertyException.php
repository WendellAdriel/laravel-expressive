<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class UnfillableExpressivePropertyException extends ExpressiveException
{
    public static function forProperty(string $class, string $property, string $key, string $model): self
    {
        return new self(sprintf(
            'Property [%s::$%s] maps to [%s] on [%s], but that attribute is not fillable.',
            $class,
            $property,
            $key,
            $model,
        ));
    }
}
