<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

final readonly class GenerateExpressiveClassOptions
{
    /**
     * @param  list<string>|null  $attributes
     * @param  list<string>|null  $relationships
     */
    public function __construct(
        public bool $withoutAttributes = false,
        public ?array $attributes = null,
        public bool $withoutRelationships = false,
        public ?array $relationships = null,
        public bool $excludeHidden = false,
        public bool $hintMorphMap = false,
    ) {}
}
