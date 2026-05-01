<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use WendellAdriel\Expressive\Attributes\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Expressives\User as ExpressiveUser;

#[Expressive(ExpressiveUser::class)]
final class ExplicitUser extends User {}
