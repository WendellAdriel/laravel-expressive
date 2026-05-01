<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use WendellAdriel\Expressive\Attributes\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\InvalidRelationship;

#[Expressive(InvalidRelationship::class)]
final class InvalidRelationshipUser extends User {}
