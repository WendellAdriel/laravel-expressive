<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

final class UnsupportedGenerationException extends ExpressiveException
{
    public static function missingTable(string $model, string $table): self
    {
        return new self("Cannot generate an Expressive class for [{$model}] because table [{$table}] does not exist.");
    }

    /**
     * @param  list<string>  $relationships
     */
    public static function unknownRelationships(string $model, array $relationships): self
    {
        return new self('Cannot generate selected relationships for ['.$model.'] because these relationships do not exist: ['.implode(', ', $relationships).'].');
    }

    /**
     * @param  list<string>  $attributes
     */
    public static function unknownAttributes(string $model, array $attributes): self
    {
        return new self('Cannot generate selected attributes for ['.$model.'] because these attributes do not exist: ['.implode(', ', $attributes).'].');
    }
}
