<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post as PostModel;

#[Model(PostModel::class)]
final class Post extends Expressive
{
    public ?int $id = null;

    public ?int $userId = null;

    public string $title;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
