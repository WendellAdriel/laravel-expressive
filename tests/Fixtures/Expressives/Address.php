<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Expressives;

use Carbon\CarbonInterface;
use WendellAdriel\Expressive\Attributes\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Address as AddressModel;

#[Model(AddressModel::class)]
final class Address extends Expressive
{
    public ?int $id = null;

    public ?int $userId = null;

    public string $street;

    public string $city;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;
}
