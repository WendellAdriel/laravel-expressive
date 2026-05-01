<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class NonExistingModelClassException extends ExpressiveException
{
    public static function forClass(string $class): self
    {
        return new self("Model class [{$class}] does not exist.");
    }
}
