<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Profile as ProfileModel;

#[Model(ProfileModel::class)]
final class Profile extends Expressive
{
    public string $uuid;

    public string $name;
}
