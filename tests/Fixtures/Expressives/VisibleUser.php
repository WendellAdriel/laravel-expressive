<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\VisibleUser as VisibleUserModel;

#[Model(VisibleUserModel::class)]
final class VisibleUser extends Expressive
{
    public ?int $id = null;

    public string $name;

    public string $email;

    public ?string $password = null;
}
