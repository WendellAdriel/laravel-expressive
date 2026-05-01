<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

final readonly class ExpressiveShapeDifference
{
    public function __construct(
        public string $kind,
        public string $modelClass,
        public string $expressiveClass,
        public string $property,
        public string $key,
        public string $expectedType,
        public string $actualType,
        public string $suggestion,
    ) {}
}
