<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Tag as TagModel;

#[Model(TagModel::class)]
final class Tag extends Expressive
{
    public ?int $id = null;

    public string $name;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
