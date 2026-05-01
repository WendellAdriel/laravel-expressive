<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class NonExistingExpressiveClassException extends ExpressiveException
{
    public static function forClass(string $class): self
    {
        return new self("Expressive class [{$class}] does not exist.");
    }
}
