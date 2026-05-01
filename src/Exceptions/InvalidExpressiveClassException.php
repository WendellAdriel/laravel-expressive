<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class InvalidExpressiveClassException extends ExpressiveException
{
    public static function forClass(string $class): self
    {
        return new self("Class [{$class}] must extend [WendellAdriel\\Expressive\\Expressive].");
    }
}
