<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

final readonly class ExpressivePropertyShape
{
    public function __construct(
        public string $kind,
        public string $property,
        public string $key,
        public string $type,
    ) {}

    public function identifier(): string
    {
        return $this->kind.':'.$this->property;
    }
}
