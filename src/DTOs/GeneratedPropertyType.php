<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

final readonly class GeneratedPropertyType
{
    /**
     * @param  list<string>  $imports
     */
    public function __construct(
        public string $type,
        public array $imports = [],
    ) {}
}
