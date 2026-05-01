<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Expressive as ExpressiveBase;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Expressive
{
    /**
     * @param  class-string<ExpressiveBase<Model>>  $class
     */
    public function __construct(public string $class) {}
}
