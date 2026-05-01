<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class ExpressiveClassTarget
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        public string $modelClass,
        public string $namespace,
        public string $class,
        public string $expressiveClass,
        public string $path,
    ) {}
}
