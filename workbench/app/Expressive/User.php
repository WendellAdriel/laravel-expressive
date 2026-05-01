<?php

namespace Workbench\App\Expressive;

use Carbon\CarbonInterface;
use WendellAdriel\Expressive\Attributes\Map;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use Workbench\App\Models\User as UserModel;

/**
 * @extends Expressive<UserModel>
 */
#[Model(UserModel::class)]
final class User extends Expressive
{
    public ?int $id = null;

    public string $name;

    public string $email;

    public string $password;

    #[Map('remember_token')]
    public ?string $rememberKey = null;

    public ?CarbonInterface $emailVerifiedAt = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
