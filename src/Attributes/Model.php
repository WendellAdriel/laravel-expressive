<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model as EloquentModel;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Model
{
    /**
     * @param  class-string<EloquentModel>  $class
     */
    public function __construct(public string $class) {}
}
