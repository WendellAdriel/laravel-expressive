<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Attributes\Map;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Attributes\Virtual;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User as UserModel;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

#[Model(UserModel::class)]
class User extends Expressive
{
    public ?int $id = null;

    public string $name;

    public string $email;

    public UserRole $role;

    public ?string $password = null;

    #[Map('remember_token')]
    public ?string $rememberKey = null;

    public ?CarbonInterface $emailVerifiedAt = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;

    #[Relationship]
    public ?Address $address = null;

    /** @var Collection<int, Post>|null */
    #[Relationship]
    public ?Collection $posts = null;

    #[Relationship]
    public ?Collection $unsupportedPosts = null;

    #[Virtual]
    public ?string $displayName = null;
}
