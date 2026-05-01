<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post as PostModel;

#[Model(PostModel::class)]
final class Post extends Expressive
{
    public ?int $id = null;

    public ?int $userId = null;

    public string $title;

    /** @var Collection<int, Tag>|null */
    #[Relationship]
    public ?Collection $tags = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
