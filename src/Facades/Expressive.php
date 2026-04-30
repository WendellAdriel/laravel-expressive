<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \WendellAdriel\Expressive\Expressive
 */
final class Expressive extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WendellAdriel\Expressive\Expressive::class;
    }
}
