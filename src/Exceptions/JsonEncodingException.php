<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

use JsonException;

final class JsonEncodingException extends ExpressiveException
{
    public static function forExpressive(string $class, JsonException $exception): self
    {
        return new self("Error encoding expressive [{$class}] to JSON: {$exception->getMessage()}", previous: $exception);
    }
}
