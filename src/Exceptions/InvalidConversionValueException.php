<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class InvalidConversionValueException extends ExpressiveException
{
    public static function nonModelCollectionItem(mixed $item): self
    {
        $type = is_object($item) ? $item::class : get_debug_type($item);

        return new self("Eloquent collection expressive conversion only supports model items; [{$type}] given.");
    }

    public static function unsupportedRelationshipValue(string $class, string $property, mixed $value): self
    {
        $type = is_object($value) ? $value::class : get_debug_type($value);

        return new self("Property [{$class}::\${$property}] contains [{$type}], which cannot be converted to an Eloquent relationship value.");
    }
}
