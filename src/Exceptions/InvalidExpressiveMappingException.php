<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class InvalidExpressiveMappingException extends ExpressiveException
{
    public static function nonPublicProperty(string $class, string $property): self
    {
        return new self("Expressive mapping attributes can only be used on public properties; [{$class}::\${$property}] is not public.");
    }
}
