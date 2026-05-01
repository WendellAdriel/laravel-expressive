<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Virtual
{
    public function __construct() {}
}
