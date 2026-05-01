<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class InvalidModelClassException extends ExpressiveException
{
    public static function forClass(string $class): self
    {
        return new self("Class [{$class}] must extend [Illuminate\\Database\\Eloquent\\Model].");
    }
}
