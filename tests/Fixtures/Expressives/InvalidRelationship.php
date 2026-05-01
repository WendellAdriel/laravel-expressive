<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User as UserModel;

#[Model(UserModel::class)]
final class InvalidRelationship extends Expressive
{
    public string $name;

    #[Relationship]
    public Address $address;
}
