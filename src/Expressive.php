<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Support\ExpressiveMapper;

/**
 * @template-covariant TModel of Model
 */
abstract class Expressive
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * @return TModel
     */
    public function model(): Model
    {
        /** @var TModel $model */
        $model = ExpressiveMapper::toModel($this);

        return $model;
    }

    /**
     * @return TModel
     */
    public function save(): Model
    {
        /** @var TModel $model */
        $model = ExpressiveMapper::save($this);

        return $model;
    }
}
