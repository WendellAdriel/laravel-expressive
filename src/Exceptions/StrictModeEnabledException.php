<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class StrictModeEnabledException extends ExpressiveException
{
    public static function whenSaving(string $class): self
    {
        return new self(sprintf(
            'Cannot save Expressive object [%s] while strict mode is enabled. Convert it to an Eloquent model before persisting.',
            $class,
        ));
    }
}
