<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Concerns;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Expressive;
use WendellAdriel\Expressive\Support\ExpressiveMapper;

trait IsExpressive
{
    /**
     * @return Expressive<Model>
     */
    public function expressive(array|string $relationships = [], array|string $attributes = []): Expressive
    {
        /** @var Model $this */
        return ExpressiveMapper::fromModel($this, $relationships, $attributes);
    }
}
