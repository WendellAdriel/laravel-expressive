<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class UnsupportedRelationshipPersistenceException extends ExpressiveException
{
    /**
     * @param  Relation<Model, Model, mixed>  $instance
     */
    public static function forRelation(string $model, string $relation, Relation $instance): self
    {
        return new self(sprintf(
            'Expressive save() does not support persisting [%s::%s] relationships of type [%s].',
            $model,
            $relation,
            $instance::class,
        ));
    }
}
