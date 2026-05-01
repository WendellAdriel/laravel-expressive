<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class UnsupportedGenerationException extends ExpressiveException
{
    public static function missingTable(string $model, string $table): self
    {
        return new self("Cannot generate an Expressive class for [{$model}] because table [{$table}] does not exist.");
    }
}
