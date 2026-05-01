<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

use ReflectionProperty;

final readonly class PropertyMetadata
{
    public function __construct(
        public string $name,
        public string $key,
        public ReflectionProperty $property,
        public bool $relationship,
        public bool $virtual,
        public bool $nullable,
    ) {}
}
