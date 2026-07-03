<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

use RuntimeException;

final class ExistingExpressiveClassException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Expressive class already exists.');
    }
}
