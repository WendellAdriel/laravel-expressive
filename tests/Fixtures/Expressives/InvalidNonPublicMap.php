<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use WendellAdriel\Expressive\Attributes\Map;
use WendellAdriel\Expressive\Expressive;

final class InvalidNonPublicMap extends Expressive
{
    #[Map('full_name')]
    protected string $name;
}
