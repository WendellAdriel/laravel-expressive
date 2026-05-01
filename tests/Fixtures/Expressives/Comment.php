<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Comment as CommentModel;

#[Model(CommentModel::class)]
final class Comment extends Expressive
{
    public ?int $id = null;

    public string $body;

    public ?string $commentableType = null;

    public ?int $commentableId = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
